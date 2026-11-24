<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Correo de confirmación de cuenta, en español, con el enlace firmado de Laravel. */
class VerifyEmailEs extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);
        $minutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Confirma tu correo en OpenDoctor')
            ->greeting('Hola')
            ->line('Confirma tu correo para activar tu código promocional de bienvenida y proteger tu cuenta.')
            ->action('Confirmar mi correo', $url)
            ->line("Por seguridad, este enlace vence en {$minutes} minutos. Si vence, puedes pedir otro desde tu panel.")
            ->line('Si no creaste una cuenta en OpenDoctor, ignora este mensaje.');
    }
}
