<?php

namespace App\Traits;

use App\Jobs\SendSmsReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait SendsReminderSms
{
    /**
     * Enviar recordatorio SMS en una fecha/hora específica
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $message Mensaje a enviar
     * @param Carbon $scheduledFor Fecha y hora en que enviar
     * @param string $context Contexto para logging (opcional)
     * @return void
     */
    public function scheduleReminder(
        string $phoneNumber,
        string $message,
        Carbon $scheduledFor,
        string $context = ''
    ): void {
        if (!$scheduledFor->isFuture()) {
            Log::warning("Fecha de recordatorio en el pasado: {$scheduledFor}");
            return;
        }

        SendSmsReminder::dispatch($phoneNumber, $message, $context)
            ->delay($scheduledFor);

        Log::info("Recordatorio programado para {$scheduledFor->format('Y-m-d H:i:s')} - {$context}");
    }

    /**
     * Enviar recordatorio SMS en N minutos
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $message Mensaje a enviar
     * @param int $minutesFromNow Minutos desde ahora
     * @param string $context Contexto para logging (opcional)
     * @return void
     */
    public function scheduleReminderInMinutes(
        string $phoneNumber,
        string $message,
        int $minutesFromNow,
        string $context = ''
    ): void {
        $scheduledFor = now()->addMinutes($minutesFromNow);
        $this->scheduleReminder($phoneNumber, $message, $scheduledFor, $context);
    }

    /**
     * Enviar recordatorio SMS en N horas
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $message Mensaje a enviar
     * @param int $hoursFromNow Horas desde ahora
     * @param string $context Contexto para logging (opcional)
     * @return void
     */
    public function scheduleReminderInHours(
        string $phoneNumber,
        string $message,
        int $hoursFromNow,
        string $context = ''
    ): void {
        $scheduledFor = now()->addHours($hoursFromNow);
        $this->scheduleReminder($phoneNumber, $message, $scheduledFor, $context);
    }

    /**
     * Enviar recordatorio SMS N minutos antes de una fecha específica
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $message Mensaje a enviar
     * @param Carbon $eventDate Fecha del evento
     * @param int $minutesBefore Minutos antes del evento
     * @param string $context Contexto para logging (opcional)
     * @return void
     */
    public function scheduleReminderBeforeEvent(
        string $phoneNumber,
        string $message,
        Carbon $eventDate,
        int $minutesBefore,
        string $context = ''
    ): void {
        $scheduledFor = $eventDate->copy()->subMinutes($minutesBefore);
        $this->scheduleReminder($phoneNumber, $message, $scheduledFor, $context);
    }
}