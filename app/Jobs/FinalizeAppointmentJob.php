<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Appointments\AppointmentFinalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sala de Zoom, correos y SMS de una cita ya confirmada. Es idempotente (AppointmentFinalizer).
 */
class FinalizeAppointmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $appointmentId)
    {
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(AppointmentFinalizer $finalizer): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if ($appointment) {
            $finalizer->finalize($appointment);
        }
    }
}
