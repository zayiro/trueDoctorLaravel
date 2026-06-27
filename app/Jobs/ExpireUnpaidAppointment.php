<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Mail\AppointmentExpiredMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ExpireUnpaidAppointment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function handle(): void
    {
        // Refrescar para obtener el estado más reciente
        $this->appointment->refresh();

        // Solo actuar si sigue sin pagar
        if ($this->appointment->payment_status !== 'paid') {
            $this->appointment->update([
                'status'         => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            \Log::info('Cita expirada por falta de pago', [
                'appointment_id' => $this->appointment->id,
                'reference'      => $this->appointment->reference,
            ]);

            // Notificar al paciente
            $patientEmail = $this->appointment->patient?->user?->email;
            if ($patientEmail) {
                try {
                    Mail::to($patientEmail)->send(new AppointmentExpiredMail($this->appointment));
                } catch (\Throwable $e) {
                    \Log::error('Error enviando email de expiración: ' . $e->getMessage());
                }
            }
        }
    }
}