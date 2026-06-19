<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\Unavailability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentRescheduleRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * La cita afectada.
     */
    public Appointment $appointment;

    /**
     * La inasistencia que causó el impacto.
     */
    public Unavailability $unavailability;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct(Appointment $appointment, Unavailability $unavailability)
    {
        $this->appointment = $appointment;
        $this->unavailability = $unavailability;
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
        $doctorName = $this->appointment->doctor->user->name ?? 'El especialista';
        $appointmentDate = $this->appointment->date->format('d/m/Y');
        $appointmentTime = $this->appointment->start_time->format('H:i');
        $reason = $this->unavailability->reason ?? 'una razón personal';

        return (new MailMessage)
            ->subject('Tu cita ha sido afectada - Reagendamiento requerido')
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu cita con {$doctorName} programada para el {$appointmentDate} a las {$appointmentTime} ha sido afectada por {$reason}.")
            ->line('Por favor, accede a tu cuenta para reagendar tu cita en un horario disponible.')
            ->action('Reagendar Cita', route('appointments.reschedule', $this->appointment->id))
            ->line('Si tienes preguntas, no dudes en contactarnos.')
            ->salutation('Saludos cordiales');
    }

    /**
     * Obtiene la representación de array de la notificación.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'unavailability_id' => $this->unavailability->id,
            'doctor_name' => $this->appointment->doctor->user->name,
            'original_date' => $this->appointment->date->format('d/m/Y'),
            'original_time' => $this->appointment->start_time->format('H:i'),
            'reason' => $this->unavailability->reason ?? 'una razón personal',
            'message' => "Tu cita ha sido afectada y requiere reagendamiento.",
        ];
    }
}
