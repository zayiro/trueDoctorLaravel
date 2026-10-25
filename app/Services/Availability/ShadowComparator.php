<?php

declare(strict_types=1);

namespace App\Services\Availability;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MODO SOMBRA (fase 2): compara el motor viejo con el nuevo sin cambiar lo que ve el usuario.
 *
 * Se activa con BOOKING_SHADOW=true y se muestrea con BOOKING_SHADOW_SAMPLE (por defecto 10 %).
 * Registra solo identificadores y horas: nunca datos personales. Nunca lanza excepciones.
 */
final class ShadowComparator
{
    public function __construct(private readonly AvailabilityResolver $resolver)
    {
    }

    public function compareNextTurn(array $doctorIds, ?int $clinicId, ?DateTimeInterface $legacy): void
    {
        if (!config('booking.shadow') || !$this->sampled()) {
            return;
        }

        try {
            $tz = config('booking.timezone', 'America/Bogota');
            $new = $this->resolver->nextSlot(new AvailabilityQuery($doctorIds, $clinicId));

            $legacyKey = $legacy ? CarbonImmutable::instance($legacy)->setTimezone($tz)->format('Y-m-d H:i') : null;
            $newKey = $new?->start->format('Y-m-d H:i');

            $day = now()->toDateString();
            Cache::add("availability:shadow:{$day}:total", 0, 172800);
            Cache::increment("availability:shadow:{$day}:total");

            if ($legacyKey !== $newKey) {
                Cache::add("availability:shadow:{$day}:diff", 0, 172800);
                Cache::increment("availability:shadow:{$day}:diff");

                Log::warning('availability.shadow.next_turn.diff', [
                    'doctor_ids' => $doctorIds,
                    'clinic_id' => $clinicId,
                    'legacy' => $legacyKey,
                    'new' => $newKey,
                    'new_doctor_id' => $new?->doctorId,
                    'new_address_id' => $new?->addressId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('availability.shadow.error', ['error' => $e->getMessage()]);
        }
    }

    private function sampled(): bool
    {
        $rate = (float) config('booking.shadow_sample_rate', 0.1);

        return $rate >= 1 || (mt_rand() / mt_getrandmax()) < $rate;
    }
}