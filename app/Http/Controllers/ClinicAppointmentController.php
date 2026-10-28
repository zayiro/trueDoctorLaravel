<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            ->map(fn ($doctor) => [
                'id'   => $doctor->id,
                'name' => $doctor->user->name ?? 'Sin nombre',
            ]);

        // 2. Obtener sedes institucionales usando Eloquent
        $clinicAddresses = $clinic->addresses()
            ->where('status', true)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        // 3. Query base filtrando estrictamente por el tenant de la clínica
        $query = Appointment::where('clinic_id', $clinic->id)
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
     * Cambia el estado de una cita (confirmar, cancelar, completar).
     */
    public function updateStatus(Request $request, int $appointmentId)
    {
        $clinic = $this->getClinicContext();

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'cancellation_reason' => ['nullable', 'string', 'max:500', 'required_if:status,cancelled'],
        ], [
            'status.required' => 'Debes seleccionar un estado válido.',
            'status.in' => 'El estado seleccionado no es válido.',
            'cancellation_reason.required_if' => 'Debes indicar el motivo de la cancelación.',
        ]);

        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->where('id', $appointmentId)
            ->firstOrFail();

        // Validar que no se pueda cambiar el estado de una cita ya completada o cancelada
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'No se puede modificar el estado de una cita ya completada o cancelada.');
        }

        // Validar que no se pueda confirmar una cita en el pasado
        if ($validated['status'] === 'confirmed' && $appointment->date < now()->toDateString()) {
            return back()->with('error', 'No se puede confirmar una cita con fecha pasada.');
        }

        try {
            DB::transaction(function () use ($appointment, $validated) {
                $updateData = ['status' => $validated['status']];
                
                if ($validated['status'] === 'cancelled' && !empty($validated['cancellation_reason'])) {
                    $updateData['cancellation_reason'] = $validated['cancellation_reason'];
                    $updateData['cancelled_at'] = now();
                }

                if ($validated['status'] === 'completed') {
                    $updateData['completed_at'] = now();
                }

                $appointment->update($updateData);
            });

            $statusMessages = [
                'pending'   => 'La cita ha sido marcada como pendiente.',
                'confirmed' => 'La cita ha sido confirmada exitosamente.',
                'completed' => 'La cita ha sido marcada como completada.',
                'cancelled' => 'La cita ha sido cancelada.',
                'no_show'   => 'La cita ha sido marcada como no asistida.',
            ];

            return back()->with('success', $statusMessages[$validated['status']] ?? 'Estado actualizado.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
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