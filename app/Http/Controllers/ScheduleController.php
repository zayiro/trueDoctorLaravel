<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{    
    /**
     * Filtro estricto de seguridad multi-inquilino corporativo.
     */
    private function authorizeAddressOwner(Address $address)
    {
        $user = Auth::user();

        if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
            abort(403, 'No posees permisos de gestión sobre esta sede institucional.');
        }

        if ($user->role === 'doctor' && $address->doctor_id !== $user->doctor->id) {
            abort(403, 'No posees permisos de gestión sobre esta sede privada.');
        }
    }

    /**
     * Muestra la lista de horarios y turnos de la sede.
     */
    public function index(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $user = Auth::user();

        // Si es una clínica, cargamos los horarios de todos los doctores asignados a esta sede
        // Si es doctor, solo los horarios generales
        $schedules = Schedule::where('address_id', $address->id)
            ->with('doctor.user')
            ->orderBy('day')
            ->get();
        
        // Carga de ausencias/bloqueos según el contexto
        if ($user->role === 'clinic') {
            // La clínica ve las ausencias de todos los doctores de su nómina actual
            $doctorIds = $address->clinic->doctors()->pluck('doctors.id')->toArray();
            $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
                ->where('end_date', '>=', now()->toDateString())
                ->with('doctor.user')
                ->orderBy('start_date', 'asc')
                ->get();
            
            // Pasamos los doctores vinculados para el formulario de asignación de turnos en la vista
            $availableDoctors = $address->clinic->doctors()->with('user')->get();
        } else {
            $doctor = $user->doctor;
            $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('start_date', 'asc')
                ->get();
            $availableDoctors = collect([$doctor]); // Solo él mismo
        }

        return view('partner.schedules.index', compact('address', 'schedules', 'unavailabilities', 'availableDoctors'));
    }

    public function edit(Address $address)
    {
        $this->authorizeAddressOwner($address);
        
        $schedules = $address->schedules()->with('doctor.user')->orderBy('day')->get();
        
        return view('partner.schedules.edit', compact('address', 'schedules'));
    }

    /**
     * Actualiza bloques horarios existentes en lote.
     */
    public function update(Request $request, Address $address)
    {
        $this->authorizeAddressOwner($address);

        $request->validate([
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time'   => 'required',
        ]);

        foreach ($request->schedules as $id => $data) {
            Schedule::where('id', $id)
                ->where('address_id', $address->id) // Blindaje de seguridad cruzado
                ->update([
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                    'is_active'  => isset($data['is_active']),
                ]);
        }

        return redirect()->route('partner.addresses.index')
            ->with('success', 'Turnos y horarios de la sede actualizados correctamente.');
    }

    /**
     * Registra franjas horarias controlando la disponibilidad física de la sede.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Validaciones base
        $rules = [
            'address_id'   => 'required|exists:addresses,id,deleted_at,NULL',
            'day'          => 'required|integer|between:0,6',
            'repeat_days'  => 'nullable|array',
            'start_time'   => 'required',
            'end_time'     => 'required|after:start_time',
        ];

        // Si opera una clínica, el formulario debe exigir a qué doctor se le asigna el turno
        if ($user->role === 'clinic') {
            $rules['doctor_id'] = 'required|exists:doctors,id';
        }

        $request->validate($rules);

        $address = Address::findOrFail($request->address_id);
        $this->authorizeAddressOwner($address);

        // Definimos el ID del doctor asignado
        $targetDoctorId = $user->role === 'clinic' ? $request->doctor_id : $user->doctor->id;

        // Combinamos el día base con los días repetidos
        $daysToRegister = collect($request->input('repeat_days', []))
            ->push($request->day)
            ->unique();

        // 🔍 2. VALIDACIÓN DE SUPERPOSICIÓN (FÍSICA E INDIVIDUAL)
        foreach ($daysToRegister as $day) {
            $overlap = Schedule::where('address_id', $address->id)
                ->where('day', $day)
                ->where(function ($query) use ($request, $targetDoctorId) {
                    $query->where(function ($q) use ($request) {
                        // Conflicto 1: Superposición horaria física en el consultorio
                        $q->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request, $targetDoctorId) {
                        // Conflicto 2: El mismo médico no puede tener agenda en otro consultorio a la misma hora
                        $q->where('doctor_id', $targetDoctorId)
                          ->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                    });
                })->exists();

            if ($overlap) {
                $nombresDias = [0=>'Dom', 1=>'Lun', 2=>'Mar', 3=>'Mie', 4=>'Jue', 5=>'Vie', 6=>'Sab'];
                return back()->with('error', "Conflicto de Agenda: El rango seleccionado para el día {$nombresDias[$day]} colisiona con un turno existente en esta sede o en la agenda del especialista.")->withInput();
            }
        }

        // 💾 3. PERSISTENCIA SEGURA
        foreach ($daysToRegister as $day) {
            Schedule::create([
                'address_id' => $address->id,
                'doctor_id'  => $targetDoctorId, // 🔥 ALTA SAAS: El horario ahora sabe de quién es
                'day'        => $day,
                'start_time' => $request->start_time,
                'end_time'   => $request->end_time,
            ]);
        }

        return back()->with('success', '¡Turnos de agenda agregados correctamente!');
    }
    
    /**
     * Remueve franjas horarias evaluando que no altere citas activas.
     */
    public function destroy(Schedule $schedule)
    {
        $this->authorizeAddressOwner($schedule->address);

        // 1. Buscamos si hay citas agendadas asignadas a este doctor específico en esta franja
        $conflicts = Appointment::where('address_id', $schedule->address_id)
            ->where('doctor_id', $schedule->doctor_id) // 🔥 Filtrado exacto por médico dueño del turno
            ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day + 1]) 
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function($query) use ($schedule) {
                $query->whereTime('start_time', '>=', $schedule->start_time)
                      ->whereTime('start_time', '<', $schedule->end_time);
            })
            ->with('patient.user')
            ->get();

        // 2. Si hay colisiones, bloqueamos la eliminación
        if ($conflicts->count() > 0) {
            return back()->with([
                'schedule_conflicts' => $conflicts,
                'error'              => 'Operación cancelada: No se puede eliminar esta franja porque cuenta con citas de pacientes agendadas de forma activa.'
            ]);
        }

        $schedule->delete();

        return back()->with('success', 'Franja de horario removida correctamente.');
    }
}
