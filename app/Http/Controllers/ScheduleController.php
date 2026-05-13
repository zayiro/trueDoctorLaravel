<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{    
    public function index(Address $address)
    {
        $doctor = auth()->user()->doctor;

        if ($address->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }
        
        $schedules = Schedule::where('address_id', $address->id)->orderBy('day')->get();
        
        // Obtenemos las ausencias futuras para mostrar en la lista
        $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        return view('partner.schedules.index', compact('address', 'schedules', 'unavailabilities'));
    }

    public function edit(Address $address)
    {
        // Cargamos los horarios de esta dirección específica
        $schedules = $address->schedules()->orderBy('day')->get();
        
        return view('partner.schedules.edit', compact('address', 'schedules'));
    }

    public function update(Request $request, Address $address)
    {
        $request->validate([
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required',
        ]);

        foreach ($request->schedules as $id => $data) {
            Schedule::where('id', $id)
                ->where('address_id', $address->id) // Validación de seguridad
                ->update([
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                    'is_active'  => isset($data['is_active']),
                ]);
        }

        return redirect()->route('doctor.addresses.index')
            ->with('success', 'Horarios de la sede actualizados.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'day' => 'required|integer|between:0,6',
            'repeat_days' => 'nullable|array',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        // Combinamos los días
        $daysToRegister = collect($request->input('repeat_days', []))
            ->push($request->day)
            ->unique();

        // 🔍 1. VALIDACIÓN PREVIA (Fuera del bucle)
        // Buscamos si alguno de los días seleccionados tiene conflicto
        foreach ($daysToRegister as $day) {
            $overlap = Schedule::where('address_id', $request->address_id)
                ->where('day', $day)
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        // Choca el inicio
                        $q->where('start_time', '<', $request->end_time)
                        ->where('end_time', '>', $request->start_time);
                    });
                })->exists();

            if ($overlap) {
                $nombresDias = [0=>'Dom', 1=>'Lun', 2=>'Mar', 3=>'Mie', 4=>'Jue', 5=>'Vie', 6=>'Sab'];
                return back()->with('error', "Conflicto: El horario para el día {$nombresDias[$day]} ya está ocupado en ese rango.")->withInput();
            }
        }

        // 💾 2. GUARDADO (Solo si no hubo errores arriba)
        foreach ($daysToRegister as $day) {
            Schedule::create([
                'address_id' => $request->address_id,
                'day' => $day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);
        }

        return back()->with('success', '¡Horarios agregados correctamente!');
    }
    
    public function destroy(Schedule $schedule)
    {
        // Seguridad: verificar que el horario pertenece a una sede del doctor logueado
        if ($schedule->address->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        // 1. Buscamos si hay citas agendadas en esta franja horaria
        // Filtramos por sede, día de la semana y que la hora esté dentro del rango
        $conflicts = \App\Models\Appointment::where('address_id', $schedule->address_id)
            ->where('doctor_id', auth()->user()->doctor->id)
            ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day + 1]) // +1 porque MySQL usa 1=Dom
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function($query) use ($schedule) {
                $query->whereTime('start_time', '>=', $schedule->start_time)
                    ->whereTime('start_time', '<', $schedule->end_time);
            })
            ->with('patient.user')
            ->get();

        // 2. Si hay conflictos, enviamos la lista a la vista y no borramos
        if ($conflicts->count() > 0) {
            return back()->with([
                'schedule_conflicts' => $conflicts,
                'error' => 'No puedes eliminar este horario porque hay citas agendadas.'
            ]);
        }

        $schedule->delete();

        return back()->with('success', 'Franja de horario eliminada correctamente.');
    }

}
