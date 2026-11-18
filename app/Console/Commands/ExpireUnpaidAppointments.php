<?php

namespace App\Console\Commands;

use App\Services\Appointments\AppointmentExpirer;
use Illuminate\Console\Command;

class ExpireUnpaidAppointments extends Command
{
    protected $signature = 'appointments:expire-unpaid {--limit=200}';

    protected $description = 'Expira las citas sin pago a tiempo y libera su turno';

    public function handle(AppointmentExpirer $expirer): int
    {
        $n = $expirer->sweep((int) $this->option('limit'));
        $this->info("Citas expiradas: {$n}");

        return self::SUCCESS;
    }
}
