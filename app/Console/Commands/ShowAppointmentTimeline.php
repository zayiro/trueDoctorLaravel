<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentEventSourcing;
use Illuminate\Console\Command;

class ShowAppointmentTimeline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment:timeline {appointment_id : ID de la cita}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el timeline completo de eventos de una cita (Event Sourcing)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $appointmentId = $this->argument('appointment_id');

        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            $this->error("Cita con ID {$appointmentId} no encontrada.");
            return 1;
        }

        $this->info("=== TIMELINE DE CITA #{$appointmentId} ===\n");
        $this->info("Referencia: {$appointment->reference}");
        $this->info("Paciente: {$appointment->patient->user->name}");
        $this->info("Doctor: {$appointment->doctor->user->name}");
        $this->info("Estado actual: {$appointment->status->value}\n");

        $events = AppointmentEventSourcing::getAppointmentTimeline($appointmentId);

        if ($events->isEmpty()) {
            $this->warn("No hay eventos registrados para esta cita.");
            return 0;
        }

        $this->table(
            ['Fecha/Hora', 'Tipo de Evento', 'Usuario', 'Descripción'],
            $events->map(function ($event) {
                return [
                    $event->created_at->format('Y-m-d H:i:s'),
                    $event->event_type,
                    $event->user?->name ?? $event->user_type ?? 'Sistema',
                    $event->description ?? '-',
                ];
            })->toArray()
        );

        // Mostrar estadísticas
        $stats = AppointmentEventSourcing::getEventStats($appointmentId);
        $this->info("\n=== ESTADÍSTICAS ===");
        $this->info("Total de eventos: {$stats['total_events']}");
        $this->info("Primer evento: {$stats['first_event']}");
        $this->info("Último evento: {$stats['last_event']}");
        $this->info("Usuarios involucrados: {$stats['users_involved']}");

        return 0;
    }
}
