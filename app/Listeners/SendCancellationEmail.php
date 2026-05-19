<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Mail\AppointmentCancelledMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCancellationEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $doctorEmail = $appointment->doctor->user->email;

        // Enviamos el correo directamente al buzón del doctor implicado
        Mail::to($doctorEmail)->send(new AppointmentCancelledMail($appointment));
    }
}
