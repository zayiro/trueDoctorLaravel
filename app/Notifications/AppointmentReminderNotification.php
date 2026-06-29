<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'   => '⏰ Recordatorio de cita',
            'body'    => 'Tu cita con Dr(a). ' . ($this->appointment->doctor?->user?->name ?? 'tu especialista') . ' es el ' .
                          \Carbon\Carbon::parse($this->appointment->date)->format('d/m/Y') . ' a las ' .
                          \Carbon\Carbon::parse($this->appointment->start_time)->format('H:i'),
            'url'     => route('home'),
            'type'    => 'appointment_reminder',
        ];
    }
}