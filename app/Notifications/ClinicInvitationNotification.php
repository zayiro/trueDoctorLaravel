<?php

namespace App\Notifications;

use App\Models\Clinic;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicInvitationNotification extends Notification
{
    use Queueable;

    protected $clinic;

    public function __construct(Clinic $clinic)
    {
        $this->clinic = $clinic;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Ruta ficticia hacia el dashboard del doctor donde verá sus invitaciones
        $url = route('doctor.clinics.index'); 

        return (new MailMessage)
            ->subject("Invitación de Vinculación: {$this->clinic->name}")
            ->greeting("¡Hola, Dr/a. {$notifiable->name}!")
            ->line("El centro médico **{$this->clinic->name}** ha solicitado incorporar tu perfil profesional a su nómina de especialistas en la plataforma.")
            ->line("Al aceptar, la clínica podrá asignarte horarios en sus sedes y agendar citas con sus pacientes en tu nombre.")
            ->action('Ver Invitación Pendiente', $url)
            ->line('Si no reconoces este centro médico o prefieres atender de forma independiente, puedes rechazar la solicitud sin problemas.');
    }
}
