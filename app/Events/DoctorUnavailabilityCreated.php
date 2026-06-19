<?php

namespace App\Events;

use App\Models\Unavailability;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;

class DoctorUnavailabilityCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * La inasistencia recién creada.
     */
    public Unavailability $unavailability;

    /**
     * Las citas afectadas por esta inasistencia.
     */
    public Collection $affectedAppointments;

    /**
     * Crear una nueva instancia del evento.
     */
    public function __construct(Unavailability $unavailability, Collection $affectedAppointments = null)
    {
        $this->unavailability = $unavailability;
        $this->affectedAppointments = $affectedAppointments ?? collect();
    }

    /**
     * Obtiene los canales en los que el evento debe transmitirse.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('doctor.' . $this->unavailability->doctor_id),
        ];
    }
}
