<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Address;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\AppointmentService;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotPresenter;
use Illuminate\Support\Facades\Log;
use App\Services\Scheduling\SlotOverlapChecker;

class PartnerAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', now()->toDateString());
        $showAll = $request->has('all');

        $availableDoctors = collect([]);
        if ($user->role === 'clinic' && isset($user->clinic)) {
            $availableDoctors = $user->clinic->doctors()->with('user')->get();
        }

        $appointmentsQuery = Appointment::forCurrentContext();

        if ($user->role === 'clinic' && $request->filled('doctor_id')) {
            $appointmentsQuery->where('doctor_id', $request->doctor_id);
        }

        if (!$showAll) {
            $appointmentsQuery->where('date', $date);
        } else {
            $appointmentsQuery->where('date', '>=', now()->toDateString());
        }

        $appointments = $appointmentsQuery->with(['patient.user', 'address', 'service'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->groupBy('address_id');

        return view('partner.appointments.index', compact('appointments', 'availableDoctors', 'date', 'showAll'));
    }

    public function getSlots(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'doctor_id'  => 'required_without:clinic_id|nullable|integer',
            'clinic_id'  => 'required_without:doctor_id|nullable|integer',
            'is_virtual' => 'nullable|string',
            'service_id' => 'nullable|integer',
            'format'     => 'nullable|in:v2',
        ]);

        $addressId = (int) $request->input('address_id');
        $dateInput = $request->input('date');
        $isVirtual = $request->input('is_virtual') === 'true';
        $useNewEngine = (bool) config('booking.new_availability.slots_web', false);

        $address = Address::findOrFail($addressId);
        $doctorId = null;
        $doctorIdsArray = [];

        if ($request->filled('clinic_id')) {
            $clinicId = (int) $request->input('clinic_id');

            if ($address->clinic_id !== $clinicId) {
                return response()->json(['error' => 'La sede seleccionada no coincide con la infraestructura de la clínica.'], 403);
            }

            $requestedDoctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;

            if ($useNewEngine && $requestedDoctorId) {
                $belongsToClinic = DB::table('clinic_doctor')
                    ->where('clinic_id', $clinicId)
                    ->where('doctor_id', $requestedDoctorId)
                    ->where('status', 'approved')
                    ->exists();

                if (!$belongsToClinic) {
                    return response()->json(['error' => 'El especialista no pertenece a esta clínica.'], 403);
                }
                $doctorId = $requestedDoctorId;
                $doctorIdsArray = [$doctorId];
            } else {
                // 🔒 CORRECCIÓN CRÍTICA: Obtener TODOS los doctores activos en esa sede para ese día, no solo uno.
                $doctorsForDay = DB::table('schedules')
                    ->where('address_id', $addressId)
                    ->where('day', Carbon::parse($dateInput, config('app.timezone', 'America/Bogota'))->dayOfWeekIso)
                    ->where('is_active', true)
                    ->whereNotNull('doctor_id')
                    ->pluck('doctor_id')
                    ->unique()
                    ->toArray();

                if (!empty($doctorsForDay)) {
                    $doctorIdsArray = $doctorsForDay;
                    $doctorId = $doctorsForDay[0]; // Fallback para el motor legacy
                } else {
                    $doctorId = DB::table('clinic_doctor')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'approved')
                        ->value('doctor_id');
                    if ($doctorId) $doctorIdsArray = [$doctorId];
                }
            }

            if (empty($doctorIdsArray)) {
                return response()->json([]);
            }
        } else {
            $doctorId = (int) $request->input('doctor_id');
            $doctorIdsArray = [$doctorId];

            $doctorExists = DB::table('doctors')
                ->where('id', $doctorId)
                ->where('validation_status', 'approved')
                ->exists();

            if (!$doctorExists) {
                return response()->json(['error' => 'El especialista solicitado no está habilitado.'], 422);
            }
        }

        if ($useNewEngine) {
            try {
                $query = new AvailabilityQuery(
                    doctorIds: $doctorIdsArray, // ✅ Ahora pasamos el array completo si hay múltiples doctores
                    clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
                    addressId: $addressId,
                    serviceId: $request->filled('service_id') ? (int) $request->input('service_id') : null,
                );

                $slots = app(AvailabilityResolver::class)->slotsForDay(
                    $query,
                    Carbon::parse($dateInput, config('app.timezone', 'America/Bogota'))
                );

                return response()->json(
                    $request->input('format') === 'v2'
                        ? SlotPresenter::v2($slots)
                        : SlotPresenter::legacyWeb($slots)
                );
            } catch (\Throwable $e) {
                Log::error('getSlots web: falló el motor nuevo; se usa el anterior', [
                    'address_id' => $addressId, 'doctor_ids' => $doctorIdsArray, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Motor anterior (Legacy)
        $appointmentService = app(AppointmentService::class);
        $slots = $appointmentService->getAvailableSlots($addressId, $dateInput, $doctorId, $isVirtual);

        return response()->json($slots);
    }

    public function getServices(Address $address)
    {
        if (!$address->status) {
            return response()->json([], 404);
        }

        $services = $address->services()
            ->where('active', true)
            ->get()
            ->map(function ($service) {
                return [
                    'id'       => $service->id,
                    'name'     => $service->name,
                    'type'     => $service->type,
                    'duration' => (int) $service->pivot->duration,
                    'price'    => (float) $service->pivot->price,
                ];
            });

        return response()->json($services);
    }

    private function validateAppointmentAccess(Appointment $appointment)
    {
        $user = auth()->user();
        $context = session('doctor_context');

        if ($context['type'] === 'particular') {
            if ($appointment->doctor_id !== $user->doctor->id || $appointment->clinic_id !== null) {
                abort(403, 'No tienes permiso para gestionar esta cita.');
            }
        } elseif ($context['type'] === 'clinic') {
            if ($appointment->clinic_id !== $context['id']) {
                abort(403, 'No tienes permiso para gestionar esta cita.');
            }
        }
    }
        
    private function authorizeAppointmentAction(Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->role === 'clinic') {
            if ($appointment->clinic_id !== $user->clinic->id) {
                abort(403, 'No tienes permiso para gestionar esta cita institucional.');
            }
        } else if ($user->role === 'doctor') {
            $doctorContext = session('doctor_context');
            if ($doctorContext && $doctorContext['type'] === 'clinic') {
                if ($appointment->clinic_id !== $doctorContext['id']) {
                    abort(403, 'No tienes permiso para gestionar esta cita institucional.');
                }
            } else {
                if ($appointment->doctor_id !== $user->doctor->id) {
                    abort(403, 'No tienes permiso para gestionar esta cita privada.');
                }
            }
        }
    }

    public function complete(Appointment $appointment)
    {        
        $this->validateAppointmentAccess($appointment);
        $this->authorizeAppointmentAction($appointment);
        
        $appointment->update(['status' => 'completed']);
        return back()->with('success', 'La cita ha sido marcada como completada.');
    }

    public function cancel(Appointment $appointment)
    {        
        $this->validateAppointmentAccess($appointment);
        $this->authorizeAppointmentAction($appointment);
     
        $appointment->update(['status' => 'cancelled']);
        return back()->with('success', 'La cita ha sido marcada como cancelada.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->validateAppointmentAccess($appointment);
        $this->authorizeAppointmentAction($appointment);
        $appointment->delete();
        return back()->with('success', 'La cita ha sido eliminada exitosamente del historial.');
    }

    public function rescheduleProcess(Request $request, Appointment $appointment)
    {
        $this->validateAppointmentAccess($appointment);
        $user = auth()->user();

        if ($user->role === 'clinic') {
            if ($appointment->clinic_id !== $user->clinic->id) {
                abort(403, 'No autorizado. Esta cita no pertenece a tu centro clínico.');
            }
            $settings = ClinicSetting::where('clinic_id', $user->clinic->id)->first();
        } else {
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No autorizado. Esta cita pertenece a otra agenda médica.');
            }
            $settings = DoctorSetting::where('doctor_id', $user->doctor->id)->first();
        }

        if (!$settings) {
            return back()->with('error', 'La configuración de la agenda no está disponible.');
        }

        $validated = $request->validate([
            'new_date'       => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i:s',
        ]);

        $newDate      = $validated['new_date'];
        $newStartTime = $validated['new_start_time'];
        $newEndTime   = Carbon::parse($newStartTime)->addMinutes($appointment->duration)->format('H:i:s');
        $endTimeWithBuffer = Carbon::parse($newEndTime)->addMinutes($settings->buffer_time_minutes ?? 0)->format('H:i:s');

        // 🔒 1. VALIDACIÓN DE CRUCES (delegada al checker centralizado)
        $isSlotOccupied = SlotOverlapChecker::overlapsWithAppointments(
            doctorId: $appointment->doctor_id,
            date: $newDate,
            newStart: $newStartTime,
            newEnd: $endTimeWithBuffer,
            excludeAppointmentId: $appointment->id,
            statuses: ['pending', 'confirmed']
        );

        if ($isSlotOccupied) {
            return back()->with('error', 'Operación cancelada. El horario seleccionado colisiona con otra cita médica vigente.');
        }

        // 🔒 2. VALIDACIÓN CONTRA BLOQUEOS DE AGENDA (delegada al checker centralizado)
        $isDoctorUnavailable = SlotOverlapChecker::overlapsWithUnavailabilities(
            doctorId: $appointment->doctor_id,
            date: $newDate,
            newStart: $newStartTime,
            newEnd: $endTimeWithBuffer
        );

        if ($isDoctorUnavailable) {
            return back()->with('error', 'No se puede reagendar: el doctor tiene su agenda bloqueada en este horario (vacaciones, congresos o bloqueos administrativos).');
        }

        // 3. PERSISTENCIA ATÓMICA
        DB::transaction(function () use ($appointment, $newDate, $newStartTime, $newEndTime) {
            $appointment->update([
                'date'       => $newDate,
                'start_time' => $newStartTime,
                'end_time'   => $newEndTime,
                'status'     => 'confirmed',
                'email_sent' => false,
            ]);
        });

        // Invalidar caché relevante
        app(\App\Repositories\AvailabilityRepository::class)->invalidateAvailabilityCache(
            $appointment->clinic_id, 
            [$appointment->doctor_id], 
            $appointment->address_id
        );

        return back()->with('success', 'La consulta médica ha sido reprogramada y confirmada exitosamente.');
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,pending,completed,cancelled'
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'email_sent' => false
        ]);

        app(\App\Repositories\AvailabilityRepository::class)->invalidateAvailabilityCache(
            $appointment->clinic_id, 
            [$appointment->doctor_id], 
            $appointment->address_id
        );

        return back()->with('success', 'El estado de la consulta ha sido actualizado correctamente.');
    }    
}