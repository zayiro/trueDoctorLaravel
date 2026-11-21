<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Services\AppointmentStateManager;
use App\Notifications\AppointmentCancelledNotification;
use Throwable;

class ClinicAppointmentController extends Controller
{
    /**
     * Resuelve el contexto comercial de la clínica autenticada.
     * 
     *  Usa $user->role (campo del modelo) en lugar de hasRole() de Spatie
     * para consistencia con el resto del sistema multi-tenant.
     */
    private function getClinicContext(): Clinic
    {
        $user = Auth::user();
        
        if ($user->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para administradores de centros médicos.');
        }
        
        return $user->clinic ?? abort(404, 'Perfil institucional de la clínica no encontrado.');
    }

    /**
     * Muestra la lista de citas de la clínica con filtros dinámicos.
     */
    public function index(Request $request)
    {
        $clinic = $this->getClinicContext();

        // 1. Obtener médicos del staff aprobado usando Eloquent
        $staffDoctors = $clinic->doctors()
            ->where('clinic_doctor.status', 'approved')
            ->with('user')
            ->get()
            ->map(fn ($doctor) => new \ArrayObject([
                'id'     => $doctor->id,
                'name'   => $doctor->user->name ?? 'Sin nombre',
                'gender' => $doctor->gender,
            ], \ArrayObject::ARRAY_AS_PROPS));

        // 2. Obtener sedes institucionales usando Eloquent
        $clinicAddresses = $clinic->addresses()
            ->where('status', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 3. Query base filtrando estrictamente por el tenant de la clínica
        $query = Appointment::where('clinic_id', $clinic->id)
            ->where('status', '!=', 'expired') // reservas no pagadas a tiempo: para la clínica nunca existieron
            ->with(['patient.user', 'doctor.user', 'address.city', 'service']);

        // 4. Aplicar filtros dinámicos validados
        $this->applyFilters($query, $request);

        // 5. Paginación ordenada por fecha y hora más cercana
        try {
            $appointments = $query->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->paginate(15)
                ->withQueryString();
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Error al cargar las citas: ' . $e->getMessage());
        }

        return view('partner.clinic.appointments.index', compact(
            'appointments',
            'staffDoctors',
            'clinicAddresses'
        ));
    }

    /**
     * Muestra los detalles de una cita específica de la clínica.
     */
    public function show(int $appointmentId)
    {
        $clinic = $this->getClinicContext();

        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->where('id', $appointmentId)
            ->with(['patient.user', 'doctor.user', 'address.city', 'service.specialties'])
            ->firstOrFail();

        return view('partner.clinic.appointments.show', compact('appointment', 'clinic'));
    }

    /**
     * Cancela una cita de la clínica.
     *
     * Pasa por AppointmentStateManager (única puerta de cambios de estado): valida la transición, deja auditoría
     * y dispara AppointmentCancelled (correo al paciente, SendCancellationEmail). La reunión de Zoom la elimina
     * AppointmentObserver al detectar el cambio de estado. Misma lógica que usa el médico en PartnerAppointmentController.
     */
    public function cancel(Request $request, int $id)
    {
        $clinic = $this->getClinicContext();

        $validated = $request->validate([
            'cancellation_notes' => ['required', 'string', 'max:255'],
        ], [
            'cancellation_notes.required' => 'Debes indicar el motivo de la cancelación.',
        ]);

        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->where('id', $id)
            ->firstOrFail();

        $reason = 'Cancelado por la clínica. Motivo: ' . trim($validated['cancellation_notes']);

        try {
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

        // Notificación en base de datos para el paciente; un fallo aquí no debe deshacer la cancelación.
        try {
            $patientUser = $appointment->patient?->user;

            if ($patientUser) {
                $patientUser->notify(new AppointmentCancelledNotification($appointment, 'doctor'));
            }
        } catch (Throwable $e) {
            Log::warning("No se pudo notificar la cancelación al paciente (cita {$appointment->id}): " . $e->getMessage());
        }

        return back()->with('success', 'La cita ha sido cancelada y se notificó al paciente.');
    }

    /**
     * Médicos de la clínica que pueden tomar esta cita (misma fecha, hora, sede y servicio).
     * Responde JSON para el modal "Cambiar médico".
     */
    public function doctorOptions(Request $request, int $id)
    {
        $clinic = $this->getClinicContext();

        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            return response()->json(
                app(\App\Services\Appointments\AppointmentDoctorChanger::class)->options($appointment, Auth::user())
            );
        } catch (Throwable $e) {
            Log::warning("No se pudieron cargar los médicos para la cita {$appointment->id}: " . $e->getMessage());

            return response()->json(['message' => 'No pudimos cargar los médicos disponibles. Intenta de nuevo.'], 500);
        }
    }

    /**
     * Reasigna la cita a otro médico de la clínica.
     * Pasa por AppointmentDoctorChanger (única puerta): valida el turno del médico nuevo con bloqueo, audita y avisa por correo.
     */
    public function changeDoctor(Request $request, int $id)
    {
        $clinic = $this->getClinicContext();

        $validated = $request->validate([
            'doctor_id' => ['required', 'integer'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ], [
            'doctor_id.required' => 'Selecciona el médico que tomará la cita.',
        ]);

        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $result = app(\App\Services\Appointments\AppointmentDoctorChanger::class)->change(
                $appointment,
                Auth::user(),
                (int) $validated['doctor_id'],
                $validated['reason'] ?? null
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error("Error al cambiar el médico de la cita {$appointment->id}: " . $e->getMessage());

            return back()->with('error', 'No pudimos cambiar el médico. Intenta de nuevo en unos minutos.');
        }

        return $result->ok
            ? back()->with('success', $result->message)
            : back()->with('error', $result->message);
    }

    /**
     * Aplica los filtros dinámicos a la query de citas.
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro por médico (validado contra el staff de la clínica)
        if ($request->filled('doctor_id')) {
            $request->validate(['doctor_id' => 'integer|exists:doctors,id']);
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        // Filtro por sede (validado contra las sedes de la clínica)
        if ($request->filled('address_id')) {
            $request->validate(['address_id' => 'integer|exists:addresses,id']);
            $query->where('address_id', $request->input('address_id'));
        }

        // Filtro por estado (permite múltiples estados separados por coma)
        if ($request->filled('status')) {
            $statuses = explode(',', $request->input('status'));
            $request->validate(['status' => 'string']);
            
            $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
            $statuses = array_filter($statuses, fn ($s) => in_array(trim($s), $validStatuses));
            
            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        // Filtro por fecha exacta
        if ($request->filled('date')) {
            $request->validate(['date' => 'date_format:Y-m-d']);
            $query->whereDate('date', $request->input('date'));
        }

        // Filtro por rango de fechas (desde/hasta)
        if ($request->filled('date_from')) {
            $request->validate(['date_from' => 'date_format:Y-m-d']);
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $request->validate(['date_to' => 'date_format:Y-m-d|after_or_equal:date_from']);
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        // Filtro por búsqueda de paciente (nombre o email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $request->validate(['search' => 'string|max:255']);
            
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
    }
}