<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Mail\AppointmentReminderMail;
use App\Notifications\AppointmentReminderNotification;
use App\Services\Twilio\WhatsAppTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function handle(WhatsAppTemplateService $whatsapp): void
    {
        $this->appointment->refresh();

        // Si ya fue cancelada o el recordatorio ya se envió, no hacer nada
        if (
            in_array($this->appointment->status, ['cancelled', 'completed']) ||
            $this->appointment->reminder_sent
        ) {
            return;
        }

        $patient      = $this->appointment->patient;
        $patientUser  = $patient?->user;
        $doctor       = $this->appointment->doctor;
        $patientEmail = $patientUser?->email;

        // ── EMAIL ──────────────────────────────────────────────────────────
        if ($patientEmail) {
            try {
                Mail::to($patientEmail)->send(new AppointmentReminderMail($this->appointment));
            } catch (\Throwable $e) {
                \Log::error('Error enviando recordatorio email: ' . $e->getMessage());
            }
        }

        // ── WHATSAPP ───────────────────────────────────────────────────────
        if ($patient?->phone) {
            try {
                $whatsapp->sendReminder(
                    phone:       $patient->country_code . $patient->phone,
                    patientName: $patientUser->name,
                    date:        \Carbon\Carbon::parse($this->appointment->date)->format('d/m/Y'),
                    time:        \Carbon\Carbon::parse($this->appointment->start_time)->format('H:i'),
                    doctor:      $doctor?->user?->name ?? 'tu especialista',
                );
            } catch (\Throwable $e) {
                \Log::error('Error enviando recordatorio WhatsApp: ' . $e->getMessage());
            }
        }

        // ── NOTIFICACIÓN EN APP ────────────────────────────────────────────
        if ($patientUser) {
            try {
                $patientUser->notify(new AppointmentReminderNotification($this->appointment));
            } catch (\Throwable $e) {
                \Log::error('Error enviando notificación app: ' . $e->getMessage());
            }
        }

        // Marcar recordatorio como enviado
        $this->appointment->update(['reminder_sent' => true]);

        \Log::info('Recordatorio enviado', ['appointment_id' => $this->appointment->id]);
    }
}