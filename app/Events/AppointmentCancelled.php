<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;

    /**
     * Quién canceló: 'patient', 'doctor' (médico o clínica, que actúa como proveedor) o 'system'.
     * Es opcional: quien dispare el evento sin indicarlo mantiene el comportamiento anterior (texto neutro).
     */
    public ?string $actor;

    /**
     * Create a new event instance.
     */
    public function __construct(Appointment $appointment, ?string $actor = null)
    {
        $this->appointment = $appointment;
        $this->actor = $actor;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
