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
     * Obtiene el modelo del perfil propietario legal según el espacio de trabajo activo en sesión.
     * 🛡️ GOBERNADO POR CONTEXTO: Inmune a fallos de roles cruzados en opendoctor.online
     */
    private function getOwner()
    {
        $user = Auth::user();
        $context = session('doctor_context');
        
        // Caso A: Si el contexto activo de la sesión es de una Clínica Corporativa Aliada
        if (isset($context['type']) && $context['type'] === 'clinic') {
            return Clinic::with(['plan', 'addresses'])->find($context['id']);
        }
        
        // Caso B: Si el usuario autenticado es el administrador de una clínica pura
        if ($user->role === 'clinic') {
            return $user->clinic()->with(['plan', 'addresses'])->first();
        }
        
        // Caso C: Contexto Consultorio Particular Autónomo (Por defecto del Médico)
        return $user->doctor()->with(['plan', 'addresses'])->first();
    }

    /**
     * Filtro estricto de seguridad multi-inquilino gobernado por el contexto de co-propiedad.
     */
    private function authorizeAddressOwner(Address $address)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        // 1. Si opera bajo el entorno institucional de una Clínica (Como Administrador o Médico Invitado)
        if ($user->role === 'clinic' || (isset($context['type']) && $context['type'] === 'clinic')) {
            $clinicId = ($user->role === 'clinic') ? $user->clinic->id : $context['id'];
            
            if ((int)$address->clinic_id !== (int)$clinicId) {
                abort(403, 'No posees privilegios de acceso sobre esta sede institucional.');
            }

            // Si es médico invitado, validamos que su vinculación esté aprobada en la nómina corporativa
            if ($user->role === 'doctor') {
                $isLinked = DB::table('clinic_doctor')
                    ->where('clinic_id', $clinicId)
                    ->where('doctor_id', $user->doctor->id)
                    ->where('status', 'approved')
                    ->exists();

                if (!$isLinked) {
                    abort(403, 'No posees permisos de gestión vigentes en la nómina de esta clínica.');
                }
            }
            return; 
        }

        // 2. Si opera bajo el entorno de Consultorio Particular Autónomo (Médico Independiente)
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'particular') {
            if ((int)$address->doctor_id !== (int)$user->doctor->id || !is_null($address->clinic_id)) {
                abort(403, 'No posees privilegios de administración sobre esta sede privada.');
            }
        }
    }
    /**
     * Muestra la lista de horarios y turnos de la sede de forma analítica y adaptativa.
     */
    public function index(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $user = Auth::user();
        $context = session('doctor_context');
        $owner = $this->getOwner();

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial o institucional propietario no encontrado.');
        }

        // Mapeo unificado para control de la interfaz (1=Lunes, 7=Domingo)
        $daysMap = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        ];

        // GOBERNADO POR CONTEXTO: Evaluamos estrictamente el espacio de trabajo activo en sesión
        $isReadOnly = (isset($context['type']) && $context['type'] === 'clinic');

        // ESCENARIO A: Contexto de Clínica Corporativa (Para el Administrador o el Médico Invitado)
        if ($isReadOnly || $user->role === 'clinic') {
            
            if ($user->role === 'clinic') {
                // Si es la clínica pura administrando, consulta todos los horarios de la sede física
                $schedules = Schedule::where('address_id', $address->id)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->orderBy('start_time')
                    ->get();
                
                $doctorIds = $owner->doctors()->wherePivot('status', 'approved')->pluck('doctors.id')->toArray();
                $availableDoctors = $owner->doctors()->wherePivot('status', 'approved')->with('user')->get();
                $isReadOnly = false; // El rol clinic siempre tiene permisos de escritura
            } else {
                // Si es un Médico Invitado bajo el contexto de clínica, ve ÚNICAMENTE sus propios turnos institucionales
                $schedules = Schedule::where('address_id', $address->id)
                    ->where('doctor_id', $user->doctor->id)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->orderBy('start_time')
                    ->get();

                $doctorIds = [$user->doctor->id];
                $availableDoctors = collect([$user->doctor]);
            }

            // Cargar ausencias vigentes de la nómina involucrada
            $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
                ->where('end_date', '>=', now()->toDateString())
                ->with('doctor.user')
                ->orderBy('start_date', 'asc')
                ->get();
            
            $addresses = $owner->addresses()->where('status', true)->get();
        } 
        // ESCENARIO B: Contexto de Consultorio Particular Autónomo (Escritura Habilitada)
        else {
            $doctor = $user->doctor;

            $schedules = Schedule::where('address_id', $address->id)
                ->with('doctor.user')
                ->orderBy('day')
                ->orderBy('start_time')
                ->get();

            $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('start_date', 'asc')
                ->get();
                
            $availableDoctors = collect([$doctor]);
            $addresses = Address::where('doctor_id', $doctor->id)->whereNull('clinic_id')->where('status', true)->get();
        }

        // Agrupar colecciones por día para la inyección limpia del Blade universal
        $schedulesByDay = $schedules->groupBy('day');

        return view('partner.schedules.index', compact(
            'address', 
            'schedules', 
            'schedulesByDay',
            'unavailabilities', 
            'availableDoctors', 
            'addresses',
            'isReadOnly',
            'daysMap'
        ));
    }

    /**
     * Muestra el formulario para modificar franjas horarias por lotes.
     */
    public function edit(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $context = session('doctor_context');

        // SEGURIDAD POR CONTEXTO: Bloquear la edición si opera bajo el entorno institucional
        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada: Los horarios institucionales solo son modificables por la administración central.');
        }

        $schedules = $address->schedules()->with('doctor.user')->orderBy('day')->get();
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

        // 🔒 BLINDAJE POR CONTEXTO: Denegar mutaciones si el médico opera dentro del entorno clínico
        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada. No posees permisos de escritura sobre la infraestructura institucional.');
        }

        $request->validate([
            'schedules.*.start_time' => ['required'],
            'schedules.*.end_time'   => ['required', 'after:schedules.*.start_time'],
        ], [
            'schedules.*.end_time.after' => 'La hora de finalización debe ser estrictamente posterior a la hora de inicio.',
        ]);

        foreach ($request->schedules as $id => $data) {
            $schedule = Schedule::where('id', $id)->where('address_id', $address->id)->first();

            if (!$schedule) {
                continue;
            }

            if (!isset($data['is_active'])) {
                // Chequear conflictos de citas activas antes de borrar el bloque en lote (1=Lun, 7=Dom)
                $hasAppointments = Appointment::where('address_id', $address->id)
                    ->where('doctor_id', $schedule->doctor_id)
                    ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day == 7 ? 1 : $schedule->day + 1])
                    ->where('date', '>=', now()->toDateString())
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->exists();

                if (!$hasAppointments) {
                    $schedule->delete();
                }
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
     * Registra franjas horarias controlando la disponibilidad física de la sede y el contexto activo.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');
        $owner = $this->getOwner();

        // 🔒 BLINDAJE POR CONTEXTO: Bloquear la inserción si el médico está operando en el entorno institucional
        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Operación no permitida: Los horarios institucionales son gestionados exclusivamente por la clínica.');
        }

        // Validaciones base de datos (Indexación robusta 1=Lun, 7=Dom unificada)
        $rules = [
            'address_id'     => ['required', 'exists:addresses,id,deleted_at,NULL'],
            'day'            => ['required', 'integer', 'between:1,7'],
            'replicate_days' => ['nullable', 'array'], // Mapeado contra el array real del Blade
            'start_time'     => ['required'],
            'end_time'       => ['required', 'after:start_time'],
        ];

        if ($user->role === 'clinic') {
            $rules['doctor_id'] = [
                'required',
                'exists:doctors,id',
                // Sella validación de nómina de forma contextual sobre la clínica actual
                function ($attribute, $value, $fail) use ($owner) {
                    $isStaff = $owner->doctors()
                        ->where('doctors.id', $value)
                        ->where('clinic_doctor.status', 'approved')
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

        $targetDoctorId = ($user->role === 'clinic') ? $request->doctor_id : $user->doctor->id;

        // Combinamos el día base con los días repetidos seleccionados por lotes (replicate_days)
        $daysToRegister = collect($request->input('replicate_days', []))
            ->push($request->day)
            ->unique();

        // 🔍 VALIDACIÓN DE SUPERPOSICIÓN HORARIA GLOBAL E HÍBRIDA ADAPTADA A TELEMEDICINA
        foreach ($daysToRegister as $day) {
            $overlap = Schedule::where('day', $day)
                ->where(function ($query) use ($request, $targetDoctorId, $address) {
                    $query->where(function ($q) use ($request, $address) {
                        // Conflicto 1: Choque físico en el mismo consultorio exacto de esta sede
                        $q->where('address_id', $address->id)
                          ->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request, $targetDoctorId, $address) {
                        // Conflicto 2 Híbrido: El médico está en otra sede a la misma hora, 
                        // pero solo choca si ambas ubicaciones operan en el mundo físico.
                        $q->where('doctor_id', $targetDoctorId)
                          ->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time)
                          ->whereHas('address', function($subQ) use ($address) {
                              if ($address->type === 'physical') {
                                  $subQ->where('type', 'physical');
                              } else {
                                  $subQ->where('type', 'virtual');
                              }
                          });
                    });
                })->exists();

            if ($overlap) {
                $dayNames = [1=>'Lun', 2=>'Mar', 3=>'Mie', 4=>'Jue', 5=>'Vie', 6=>'Sab', 7=>'Dom'];
                return back()->with('error', "Conflicto de Agenda: El rango seleccionado para el día {$dayNames[$day]} colisiona con un turno existente que comparte el mismo entorno físico o virtual.")->withInput();
            }
        }

        // PERSISTENCIA SEGURA MULTI-TENANT
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
     * Remueve franjas horarias de forma segura evaluando que no altere citas activas.
     */
    public function destroy(Schedule $schedule)
    {
        $this->authorizeAddressOwner($schedule->address);
        $user = Auth::user();
        $context = session('doctor_context');

        // 🔒 BLINDAJE POR CONTEXTO: Denegar eliminación si el médico se encuentra operando en el entorno institucional
        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada: No posees permisos administrativos para alterar turnos de la infraestructura institucional.');
        }

        // 1. Validar colisiones contra citas transaccionadas activas de pacientes reales (1=Lun, 7=Dom)
        $conflicts = Appointment::where('address_id', $schedule->address_id)
            ->where('doctor_id', $schedule->doctor_id) 
            ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day == 7 ? 1 : $schedule->day + 1]) 
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function($query) use ($schedule) {
                $query->whereTime('start_time', '>=', $schedule->start_time)
                      ->whereTime('start_time', '<', $schedule->end_time);
            })
            ->with('patient.user')
            ->get();

        if ($conflicts->count() > 0) {
            return back()->with([
                'schedule_conflicts' => $conflicts,
                'error'              => 'Operación cancelada: No se puede eliminar esta franja porque cuenta con citas de pacientes agendadas de forma activa.'
            ]);
        }

        $schedule->delete();

        return back()->with('success', 'Franja de horario removida correctamente.');
    }
} // Cierre definitivo de la clase ScheduleController
