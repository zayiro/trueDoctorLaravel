<?php

namespace App\Notifications;

use App\Models\Clinic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectDoctorWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $clinic;

    /**
     * Crear una nueva instancia de notificación inyectando el contexto de la clínica.
     */
    public function __construct(Clinic $clinic)
    {
        $this->clinic = $clinic;
    }

    /**
     * Definir los canales de entrega (Estrategia por Email obligatoria de tu plan SaaS).
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Estructurar el correo electrónico con diseño premium e internacional para OpenDoctor.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Recuperamos la identificación del médico para recordarle que esa es su contraseña inicial
        $doctorIdentification = $notifiable->doctor->identification ?? 'Tu número de documento';

        return (new MailMessage)
            ->level('info')
            ->subject('¡Bienvenido al Staff Médico de ' . $this->clinic->name . ' en OpenDoctor!')
            ->greeting('Estimado/a Dr/a. ' . $notifiable->name . ',')
            ->line('Le informamos que el centro médico **' . $this->clinic->name . '** lo ha dado de alta de manera directa y exitosa dentro de su nómina de especialistas en nuestra plataforma.')
            ->line('A partir de este momento, su perfil corporativo se encuentra activo y habilitado para gestionar su agenda de citas, historiales clínicos y telemedicina.')
            ->line('### Credenciales de acceso provisionales:')
            ->line('• **Usuario:** ' . $notifiable->email)
            ->line('• **Contraseña:** ' . $doctorIdentification . ' *(Su número de identificación/cédula)*')
            ->action('Ingresar a mi Panel de Control', route('login'))
            ->line('Por motivos estrictos de seguridad de la infraestructura del SaaS, le solicitamos modificar esta contraseña provisional inmediatamente después de realizar su primer inicio de sesión.')
            ->salutation('Atentamente, El equipo técnico de OpenDoctor.online');
    }

    /**
     * Convertir a formato de array si en el futuro habilitas las notificaciones en el Dashboard.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'clinic_id' => $this->clinic->id,
            'message' => 'Fuiste incorporado al staff de la clínica: ' . $this->clinic->name
        ];
    }
}
