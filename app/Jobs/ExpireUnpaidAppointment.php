<?php

namespace App\Jobs;

use App\Mail\AppointmentExpiredMail;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExpireUnpaidAppointment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Si la cita ya no existe, el Job se descarta en silencio. */
    public $deleteWhenMissingModels = true;

    public function __construct(public Appointment $appointment) {}

    public function handle(): void
    {
        // Estado más reciente desde la base de datos
        $appointment = $this->appointment->fresh();

        // Solo expira lo que sigue esperando pago. Una cita pagada, gratis (código del 100%),
        // ya cancelada o completada no se toca.
        if (!$appointment
            || $appointment->payment_status !== 'pending'
            || in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return;
        }

        // Cancelación atómica: si el pago se confirma justo ahora, esta actualización
        // no encuentra la fila en 'pending' y no hace nada.
        $cancelled = Appointment::whereKey($appointment->id)
            ->where('payment_status', 'pending')
            ->update([
                'status'         => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

        if (!$cancelled) {
            return;
        }

        Log::info('Cita expirada por falta de pago', [
            'appointment_id' => $appointment->id,
            'reference'      => $appointment->reference,
        ]);

        // Notificar al paciente
        $appointment->refresh();
        $patientEmail = $appointment->patient?->user?->email;

        if ($patientEmail) {
            try {
                Mail::to($patientEmail)->send(new AppointmentExpiredMail($appointment));
            } catch (\Throwable $e) {
                Log::error('Error enviando email de expiración: ' . $e->getMessage());
            }
        }
    }
}