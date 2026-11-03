<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification
{
    use Queueable;

    protected $appointment;
    protected $actor; // Quién canceló la cita: 'patient' (paciente) o 'doctor' (médico/clínica)

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

        $timeFormatted = \Carbon\Carbon::parse($this->appointment->start_time)->format('g:i A');
        $reference     = $this->appointment->reference;

        // El destinatario es siempre la contraparte de quien canceló
        if (in_array($this->actor, ['patient', 'paciente'], true)) {
            // Cancela el paciente → se avisa al médico/clínica
            $message    = "El paciente ha cancelado la cita {$reference} que estaba programada para el día {$dateFormatted} a las {$timeFormatted}.";
            $dir_action = "/partner/appointments";
        } else {
            // Cancela el médico/clínica → se avisa al paciente
            $message    = "El médico ha cancelado la cita {$reference} que estaba programada para el día {$dateFormatted} a las {$timeFormatted}. Puedes agendar una nueva consulta cuando lo desees.";
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