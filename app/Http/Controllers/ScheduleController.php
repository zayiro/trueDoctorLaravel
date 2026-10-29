<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Repositories\AvailabilityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Scheduling\SlotOverlapChecker;

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
        
        if (isset($context['type']) && $context['type'] === 'clinic') {
            return Clinic::with(['plan', 'addresses'])->find($context['id']);
        }
        
        if ($user->role === 'clinic') {
            return $user->clinic()->with(['plan', 'addresses'])->first();
        }
        
        return $user->doctor()->with(['plan', 'addresses'])->first();
    }

    /**
     * Filtro estricto de seguridad multi-inquilino gobernado por el contexto de co-propiedad.
     */
    private function authorizeAddressOwner(Address $address)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if ($user->role === 'clinic' || (isset($context['type']) && $context['type'] === 'clinic')) {
            $clinicId = ($user->role === 'clinic') ? $user->clinic->id : $context['id'];
            
            if ((int)$address->clinic_id !== (int)$clinicId) {
                abort(403, 'No posees privilegios de acceso sobre esta sede institucional.');
            }

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

        $daysMap = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        ];

        $isReadOnly = (isset($context['type']) && $context['type'] === 'clinic');

        // ESCENARIO A: Contexto de Clínica Corporativa
        if ($isReadOnly || $user->role === 'clinic') {
            
            if ($user->role === 'clinic') {
                // 🔒 CORRECCIÓN: Solo horarios activos
                $schedules = Schedule::where('address_id', $address->id)
                    ->where('is_active', true)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->orderBy('start_time')
                    ->get();
                
                $doctorIds = $owner->doctors()->wherePivot('status', 'approved')->pluck('doctors.id')->toArray();
                $availableDoctors = $owner->doctors()->wherePivot('status', 'approved')->with('user')->get();
                $isReadOnly = false;
            } else {
                // 🔒 CORRECCIÓN: Solo horarios activos del doctor invitado
                $schedules = Schedule::where('address_id', $address->id)
                    ->where('is_active', true)
                    ->where('doctor_id', $user->doctor->id)
                    ->with('doctor.user')
                    ->orderBy('day')
                    ->orderBy('start_time')
                    ->get();

                $doctorIds = [$user->doctor->id];
                $availableDoctors = collect([$user->doctor]);
            }

            $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
                ->where('end_date', '>=', now()->toDateString())
                ->with('doctor.user')
                ->orderBy('start_date', 'asc')
                ->get();
            
            $addresses = $owner->addresses()->where('status', true)->get();
        } 
        // ESCENARIO B: Contexto de Consultorio Particular Autónomo
        else {
            $doctor = $user->doctor;

            // 🔒 CORRECCIÓN: Solo horarios activos
            $schedules = Schedule::where('address_id', $address->id)
                ->where('is_active', true)
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

        $schedulesByDay = $schedules->groupBy('day');

        // 🔒 CORRECCIÓN CRÍTICA: Pasar $currentContext para la vista de addresses
        $currentContext = $context ?? [
            'type'  => 'particular',
            'id'    => null,
            'name'  => 'Consultorio Particular',
            'photo' => null,
        ];

        return view('partner.schedules.index', compact(
            'address', 
            'schedules', 
            'schedulesByDay',
            'unavailabilities', 
            'availableDoctors', 
            'addresses',
            'isReadOnly',
            'daysMap',
            'currentContext'
        ));
    }

    /**
     * Muestra el formulario para modificar franjas horarias por lotes.
     */
    public function edit(Address $address)
    {
        $this->authorizeAddressOwner($address);
        $context = session('doctor_context');

        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada: Los horarios institucionales solo son modificables por la administración central.');
        }

        $schedules = $address->schedules()->with('doctor.user')->orderBy('day')->get();
        return view('partner.schedules.edit', compact('address', 'schedules'));
    }

    /**
     * Actualiza bloques horarios existentes en lote de forma segura.
     */
    public function update(Request $request, Address $address, AvailabilityRepository $cacheRepo)
    {
        $this->authorizeAddressOwner($address);
        $context = session('doctor_context');

        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada. No posees permisos de escritura sobre la infraestructura institucional.');
        }

        $request->validate([
            'schedules.*.start_time' => ['required'],
            'schedules.*.end_time'   => ['required', 'after:schedules.*.start_time'],
        ], [
            'schedules.*.end_time.after' => 'La hora de finalización debe ser estrictamente posterior a la hora de inicio.',
        ]);

        $doctorIdsToInvalidate = [];

        foreach ($request->schedules as $id => $data) {
            $schedule = Schedule::where('id', $id)->where('address_id', $address->id)->first();

            if (!$schedule) {
                continue;
            }

            $doctorIdsToInvalidate[] = $schedule->doctor_id;

            if (!isset($data['is_active'])) {
                $hasAppointments = Appointment::where('address_id', $address->id)
                    ->where('doctor_id', $schedule->doctor_id)
                    ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day == 7 ? 1 : $schedule->day + 1])
                    ->where('date', '>=', now()->toDateString())
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->where(function($query) use ($schedule) {
                        $query->where('start_time', '<', $schedule->end_time)
                              ->where('end_time', '>', $schedule->start_time);
                    })
                    ->exists();

                if (!$hasAppointments) {
                    $schedule->update(['is_active' => false]);
                } else {
                    return back()->withErrors([
                        'schedule_conflict' => "No se puede desactivar este bloque: existen citas de pacientes agendadas en este rango horario."
                    ])->withInput();
                }
            } else {
                if ($this->hasTimeConflict(
                    $schedule->doctor_id,
                    $schedule->day,
                    $data['start_time'],
                    $data['end_time'],
                    $schedule->clinic_id,
                    $schedule->id
                )) {
                    return back()->withErrors([
                        'schedule_conflict' => "No se puede actualizar este bloque: el nuevo rango horario se solapa con otro bloque existente del especialista."
                    ])->withInput();
                }

                $schedule->update([
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                ]);
            }
        }

        if (!empty($doctorIdsToInvalidate)) {
            $cacheRepo->invalidateAvailabilityCache(
                $address->clinic_id, 
                array_unique($doctorIdsToInvalidate), 
                $address->id
            );
        }

        return redirect()->route('partner.schedules.index', $address->id)
            ->with('success', 'Los horarios y turnos de la sede han sido actualizados en lote.');
    }

    /**
     * 🆕 Valida que NO exista solapamiento de horarios para un doctor en un día específico.
     * Considera TODOS los contextos: particular + todas las clínicas donde trabaja.
     * 
     * Delegado al SlotOverlapChecker como única fuente de verdad.
     */
    private function hasTimeConflict(
        int $doctorId,
        int $day,
        string $startTime,
        string $endTime,
        ?int $clinicId = null,
        ?int $excludeScheduleId = null
    ): bool
    {
        return SlotOverlapChecker::overlapsWithSchedules(
            doctorId: $doctorId,
            dayOfWeek: $day,
            newStart: $startTime,
            newEnd: $endTime,
            excludeScheduleId: $excludeScheduleId
        );
    }

    /**
     * Registra franjas horarias controlando la disponibilidad física de la sede y el contexto activo.
     * Acepta DOS formatos:
     * - Formato A (index.blade.php): day + start_time + end_time + replicate_days[] (strings)
     * - Formato B (form.blade.php): active_days[] + start_time[day] + end_time[day] (arrays)
     */
    public function store(Request $request, AvailabilityRepository $cacheRepo)
    {
        $user = Auth::user();
        $context = session('doctor_context');
        $owner = $this->getOwner();

        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Operación no permitida: Los horarios institucionales son gestionados exclusivamente por la clínica.');
        }

        // 🔍 DETECTAR QUÉ FORMATO VIENE DEL FORMULARIO
        $isBatchFormat = $request->has('active_days'); // Formato B: arrays
        $isSingleFormat = $request->has('day'); // Formato A: strings

        if ($isBatchFormat) {
            // Formato B: form.blade.php (editar en lote)
            $request->validate([
                'address_id'    => ['required', 'exists:addresses,id,deleted_at,NULL'],
                'active_days'   => ['required', 'array', 'min:1'],
                'active_days.*' => ['integer', 'between:1,7'],
                'start_time'    => ['required', 'array'],
                'end_time'      => ['required', 'array'],
            ], [
                'active_days.required' => 'Debes seleccionar al menos un día de la semana.',
                'active_days.min'      => 'Debes seleccionar al menos un día de la semana.',
            ]);
        } elseif ($isSingleFormat) {
            // Formato A: index.blade.php (agregar turno individual)
            $request->validate([
                'address_id'     => ['required', 'exists:addresses,id,deleted_at,NULL'],
                'day'            => ['required', 'integer', 'between:1,7'],
                'replicate_days' => ['nullable', 'array'],
                'replicate_days.*' => ['integer', 'between:1,7'],
                'start_time'     => ['required', 'date_format:H:i'],
                'end_time'       => ['required', 'date_format:H:i', 'after:start_time'],
            ], [
                'end_time.after' => 'La hora de finalización debe ser estrictamente posterior a la hora de inicio.',
                'start_time.date_format' => 'El formato de hora de inicio debe ser HH:MM.',
                'end_time.date_format' => 'El formato de hora de finalización debe ser HH:MM.',
            ]);
        } else {
            return back()->withErrors(['error' => 'Formato de formulario no reconocido.'])->withInput();
        }

        if ($user->role === 'clinic') {
            $request->validate([
                'doctor_id' => [
                    'required',
                    'exists:doctors,id',
                    function ($attribute, $value, $fail) use ($owner) {
                        $isStaff = $owner->doctors()
                            ->where('doctors.id', $value)
                            ->where('clinic_doctor.status', 'approved')
                            ->exists();
                        if (!$isStaff) {
                            $fail('El especialista seleccionado no se encuentra activo en tu nómina institucional.');
                        }
                    }
                ]
            ]);
        }

        $address = Address::findOrFail($request->address_id);
        $this->authorizeAddressOwner($address);

        $targetDoctorId = ($user->role === 'clinic') ? $request->doctor_id : $user->doctor->id;
        $targetClinicId = ($user->role === 'clinic') ? $user->clinic->id : null;

        //  NORMALIZAR DÍAS A REGISTRAR (ambos formatos terminan en un array de días)
        if ($isBatchFormat) {
            $daysToRegister = collect($request->input('active_days', []))
                ->map(fn ($day) => ((int) $day === 0) ? 7 : (int) $day) // Mapear Domingo 0 → 7
                ->unique()
                ->values();
        } else {
            // Formato A: day base + replicate_days
            $daysToRegister = collect($request->input('replicate_days', []))
                ->push($request->day)
                ->unique()
                ->values();
        }

        $dayNames = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];

        // 🔍 VALIDACIÓN ANTI-SOLAPAMIENTO Y COHERENCIA TEMPORAL POR DÍA
        foreach ($daysToRegister as $day) {
            // Obtener horas según el formato
            if ($isBatchFormat) {
                $startTime = $request->input("start_time.{$day}");
                $endTime = $request->input("end_time.{$day}");
            } else {
                $startTime = $request->start_time; // Mismo valor para todos los días
                $endTime = $request->end_time;
            }

            // Validar que existan los tiempos
            if (!$startTime || !$endTime) {
                return back()
                    ->withErrors([
                        'schedule_conflict' => "El día {$dayNames[$day]} no tiene horas de inicio o fin definidas."
                    ])
                    ->withInput();
            }

            // Validar formato de hora (acepta HH:MM o HH:MM:SS)
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
                return back()
                    ->withErrors([
                        'schedule_conflict' => "El formato de hora para {$dayNames[$day]} es inválido. Use HH:MM."
                    ])
                    ->withInput();
            }

            // Normalizar a HH:MM:SS
            $startTimeFormatted = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endTimeFormatted = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

            // Validar start_time < end_time
            if ($startTimeFormatted >= $endTimeFormatted) {
                return back()
                    ->withErrors([
                        'schedule_conflict' => "Conflicto en {$dayNames[$day]}: La hora de inicio ({$startTime}) debe ser anterior a la hora de fin ({$endTime})."
                    ])
                    ->withInput();
            }

            // Validar solapamiento global del doctor
            if (SlotOverlapChecker::overlapsWithSchedules(
                doctorId: $targetDoctorId,
                dayOfWeek: $day,
                newStart: $startTimeFormatted,
                newEnd: $endTimeFormatted
            )) {
                return back()
                    ->withErrors([
                        'schedule_conflict' => "Conflicto de Agenda: El rango seleccionado para {$dayNames[$day]} ({$startTime} - {$endTime}) se solapa con un bloque horario existente del especialista."
                    ])
                    ->withInput();
            }

            // Validar duplicados exactos
            $exactDuplicate = Schedule::where('doctor_id', $targetDoctorId)
                ->where('address_id', $address->id)
                ->where('day', $day)
                ->where('start_time', $startTimeFormatted)
                ->where('end_time', $endTimeFormatted)
                ->where('is_active', true)
                ->exists();

            if ($exactDuplicate) {
                return back()
                    ->withErrors([
                        'schedule_conflict' => "El horario para {$dayNames[$day]} ({$startTime} - {$endTime}) ya existe en esta sede."
                    ])
                    ->withInput();
            }
        }

        // PERSISTENCIA SEGURA MULTI-TENANT
        $createdCount = 0;
        foreach ($daysToRegister as $day) {
            if ($isBatchFormat) {
                $startTime = $request->input("start_time.{$day}");
                $endTime = $request->input("end_time.{$day}");
            } else {
                $startTime = $request->start_time;
                $endTime = $request->end_time;
            }

            $startTimeFormatted = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endTimeFormatted = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

            Schedule::create([
                'address_id' => $address->id,
                'doctor_id'  => $targetDoctorId,
                'clinic_id'  => $targetClinicId,
                'day'        => $day,
                'start_time' => $startTimeFormatted,
                'end_time'   => $endTimeFormatted,
                'is_active'  => true,
            ]);
            $createdCount++;
        }

        // 🔥 INVALIDAR CACHÉ
        $cacheRepo->invalidateAvailabilityCache(
            $targetClinicId,
            [$targetDoctorId],
            $address->id
        );

        return back()->with('success', "¡{$createdCount} bloque(s) de agenda agregado(s) correctamente!");
    }

    /**
     * Remueve franjas horarias de forma segura evaluando que no altere citas activas.
     */
    public function destroy(Schedule $schedule, AvailabilityRepository $cacheRepo)
    {
        $this->authorizeAddressOwner($schedule->address);
        $context = session('doctor_context');

        if (isset($context['type']) && $context['type'] === 'clinic') {
            abort(403, 'Acción denegada: No posees permisos administrativos para alterar turnos de la infraestructura institucional.');
        }

        $conflicts = Appointment::where('address_id', $schedule->address_id)
            ->where('doctor_id', $schedule->doctor_id) 
            ->whereRaw('DAYOFWEEK(date) = ?', [$schedule->day == 7 ? 1 : $schedule->day + 1]) 
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function($query) use ($schedule) {
                $query->where('start_time', '<', $schedule->end_time)
                      ->where('end_time', '>', $schedule->start_time);
            })
            ->with('patient.user')
            ->get();

        if ($conflicts->count() > 0) {
            return back()->with([
                'schedule_conflicts' => $conflicts,
                'error'              => 'Operación cancelada: No se puede eliminar esta franja porque cuenta con citas de pacientes agendadas de forma activa.'
            ]);
        }

        $schedule->update(['is_active' => false]);

        $cacheRepo->invalidateAvailabilityCache(
            $schedule->clinic_id, 
            [$schedule->doctor_id], 
            $schedule->address_id
        );

        return back()->with('success', 'Franja de horario removida correctamente.');
    }
}