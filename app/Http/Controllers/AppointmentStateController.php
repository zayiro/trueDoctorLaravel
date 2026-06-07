<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentStateController extends Controller
{
    /**
     * Controlador universal de estados. 
     * El procesamiento de Zoom y correos se delega automáticamente al AppointmentObserver.
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        // 1. Validar estados permitidos y notas opcionales de auditoría institucional
        $request->validate([
            'status'             => 'required|in:pending,confirmed,completed,cancelled',
            'cancellation_notes' => 'nullable|string|max:500'
        ], [
            'status.required' => 'El estado de la cita es un campo obligatorio.'
        ]);

        $user = Auth::user();
        $newStatus = $request->status;

        // Si la cita ya está cancelada o completada en la BD, congelamos mutaciones secundarias
        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            return redirect()->back()->with('error', "No puedes modificar una cita que ya se encuentra en estado {$appointment->status}.");
        }

        // 2. CONTROL DE SEGURIDAD INTERNA Y PERMISOS POR ROL (ANTI-FRAUDES MULTI-TENANT)
        if ($user->role === 'patient') {
            // Un paciente SOLO puede cancelar su propia cita cruzando su relación de usuario
            if ($appointment->patient->user_id !== $user->id) {
                abort(403, 'Acción no autorizada. No eres el propietario de esta reserva.');
            }
            if ($newStatus !== 'cancelled') {
                return redirect()->back()->with('error', 'Los pacientes únicamente tienen permitido cancelar citas.');
            }
        }
        elseif (in_array($user->role, ['doctor', 'clinic'])) {
            // Extraemos el ID del Tenant (clínica o médico) de forma dinámica
            $ownerId = $user->role === 'clinic' ? $user->clinic->id : $user->doctor->id;
            
            // Evaluamos la propiedad de la infraestructura golpeando la tabla addresses
            $address = DB::table('addresses')->where('id', $appointment->address_id)->first();

            if (!$address) {
                abort(404, 'La sede vinculada a la cita médica no existe en el sistema.');
            }

            // Si es una clínica, la sede debe pertenecerle obligatoriamente en su columna clinic_id
            if ($user->role === 'clinic' && $address->clinic_id !== $ownerId) {
                abort(403, 'Acción no autorizada. Esta sede no pertenece a tu centro médico.');
            }

            // Si es un doctor independiente, la sede debe ser de su propiedad
            if ($user->role === 'doctor' && $address->doctor_id !== $ownerId) {
                // Salvaguarda: Si atiende en una clínica aliada, validamos propiedad directa de la cita
                if ($appointment->doctor_id !== $ownerId) {
                    abort(403, 'Acción no autorizada.');
                }
            }
        } elseif ($user->role !== 'admin') {
            abort(403, 'Rol no autorizado para modificar estados médicos.');
        }
        // 3. EJECUTAR ACTUALIZACIÓN ATÓMICA DE BASE DE DATOS
        try {
            DB::transaction(function () use ($appointment, $newStatus, $request, $user) {
                
                // Formatear e inyectar las notas de auditoría si se ingresó un motivo en la modal
                $notes = $appointment->notes;
                if ($newStatus === 'cancelled' && $request->has('cancellation_notes') && $request->cancellation_notes) {
                    $roleLabel = match ($user->role) { 'clinic' => 'Clínica', 'doctor' => 'Especialista', 'patient' => 'Paciente', default => 'Admin' };
                    $notes .= "\n[Cancelado por {$roleLabel} el " . now()->toDateTimeString() . "]: " . trim($request->cancellation_notes);
                }

                // El método update disparará de forma segura el Flujo B de tu AppointmentObserver
                $appointment->update([
                    'status' => $newStatus,
                    'notes'  => $notes
                ]);
            });

            // Redirección adaptativa según el panel de control de origen
            if ($user->role === 'clinic') {
                return redirect()->route('partner.clinic.appointments.index')
                    ->with('success', "El estado de la reservación médica con referencia {$appointment->reference} ha sido actualizado correctamente.");
            }

            return redirect()->back()
                ->with('success', 'El estado de la reservación ha sido actualizado a: ' . __($newStatus));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
} // Cierre definitivo de la clase
