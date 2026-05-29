<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MailLimitExceededNotification extends Notification
{
    use Queueable;

    protected $errorMessage;
    protected $patientEmail;

    public function __construct($errorMessage, $patientEmail)
    {
        $this->errorMessage = $errorMessage;
        $this->patientEmail = $patientEmail;
    }

    // Guardar la notificación estrictamente en la base de datos
    public function via($notifiable)
    {
        return ['database'];
    }

    // Estructura de datos que leerá tu panel de administración
    public function toArray($notifiable)
    {
        return [
            'title' => 'Alerta de Servidor de Correos',
            'message' => 'MailerSend rechazó el envío de confirmación debido a límites del plan gratuito.',
            'patient_email' => $this->patientEmail,
            'technical_error' => $this->errorMessage,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
