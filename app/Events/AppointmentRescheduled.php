<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentRescheduled
{
    use Dispatchable, SerializesModels;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param Appointment $appointment La instancia de la cita que se está modificando.
     * @param string $previousDateTime El horario anterior formateado (ej: "28/10/2026 15:30").
     */
    public function __construct(
        public Appointment $appointment,
        public string $previousDateTime
    ) {}
}
