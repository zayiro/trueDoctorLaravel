<?php

namespace App\Notifications;

use App\Models\Clinic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

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
     * Definir los canales de entrega.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Correo de bienvenida con enlace de activación (el médico define su propia contraseña).
     * Ya no se usa la cédula como contraseña inicial.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Mismo mecanismo de "restablecer contraseña" de Fortify: token de un solo uso.
        $token = Password::broker()->createToken($notifiable);

        $activationUrl = route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->level('info')
            ->subject('¡Bienvenido al Staff Médico de ' . $this->clinic->name . ' en OpenDoctor!')
            ->greeting('Estimado/a Dr/a. ' . $notifiable->name . ',')
            ->line('El centro médico **' . $this->clinic->name . '** lo ha dado de alta dentro de su nómina de especialistas en OpenDoctor.')
            ->line('Su perfil corporativo ya está activo. Para ingresar, primero debe crear su contraseña personal con el siguiente botón:')
            ->action('Activar mi cuenta y crear contraseña', $activationUrl)
            ->line('Su usuario de acceso es: **' . $notifiable->email . '**')
            ->line('Este enlace es de un solo uso y vence en ' . $minutes . ' minutos. Si ya venció, ingrese a ' . route('password.request') . ' y solicite uno nuevo con su correo.')
            ->salutation('Atentamente, El equipo de OpenDoctor.online');
    }

    /**
     * Convertir a formato de array si en el futuro habilitas las notificaciones en el Dashboard.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'clinic_id' => $this->clinic->id,
            'message'   => 'Fuiste incorporado al staff de la clínica: ' . $this->clinic->name,
        ];
    }
}
