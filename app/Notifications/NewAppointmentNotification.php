<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAppointmentNotification extends Notification
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📅 Nueva Cita Agendada - TrueDoctor')
            ->greeting('Hola, Dr. ' . $notifiable->name)
            ->line('Tienes una nueva cita agendada en tu plataforma.')
            ->line('**Paciente:** ' . $this->appointment->patient->user->name)
            ->line('**Servicio:** ' . $this->appointment->service->name)
            ->line('**Fecha:** ' . \Carbon\Carbon::parse($this->appointment->date)->format('d/m/Y'))
            ->line('**Hora:** ' . \Carbon\Carbon::parse($this->appointment->start_time)->format('g:i A'))
            ->action('Ver Agenda', url('/admin/dashboard'))
            ->line('Recuerda estar preparado para la consulta.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Estos datos son los que se guardarán en el campo 'data' (JSON) de la tabla
        return [
            'appointment_id' => $this->appointment->id,
            'patient_name'   => $this->appointment->patient->user->name,
            'service'        => $this->appointment->service->name,
            'date'           => $this->appointment->date,
            'hour'           => $this->appointment->start_time,
            'type'           => $this->appointment->service->type,
            'message'        => 'Nueva cita agendada con ' . $this->appointment->patient->user->name
        ];
    }
}
