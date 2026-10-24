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
        $appointment = $this->appointment->fresh();

        if (!$appointment) {
            return;
        }

        // Solo expira lo que sigue esperando pago: pendiente, sin fecha de pago.
        // Una cita pagada, gratis (código del 100%), cancelada o completada no se toca.
        if ($this->raw($appointment->payment_status) !== 'pending'
            || $this->raw($appointment->status) !== 'pending'
            || $appointment->paid_at !== null) {
            return;
        }

        // Cancelación atómica: si el pago se confirma justo ahora, esta actualización
        // no encuentra la fila en 'pending' y no hace nada.
        $cancelled = Appointment::whereKey($appointment->id)
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->whereNull('paid_at')
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

    /** Valor en texto de un campo que puede ser un enum o un string. */
    private function raw(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}