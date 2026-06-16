<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification
{
    use Queueable;

    protected $appointment;
    protected $actor; // Quién canceló la cita ('médico' o 'paciente')

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct($appointment, $actor)
    {
        $this->appointment = $appointment;
        $this->actor = $actor;
    }

    /**
     * Determinar los canales de notificación (Base de datos en este caso).
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Obtener la representación de matriz de la notificación (JSON en BD).
     */
    public function toArray(object $notifiable): array
    {
        // Aseguramos el formateo de la fecha si viene como string o Carbon
        $dateFormatted = is_string($this->appointment->date) 
            ? date('d/m/Y', strtotime($this->appointment->date)) 
            : $this->appointment->date->format('d/m/Y');

        if ($this->actor === 'patient') {
            $message = "El paciente ha reagendado la cita {$this->appointment->reference} para el día {$dateFormatted} a las {$this->appointment->start_time}.";
            $dir_action = "/partner/appointments";
        } else {
            $message = "El médico ha reagendado la cita {$this->appointment->reference} para el día {$dateFormatted} a las {$this->appointment->start_time}.";
            $dir_action = "/patient/appointments";
        }    

        return [
            'appointment_id' => $this->appointment->id,
            'reference'      => $this->appointment->reference,
            'title'          => 'Cita Cancelada',
            'message'        => $message,
            'action_url'     => $dir_action,
        ];
    }
}
