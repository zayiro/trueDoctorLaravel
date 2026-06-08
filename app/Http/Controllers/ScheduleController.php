<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{    
        /**
     * Filtro estricto de seguridad multi-inquilino corporativo y co-propiedad del SaaS.
     */
    private function authorizeAddressOwner(Address $address)
    {
        $user = Auth::user();

        // 1. CONTROL PARA CLÍNICAS PURAS (Producción)
        if ($user->role === 'clinic') {
            if ((int)$address->clinic_id !== (int)$user->clinic->id) {
                abort(403, 'No posees privilegios administrativos sobre esta sede institucional.');
            }
            return; // Autorizado
        }

        // 2. CONTROL PARA DOCTORES (Híbridos o Particulares)
        if ($user->role === 'doctor') {
            
            // ESCENARIO A: La sede pertenece a una Clínica (Sede Institucional)
            if (!is_null($address->clinic_id)) {
                // Verificamos de forma infalible si el médico pertenece a la nómina aprobada de esta clínica
                $isLinked = DB::table('clinic_doctor')
                    ->where('clinic_id', $address->clinic_id)
                    ->where('doctor_id', $user->doctor->id)
                    ->where('status', 'approved')
                    ->exists();

                if (!$isLinked) {
                    abort(403, 'No posees permisos de gestión ni vinculación en la nómina de esta clínica institucional.');
                }
                
                return; // Autorizado para gestionar sus horarios en la clínica
            }

            // ESCENARIO B: La sede NO tiene clínica (Sede Privada de Producción)
            if ((int)$address->doctor_id !== (int)$user->doctor->id) {
                abort(403, 'No posees privilegios de administración sobre esta sede privada.');
            }
        }
    }


    /**
     * Muestra la lista de horarios y turnos de la sede de forma analítica.
     */
    public function index(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $user = Auth::user();
        $context = session('doctor_context');

        // ESCENARIO 1: El usuario autenticado es una Clínica Corporativa (Lógica de producción intacta)
        if ($user->role === 'clinic') {
            $schedules = Schedule::where('address_id', $address->id)
                ->with('doctor.user')
                ->orderBy('day')
                ->get();

            // La clínica consulta relacionalmente los doctores aprobados en su nómina actual
            $doctorIds = $address->clinic->doctors()->wherePivot('status', 'approved')->pluck('doctors.id')->toArray();
            
            $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
                ->where('end_date', '>=', now()->toDateString())
                ->with('doctor.user')
                ->orderBy('start_date', 'asc')
                ->get();
            
            $availableDoctors = $address->clinic->doctors()->wherePivot('status', 'approved')->with('user')->get();
        } 
        // ESCENARIO 2: El usuario es un Médico (Híbrido o Independiente)
        else {
            $doctor = $user->doctor;

            // Si está en contexto de clínica, filtramos ÚNICAMENTE sus propios horarios en esta sede corporativa
            if (($context['type'] ?? 'particular') === 'clinic') {
                $schedules = Schedule::where('address_id', $address->id)
                    ->where('doctor_id', $doctor->id)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->get();
            } else {
                // Modo Consultorio Particular: Ve todas las franjas de su propia sede privada (Lógica original)
                $schedules = Schedule::where('address_id', $address->id)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->get();
            }
            
            $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('start_date', 'asc')
                ->get();
                
            $availableDoctors = collect([$doctor]); // El especialista se gestiona de forma autónoma
        }

        return view('partner.schedules.index', compact('address', 'schedules', 'unavailabilities', 'availableDoctors'));
    }
    /**
     * Muestra el formulario para modificar franjas horarias por lotes.
     */
    public function edit(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $user = Auth::user();
        $context = session('doctor_context');

        // Si es médico operando en clínica, restringimos el formulario a sus propios turnos institucionales
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            $schedules = $address->schedules()
                ->where('doctor_id', $user->doctor->id)
                ->with('doctor.user')
                ->orderBy('day')
                ->get();
        } else {
            // Modo particular o Clínica administrando (Lógica original de producción)
            $schedules = $address->schedules()->with('doctor.user')->orderBy('day')->get();
        }
        
        return view('partner.schedules.edit', compact('address', 'schedules'));
    }

    /**
     * Actualiza bloques horarios existentes en lote de forma segura.
     */
    public function update(Request $request, Address $address)
    {
        $this->authorizeAddressOwner($address);
        $user = Auth::user();
        $context = session('doctor_context');

        $request->validate([
            'schedules.*.start_time' => ['required'],
            'schedules.*.end_time'   => ['required', 'after:schedules.*.start_time'],
        ], [
            'schedules.*.end_time.after' => 'La hora de finalización debe ser estrictamente posterior a la hora de inicio.',
        ]);

        foreach ($request->schedules as $id => $data) {
            // Filtro dinámico multi-tenant para evitar alteraciones cruzadas entre personal de la clínica
            $query = Schedule::where('id', $id)->where('address_id', $address->id);
            
            if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
                $query->where('doctor_id', $user->doctor->id);
            }

            $schedule = $query->first();

            if (!$schedule) {
                continue;
            }

            // Si el checkbox de activación no viaja en el request, el usuario deshabilitó el turno
            if (!isset($data['is_active'])) {
                $schedule->delete();
            } else {
                $schedule->update([
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                ]);
            }
        }

        return redirect()->route('partner.schedules.index', $address->id)
            ->with('success', 'Los horarios y turnos de la sede han sido actualizados en lote.');
    }
    /**
     * Registra franjas horarias controlando la disponibilidad física de la sede.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        // 1. Validaciones base de datos
        $rules = [
            'address_id'   => ['required', 'exists:addresses,id,deleted_at,NULL'],
            'day'          => ['required', 'integer', 'between:0,6'],
            'repeat_days'  => ['nullable', 'array'],
            'start_time'   => ['required'],
            'end_time'     => ['required', 'after:start_time'],
        ];

        // 🔒 BLINDAJE SAAS ADAPTADO: Si es clínica pura corporativa (Lógica original de producción)
        if ($user->role === 'clinic') {
            $rules['doctor_id'] = [
                'required',
                'exists:doctors,id',
                function ($attribute, $value, $fail) use ($user) {
                    $isStaff = DB::table('clinic_doctor')
                        ->where('clinic_id', $user->clinic->id)
                        ->where('doctor_id', $value)
                        ->where('status', 'approved')
                        ->exists();
                    if (!$isStaff) {
                        $fail('El especialista seleccionado no se encuentra activo en tu nómina institucional.');
                    }
                }
            ];
        }

        $request->validate($rules);

        $address = Address::findOrFail($request->address_id);
        $this->authorizeAddressOwner($address);

        // Establecer el doctor objetivo según el rol y contexto activo
        if ($user->role === 'clinic') {
            $targetDoctorId = $request->doctor_id;
        } else {
            // Si es médico (Particular o dentro de Clínica Aliada), solo puede agendarse a sí mismo
            $targetDoctorId = $user->doctor->id;
        }

        // Combinamos el día base con los días repetidos seleccionados
        $daysToRegister = collect($request->input('repeat_days', []))
            ->push($request->day)
            ->unique();

        // 🔍 2. VALIDACIÓN DE SUPERPOSICIÓN HORARIA
        foreach ($daysToRegister as $day) {
            $overlap = Schedule::where('address_id', $address->id)
                ->where('day', $day)
                ->where(function ($query) use ($request, $targetDoctorId) {
                    $query->where(function ($q) use ($request) {
                        // Conflicto 1: Choque físico en el mismo consultorio
                        $q->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request, $targetDoctorId) {
                        // Conflicto 2: El médico no puede estar en dos consultorios a la misma hora
                        $q->where('doctor_id', $targetDoctorId)
                          ->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                    });
                })->exists();

            if ($overlap) {
                $dayNames = [0=>'Dom', 1=>'Lun', 2=>'Mar', 3=>'Mie', 4=>'Jue', 5=>'Vie', 6=>'Sab'];
                return back()->with('error', "Conflicto de Agenda: El rango seleccionado para el día {$dayNames[$day]} colisiona con un turno existente en esta sede o en la agenda del especialista.")->withInput();
            }
        }

        // 💾 3. PERSISTENCIA SEGURA MULTI-TENANT
        foreach ($daysToRegister as $day) {
            Schedule::create([
                'address_id' => $address->id,
                'doctor_id'  => $targetDoctorId,
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
        $user = Auth::user();
        $context = session('doctor_context');

        // Escudo Multi-tenant: Si es médico en contexto de clínica, no puede borrar turnos de otros médicos
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            if ($schedule->doctor_id !== $user->doctor->id) {
                abort(403, 'No posees permisos para alterar franjas horarias de otros especialistas de la institución.');
            }
        }

        // 1. Buscamos si hay citas agendadas asignadas a este doctor en esta franja exacta
        $conflicts = Appointment::where('address_id', $schedule->address_id)
            ->where('doctor_id', $schedule->doctor_id) 
            ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day + 1]) 
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function($query) use ($schedule) {
                $query->whereTime('start_time', '>=', $schedule->start_time)
                      ->whereTime('start_time', '<', $schedule->end_time);
            })
            ->with('patient.user')
            ->get();

        // 2. Si hay colisiones vigentes de pacientes reales, se frena la eliminación
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
