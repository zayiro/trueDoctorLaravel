<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Appointments\AppointmentExpirer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Compatibilidad: jobs ya encolados con el sistema anterior. La expiración real la hace el barrido
 * `appointments:expire-unpaid`; este job solo delega en el mismo servicio (y respeta el vencimiento).
 */
class ExpireUnpaidAppointment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public Appointment $appointment) {}

    public function handle(AppointmentExpirer $expirer): void
    {
        $a = $this->appointment->fresh();

        if (!$a || $a->payment_expires_at?->isFuture()) {
            return;
        }

        $expirer->expire($a->id);
    }
}
