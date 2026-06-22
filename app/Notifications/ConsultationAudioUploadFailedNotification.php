<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsultationAudioUploadFailedNotification extends Notification
{
    use Queueable;

    protected $patient;
    protected $appointmentId;

    public function __construct($patient, $appointmentId = null)
    {
        $this->patient = $patient;
        $this->appointmentId = $appointmentId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patientName = $this->patient->user->name ?? 'el paciente';

        $actionUrl = $this->appointmentId
            ? "/partner/patients/{$this->patient->id}?appointment_id={$this->appointmentId}"
            : "/partner/patients/{$this->patient->id}";

        return [
            'patient_id'     => $this->patient->id,
            'appointment_id' => $this->appointmentId,
            'title'          => 'Audio de consulta sin subir',
            'message'        => "Hubo un problema de red al subir la grabación de la consulta con {$patientName}. Tu audio quedó guardado en este dispositivo — entra a la ficha del paciente para reintentar la subida.",
            'action_url'     => $actionUrl,
        ];
    }
}