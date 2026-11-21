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
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotPresenter;
use App\Services\Appointments\AppointmentRescheduler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use App\Services\AppointmentStateManager;
use App\Notifications\AppointmentCancelledNotification;
use Throwable;

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

        // Las reservas que no se pagaron a tiempo (expired) se liberan en silencio: para el doctor nunca existieron.
        $appointmentsQuery->where('status', '!=', 'expired');

        // forCurrentContext() devuelve TODAS las citas si no hay contexto en sesión.
        // Sin contexto, se acota por el dueño según el rol (nunca se listan citas de otros tenants).
        if (!session('doctor_context')) {
            if ($user->role === 'clinic' && $user->clinic) {
                $appointmentsQuery->where('clinic_id', $user->clinic->id);
            } elseif ($user->role === 'doctor' && $user->doctor) {
                $appointmentsQuery->where('doctor_id', $user->doctor->id);
            } elseif (!$this->isAdmin($user)) {
                $appointmentsQuery->whereRaw('1 = 0');
            }
        }

        if ($user->role === 'clinic' && $request->filled('doctor_id')) {
            $appointmentsQuery->where('doctor_id', $request->doctor_id);
        }

        if (!$showAll) {
            $appointmentsQuery->where('date', $date);
        } else {
            $appointmentsQuery->where('date', '>=', now()->toDateString());
        }

        $appointments = $appointmentsQuery->with(['patient.user', 'address', 'service', 'patientHistory.notes'])
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

        $address = Address::findOrFail($addressId);
        $doctorIdsArray = [];

        if ($request->filled('clinic_id')) {
            $clinicId = (int) $request->input('clinic_id');

            if ($address->clinic_id !== $clinicId) {
                return response()->json(['error' => 'La sede seleccionada no coincide con la infraestructura de la clínica.'], 403);
            }

            $requestedDoctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;

            if ($requestedDoctorId) {
                $belongsToClinic = DB::table('clinic_doctor')
                    ->where('clinic_id', $clinicId)
                    ->where('doctor_id', $requestedDoctorId)
                    ->where('status', 'approved')
                    ->exists();

                if (!$belongsToClinic) {
                    return response()->json(['error' => 'El especialista no pertenece a esta clínica.'], 403);
                }

                $doctorIdsArray = [$requestedDoctorId];
            } else {
                // Todos los doctores con horario en esa sede ese día (no solo uno).
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
                } else {
                    $fallbackDoctorId = DB::table('clinic_doctor')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'approved')
                        ->value('doctor_id');

                    if ($fallbackDoctorId) {
                        $doctorIdsArray = [(int) $fallbackDoctorId];
                    }
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

        try {
            $query = new AvailabilityQuery(
                doctorIds: $doctorIdsArray,
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
            Log::error('getSlots web: falló el motor de disponibilidad', [
                'address_id' => $addressId, 'doctor_ids' => $doctorIdsArray, 'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'No se pudo consultar la disponibilidad. Intenta de nuevo.'], 500);
        }
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

    private function tz(): string
    {
        return config('app.timezone', 'America/Bogota');
    }

    private function isAdmin($user): bool
    {
        return $user->role === 'admin' || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
    }

    /**
     * Valor string del estado (status tiene cast a enum AppointmentStatus).
     */
    private function statusOf(Appointment $appointment): string
    {
        return $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;
    }

    private function validateAppointmentAccess(Appointment $appointment)
    {
        $context = session('doctor_context');

        // Sin contexto de sesión no hay nada que validar aquí: la propiedad de la cita
        // se comprueba por rol en authorizeAppointmentAction().
        if (!$context) {
            return;
        }

        $user = auth()->user();

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
            if ($appointment->clinic_id !== $user->clinic?->id) {
                abort(403, 'No tienes permiso para gestionar esta cita institucional.');
            }
        } else if ($user->role === 'doctor') {
            $doctorContext = session('doctor_context');
            if ($doctorContext && $doctorContext['type'] === 'clinic') {
                if ($appointment->clinic_id !== $doctorContext['id']) {
                    abort(403, 'No tienes permiso para gestionar esta cita institucional.');
                }
            } else {
                if ($appointment->doctor_id !== $user->doctor?->id) {
                    abort(403, 'No tienes permiso para gestionar esta cita privada.');
                }
            }
        }
    }

    /**
     * Punto único de autorización para gestionar una cita desde el panel de partners.
     * Solo admin, doctor y clínica; y el doctor/clínica solo sobre sus propias citas.
     */
    private function authorizeManage(Appointment $appointment): void
    {
        $user = Auth::user();

        if ($this->isAdmin($user)) {
            return;
        }

        abort_unless(in_array($user->role, ['doctor', 'clinic'], true), 403, 'No tienes permiso para gestionar citas.');

        $this->validateAppointmentAccess($appointment);
        $this->authorizeAppointmentAction($appointment);
    }

    /**
     * Notificación en base de datos para el paciente cuando el profesional cancela.
     * Los correos los envía el listener SendCancellationEmail (AppointmentCancelled, que dispara el gestor de estados).
     * Un fallo aquí NO debe romper la cancelación (la cita ya quedó cancelada).
     */
    private function notifyPatientOfCancellation(Appointment $appointment): void
    {
        try {
            $patientUser = $appointment->patient?->user;

            if ($patientUser) {
                $patientUser->notify(new AppointmentCancelledNotification($appointment, 'doctor'));
            }
        } catch (Throwable $e) {
            Log::warning("No se pudo notificar la cancelación al paciente (cita {$appointment->id}): " . $e->getMessage());
        }
    }

    public function complete(Appointment $appointment)
    {
        $this->authorizeManage($appointment);

        try {
            // El gestor valida (estado, pago, hora de inicio) y dispara AppointmentCompleted;
            // el listener EndZoomMeetingOnAppointmentCompleted cierra la reunión de Zoom.
            app(AppointmentStateManager::class)->transitionTo(
                $appointment,
                'completed',
                'Marcada como completada por el profesional desde la agenda médica',
                [],
                'doctor'
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'La cita ha sido marcada como completada.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorizeManage($appointment);

        $request->validate(['cancellation_reason' => 'nullable|string|max:255']);

        $reason = $request->filled('cancellation_reason')
            ? 'Cancelado por el profesional. Motivo: ' . trim($request->cancellation_reason)
            : 'Cancelado por el profesional desde la agenda médica.';

        try {
            // La reunión de Zoom la elimina AppointmentObserver (CancelZoomMeetingJob) al detectar el cambio de estado;
            // los correos, el listener SendCancellationEmail (AppointmentCancelled).
            app(AppointmentStateManager::class)->transitionTo(
                $appointment,
                'cancelled',
                $reason,
                ['notes' => ($appointment->notes ? $appointment->notes . "\n" : '') . "[Auditoría: {$reason}]"],
                'doctor'
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifyPatientOfCancellation($appointment);

        return back()->with('success', 'La cita ha sido marcada como cancelada.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorizeManage($appointment);

        if (!in_array($this->statusOf($appointment), ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Solo se pueden eliminar del historial las citas completadas o canceladas. Cancela la cita primero.');
        }

        $appointment->delete();
        return back()->with('success', 'La cita ha sido eliminada exitosamente del historial.');
    }

    /**
     * Vista para elegir el nuevo horario (doctor, clínica y admin).
     */
    public function rescheduleView(Appointment $appointment)
    {
        $this->authorizeManage($appointment);

        $appointment->loadMissing(['patient.user', 'doctor.user', 'service', 'address']);

        return view('partner.appointments.reschedule', compact('appointment'));
    }

    /**
     * Turnos de un día para reprogramar esta cita (mismas reglas que se validan al guardar).
     */
    public function rescheduleSlots(Request $request, Appointment $appointment, AppointmentRescheduler $rescheduler)
    {
        $validated = $request->validate(['date' => 'required|date_format:Y-m-d']);

        try {
            $slots = $rescheduler->slotsFor($appointment, auth()->user(), $validated['date']);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('rescheduleSlots: falló el motor de disponibilidad', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo consultar la disponibilidad.'], 500);
        }

        return response()->json(SlotPresenter::v2($slots));
    }

    /**
     * Reprograma la cita. Toda la lógica vive en AppointmentRescheduler (la misma que usa el paciente).
     */
    public function rescheduleProcess(Request $request, Appointment $appointment, AppointmentRescheduler $rescheduler)
    {
        $validated = $request->validate([
            'new_date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i,H:i:s',
        ]);

        try {
            $result = $rescheduler->reschedule($appointment, auth()->user(), $validated['new_date'], $validated['new_start_time']);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if (!$result->ok) {
            return back()->with('error', $result->message);
        }

        // La clínica tiene su propia agenda; el médico, la suya.
        $agenda = auth()->user()->role === 'clinic' ? 'partner.clinic.appointments.index' : 'partner.appointments.index';

        return redirect()->route($agenda, ['date' => $validated['new_date']])->with('success', $result->message);
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        // Antes este endpoint no verificaba quién llamaba ni sobre qué cita
        $this->authorizeManage($appointment);

        $validated = $request->validate([
            'status' => 'required|in:confirmed,pending,completed,cancelled'
        ]);

        try {
            app(AppointmentStateManager::class)->transitionTo(
                $appointment,
                $validated['status'],
                'Cambio de estado desde la agenda médica',
                ['email_sent' => false],
                'doctor'
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($validated['status'] === 'cancelled') {
            $this->notifyPatientOfCancellation($appointment);
        }

        return back()->with('success', 'El estado de la consulta ha sido actualizado correctamente.');
    }
}