<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Address;
use App\Services\AvailabilityService;
use App\Services\Scheduling\SlotOverlapChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UnavailabilityController extends Controller
{
    private AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Resuelve el doctor_id objetivo aplicando filtros estrictos de seguridad de Co-propiedad.
     * 
     *  CORRECCIÓN: Valida que el doctor esté 'approved' en la clínica.
     */
    private function resolveDoctorId(Request $request, $user): int
    {
        if ($user->role === 'clinic') {
            $request->validate(['doctor_id' => 'required|exists:doctors,id']);
            
            // 🔒 VALIDACIÓN: El doctor debe estar aprobado en la clínica
            $isLinked = $user->clinic->doctors()
                ->where('doctor_id', $request->doctor_id)
                ->where('clinic_doctor.status', 'approved')
                ->exists();
            
            if (!$isLinked) {
                abort(403, 'El especialista seleccionado no trabaja para esta clínica o no está aprobado.');
            }
            
            return (int) $request->doctor_id;
        }
        
        // 🔒 VALIDACIÓN: El usuario debe tener perfil médico
        if (!$user->doctor) {
            abort(403, 'Perfil médico no encontrado.');
        }
        
        return (int) $user->doctor->id;
    }

    /**
     * Despacha la vista principal de indisponibilidades inyectando las dependencias por rol o contexto activo.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');
        $addresses = collect();
        $clinicDoctors = collect();

        // ESCENARIO 1: El actor es una Clínica institucional pura
        if ($user->role === 'clinic') {
            $clinic = $user->clinic;
            $clinicDoctors = $clinic->doctors()
                ->where('clinic_doctor.status', 'approved')
                ->with('user')
                ->get();
            $addresses = Address::where('clinic_id', $clinic->id)
                ->where('status', true)
                ->get();
            $addressIds = $addresses->pluck('id')->toArray();
            
            $unavailabilities = Unavailability::whereIn('address_id', $addressIds)
                ->with(['doctor.user', 'address'])
                ->orderBy('start_date', 'asc')
                ->get();
        }
        // ESCENARIO 2: El actor es un Médico
        else {
            $doctor = $user->doctor;
            
            // Caso A: El médico está operando dentro del contexto de una Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinicId = (int)$context['id'];
                $addresses = Address::where('clinic_id', $clinicId)
                    ->where('status', true)
                    ->get();
                $addressIds = $addresses->pluck('id')->toArray();
                
                $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                    ->whereIn('address_id', $addressIds)
                    ->with(['doctor.user', 'address'])
                    ->orderBy('start_date', 'asc')
                    ->get();
            }
            // Caso B: El médico opera en su Consultorio Particular
            else {
                $addresses = Address::where('doctor_id', $doctor->id)
                    ->where('status', true)
                    ->get();
                
                $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                    ->with(['doctor.user', 'address'])
                    ->orderBy('start_date', 'asc')
                    ->get();
            }
        }

        $selectedDoctorId = $request->input('doctor_id');

        return view('partner.unavailabilities.index', compact(
            'unavailabilities',
            'addresses',
            'clinicDoctors',
            'selectedDoctorId'
        ));
    }

    /**
     * Valida la pertenencia y propiedad física de la sede si se envía en la petición.
     */
    private function checkAddressOwnership(?int $addressId, $user): void
    {
        if (!$addressId) {
            return; // Ausencia global (sin sede específica)
        }

        $address = Address::find($addressId);
        if (!$address) {
            abort(404, 'La sede seleccionada no existe.');
        }

        $context = session('doctor_context');

        if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
            abort(403, 'La sede seleccionada no pertenece a tu clínica institucional.');
        }

        if ($user->role === 'doctor') {
            if (($context['type'] ?? 'particular') === 'clinic') {
                if ((int)$address->clinic_id !== (int)$context['id']) {
                    abort(403, 'La sede seleccionada no corresponde a la clínica activa de tu espacio de trabajo.');
                }
                
                $isLinked = DB::table('clinic_doctor')
                    ->where('clinic_id', $address->clinic_id)
                    ->where('doctor_id', $user->doctor->id)
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$isLinked) {
                    abort(403, 'No tienes privilegios de staff autorizados para esta sede.');
                }
            } else {
                if ($address->doctor_id !== $user->doctor->id) {
                    abort(403, 'La sede seleccionada no te pertenece de forma privada.');
                }
            }
        }
    }

    /**
     * Almacena el bloqueo validando las fechas, horas exactas y choques contra la tabla appointments.
     * 
     *  CORRECCIONES:
     * - Usa DB::transaction() en lugar de beginTransaction/commit manual
     * - Usa SlotOverlapChecker para detección de conflictos
     * - Valida start_date <= end_date
     * - Valida consistencia entre start_time y end_time
     * - Valida ausencias existentes superpuestas
     * - Invalida caché de disponibilidad
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        try {
            return DB::transaction(function () use ($request, $user, $context) {
                $doctorId = $this->resolveDoctorId($request, $user);
                $this->checkAddressOwnership($request->address_id, $user);

                // 🔒 VALIDACIÓN: start_date <= end_date
                if ($request->start_date > $request->end_date) {
                    return back()->with('error', 'La fecha de inicio no puede ser posterior a la fecha de fin.')
                        ->withInput();
                }

                // 🔒 VALIDACIÓN: Consistencia entre start_time y end_time
                $hasStartTime = $request->filled('start_time');
                $hasEndTime = $request->filled('end_time');
                
                if ($hasStartTime !== $hasEndTime) {
                    return back()->with('error', 'Debes especificar tanto la hora de inicio como la hora de fin, o dejar ambas vacías para un bloqueo de día completo.')
                        ->withInput();
                }

                // 🔒 VALIDACIÓN: start_time < end_time si ambas están presentes
                if ($hasStartTime && $request->start_time >= $request->end_time) {
                    return back()->with('error', 'La hora de inicio debe ser anterior a la hora de fin.')
                        ->withInput();
                }

                // 🔍 MÁQUINA DE DETECCIÓN DE CONFLICTOS CON CITAS (usando SlotOverlapChecker)
                $conflicts = $this->detectAppointmentConflicts(
                    $doctorId,
                    $request->start_date,
                    $request->end_date,
                    $request->start_time,
                    $request->end_time,
                    $request->address_id
                );

                // Si hay conflictos y no se fuerza el guardado, retornar con advertencia
                if ($conflicts->count() > 0 && !$request->has('force_save')) {
                    return back()->with([
                        'conflict_appointments' => $conflicts,
                        'old_data' => $request->all()
                    ])->withInput();
                }

                // 🔍 VALIDACIÓN: Ausencias existentes superpuestas
                $hasOverlappingUnavailability = $this->detectUnavailabilityConflicts(
                    $doctorId,
                    $request->start_date,
                    $request->end_date,
                    $request->start_time,
                    $request->end_time,
                    $request->address_id
                );

                if ($hasOverlappingUnavailability) {
                    return back()->with('error', 'Ya existe un bloqueo de agenda en este rango de fechas/horas para este doctor.')
                        ->withInput();
                }

                // 🔥 CREAR LA INASISTENCIA
                $unavailability = Unavailability::create([
                    'doctor_id'  => $doctorId,
                    'address_id' => $request->address_id,
                    'start_date' => $request->start_date,
                    'end_date'   => $request->end_date,
                    'start_time' => $request->start_time,
                    'end_time'   => $request->end_time,
                    'reason'     => $request->reason,
                ]);

                Log::info('Inasistencia creada exitosamente', [
                    'unavailability_id' => $unavailability->id,
                    'doctor_id' => $doctorId,
                    'affected_appointments_count' => $conflicts->count(),
                ]);

                // 🔥 DISPARAR EVENTO CON CITAS AFECTADAS
                event(new \App\Events\DoctorUnavailabilityCreated($unavailability, $conflicts));

                //  INVALIDAR CACHÉ DE DISPONIBILIDAD
                $clinicId = ($context['type'] ?? 'particular') === 'clinic' ? (int)$context['id'] : null;
                $this->availabilityService->invalidateAvailabilityCache(
                    $clinicId,
                    [$doctorId],
                    $request->address_id
                );

                return back()->with('success', 'Ausencia e indisponibilidad guardada correctamente. Se han notificado los pacientes afectados.');
            });
        } catch (\Exception $e) {
            Log::error('Error al crear inasistencia', [
                'doctor_id' => $doctorId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Error al guardar la inasistencia. Por favor, intenta nuevamente.')
                ->withInput();
        }
    }

    /**
     * Detecta conflictos con citas existentes usando SlotOverlapChecker.
     */
    private function detectAppointmentConflicts(
        int $doctorId,
        string $startDate,
        string $endDate,
        ?string $startTime,
        ?string $endTime,
        ?int $addressId
    ): \Illuminate\Support\Collection {
        $appointmentsQuery = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'pending']);

        if ($addressId) {
            $appointmentsQuery->where('address_id', $addressId);
        }

        $conflicts = collect();

        // Iterar por cada día del rango y verificar solapamientos
        $currentDate = \Carbon\Carbon::parse($startDate);
        $endDateTime = \Carbon\Carbon::parse($endDate);

        while ($currentDate->lte($endDateTime)) {
            $dateStr = $currentDate->toDateString();
            
            $dayAppointments = $appointmentsQuery->where('date', $dateStr)->get();

            foreach ($dayAppointments as $appointment) {
                $appointmentStart = $appointment->start_time;
                $appointmentEnd = $appointment->end_time;

                // Si la ausencia es de día completo, siempre hay conflicto
                if (!$startTime || !$endTime) {
                    $conflicts->push($appointment);
                    continue;
                }

                // Usar SlotOverlapChecker para detectar solapamiento
                if (SlotOverlapChecker::intervalsOverlap(
                    $startTime,
                    $endTime,
                    $appointmentStart,
                    $appointmentEnd
                )) {
                    $conflicts->push($appointment);
                }
            }

            $currentDate->addDay();
        }

        return $conflicts->unique('id');
    }

    /**
     * Detecta conflictos con ausencias existentes.
     */
    private function detectUnavailabilityConflicts(
        int $doctorId,
        string $startDate,
        string $endDate,
        ?string $startTime,
        ?string $endTime,
        ?int $addressId
    ): bool {
        $query = Unavailability::where('doctor_id', $doctorId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $endDate)
                       ->where('end_date', '>=', $startDate);
                });
            });

        if ($addressId) {
            $query->where('address_id', $addressId);
        } else {
            $query->whereNull('address_id');
        }

        $existingUnavailabilities = $query->get();

        foreach ($existingUnavailabilities as $unav) {
            // Si ambas son de día completo, hay conflicto
            if ((!$unav->start_time && !$unav->end_time) && (!$startTime || !$endTime)) {
                return true;
            }

            // Si una es de día completo y la otra no, hay conflicto si las fechas se solapan
            if ((!$unav->start_time && !$unav->end_time) || (!$startTime || !$endTime)) {
                return true;
            }

            // Ambas tienen horas, verificar solapamiento
            if (SlotOverlapChecker::intervalsOverlap(
                $startTime,
                $endTime,
                $unav->start_time,
                $unav->end_time
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remueve el bloqueo validando jerarquías Multi-tenant estrictas.
     * 
     *  CORRECCIÓN: Invalida caché de disponibilidad al eliminar.
     */
    public function destroy(Unavailability $unavailability)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        // Validar propiedad
        if ($user->role === 'clinic') {
            if (empty($unavailability->address_id)) {
                abort(403, 'Las clínicas no pueden remover bloqueos globales de los médicos.');
            }
            
            $ownsAddress = Address::where('id', $unavailability->address_id)
                ->where('clinic_id', $user->clinic->id)
                ->exists();
            
            if (!$ownsAddress) {
                return back()->with('error', 'No tienes autorización para remover ausencias configuradas fuera de tus instalaciones.');
            }
        } else {
            if ($unavailability->doctor_id !== $user->doctor->id) {
                return back()->with('error', 'No tienes permiso para eliminar esta ausencia.');
            }
            
            if (($context['type'] ?? 'particular') === 'clinic') {
                $addressClinicId = Address::where('id', $unavailability->address_id)->value('clinic_id');
                if ((int)$addressClinicId !== (int)$context['id']) {
                    abort(403, 'No posees autorización sobre este registro en el entorno activo.');
                }
            }
        }

        // Capturar datos antes de eliminar para invalidar caché
        $doctorId = $unavailability->doctor_id;
        $addressId = $unavailability->address_id;
        $clinicId = ($context['type'] ?? 'particular') === 'clinic' ? (int)$context['id'] : null;

        $unavailability->delete();

        // ⚡ INVALIDAR CACHÉ DE DISPONIBILIDAD
        $this->availabilityService->invalidateAvailabilityCache(
            $clinicId,
            [$doctorId],
            $addressId
        );

        return back()->with('success', 'El periodo de indisponibilidad ha sido eliminado. Los turnos vuelven al buscador público.');
    }
}