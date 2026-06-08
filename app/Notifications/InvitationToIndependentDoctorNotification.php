<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationToIndependentDoctorNotification extends Notification
{
    use Queueable;

    protected $clinicName;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct($clinicName)
    {
        $this->clinicName = $clinicName;
    }

    /**
     * Definir los canales de entrega (Email).
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Diseñar el cuerpo del correo electrónico.
     */
    public function toMail($notifiable): MailMessage
    {
        $loginUrl = url('/login');

        return (new MailMessage)
            ->subject('🚀 ¡Continúa tu consulta médica de forma independiente en OpenDoctor!')
            ->greeting('Estimado(a) Dr(a). ' . $notifiable->name . ',')
            ->line('Le informamos que su vinculación en la nómina de la clínica **' . $this->clinicName . '** ha finalizado en nuestra plataforma.')
            ->line('¡Pero su práctica médica no tiene por qué detenerse!')
            ->line('Queremos invitarle a dar el siguiente paso en su carrera: **gestionar su propio consultorio virtual de forma 100% independiente**.')
            ->line('Hemos activado para usted nuestro **Plan Free** de manera automática. Con su misma cuenta actual podrá:')
            ->line('• Controlar sus propios horarios y disponibilidad de agenda.')
            ->line('• Gestionar historias clínicas de forma segura.')
            ->line('• Crear recetas, evoluciones y marcas personales.')
            ->action('Activar mi Consultorio Independiente', $loginUrl)
            ->line('**Nota importante por normativa legal:** Al iniciar sesión por primera vez como independiente, el sistema le solicitará adjuntar su **Cédula de Ciudadanía** y **Tarjeta Profesional** para habilitar su agenda de pacientes.')
            ->line('Gracias por formar parte de la comunidad médica de OpenDoctor. ¡Estamos aquí para ayudarle a crecer!');
    }
}
