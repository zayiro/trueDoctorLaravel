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

        $daysToRegister = collect($request->input('repeat_days', []))
            ->push($request->day)
            ->unique();

        // 📍 AQUÍ VA LA LÓGICA DE BORRADO
        // Esto evita que si ya había un horario el lunes, se duplique con el nuevo
        Schedule::where('address_id', $request->address_id)
                ->whereIn('day', $daysToRegister)
                ->delete();

        // Luego procedes a crear los nuevos
        foreach ($daysToRegister as $day) {
            Schedule::create([
                'address_id' => $request->address_id,
                'day' => $day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);
        }

        return back()->with('success', '¡Horarios actualizados correctamente!');
    }

    public function destroy(Schedule $schedule)
    {
        // Seguridad: verificar que el horario pertenece a una sede del doctor logueado
        if ($schedule->address->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $schedule->delete();

        return back()->with('success', 'Franja horaria eliminada.');
    }
}
