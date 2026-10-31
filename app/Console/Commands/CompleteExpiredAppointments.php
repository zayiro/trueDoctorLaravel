<?php
namespace App\Console\Commands;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompleteExpiredAppointments extends Command
{
    protected $signature = 'appointments:complete-expired';
    protected $description = 'Marca como completadas las citas virtuales cuyo horario ya terminó';

    public function handle(): int
    {
        $now = now();
        
        $expired = Appointment::where('status', AppointmentStatus::CONFIRMED)
            ->where('service_id', function ($q) {
                $q->select('id')->from('services')->where('type', 'virtual');
            })
            ->whereRaw("CONCAT(date, ' ', end_time) < ?", [$now->format('Y-m-d H:i:s')])
            ->lockForUpdate()
            ->get();

        foreach ($expired as $appointment) {
            $appointment->update([
                'status'       => AppointmentStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);
            
            Log::info('Cita expirada completada automáticamente', [
                'appointment_id' => $appointment->id,
                'doctor_id'      => $appointment->doctor_id,
            ]);
        }

        $this->info("Completadas {$expired->count()} citas expiradas.");
        return self::SUCCESS;
    }
}