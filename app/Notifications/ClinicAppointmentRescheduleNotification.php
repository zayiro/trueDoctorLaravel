<?php

namespace App\Notifications;

use App\Models\Unavailability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class ClinicAppointmentRescheduleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * La inasistencia que causó el impacto.
     */
    public Unavailability $unavailability;

    /**
     * Las citas afectadas.
     */
    public Collection $affectedAppointments;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct(Unavailability $unavailability, Collection $affectedAppointments)
    {
        $this->unavailability = $unavailability;
        $this->affectedAppointments = $affectedAppointments;
    }

    /**
     * Obtiene los canales en los que se debe enviar la notificación.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Obtiene la representación de correo de la notificación.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->unavailability->doctor->user->name ?? 'Un especialista';
        $affectedCount = $this->affectedAppointments->count();
        $startDate = $this->unavailability->start_date->format('d/m/Y');
        $endDate = $this->unavailability->end_date->format('d/m/Y');
        $reason = $this->unavailability->reason ?? 'una razón personal';

        $appointmentsList = $this->affectedAppointments
            ->map(fn($apt) => "- {$apt->patient->user->name} ({$apt->date->format('d/m/Y')} {$apt->start_time->format('H:i')})")
            ->join("\n");

        return (new MailMessage)
            ->subject("Alerta: {$affectedCount} citas requieren reagendamiento")
            ->greeting("Hola {$notifiable->name},")
            ->line("El especialista {$doctorName} ha registrado una inasistencia del {$startDate} al {$endDate} por {$reason}.")
            ->line("Esto afecta a {$affectedCount} cita(s) que requieren reagendamiento inmediato:")
            ->line($appointmentsList)
            ->action('Ver Detalles', route('clinic.affected-appointments.index'))
            ->line('Por favor, contacta a los pacientes afectados para coordinar nuevas fechas.')
            ->salutation('Saludos cordiales');
    }

    /**
     * Obtiene la representación de array de la notificación.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'unavailability_id' => $this->unavailability->id,
            'doctor_name' => $this->unavailability->doctor->user->name,
            'affected_count' => $this->affectedAppointments->count(),
            'start_date' => $this->unavailability->start_date->format('d/m/Y'),
            'end_date' => $this->unavailability->end_date->format('d/m/Y'),
            'reason' => $this->unavailability->reason,
            'message' => "{$this->affectedAppointments->count()} cita(s) requieren reagendamiento.",
        ];
    }
}
