<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Historial de cambios de plan de un usuario (compras, renovaciones, gracia, vencimientos, bajadas, avisos de pago).
 * Solo se agregan filas; sirve para soporte y para explicar por qué un médico tiene el plan que tiene.
 * Registrar un evento NUNCA debe romper el flujo que lo origina.
 *
 * Tipos: purchased | renewed | upgraded | changed | grace | expired | downgraded | payment_review | payment_voided
 */
class PlanEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    /** @param array{from_plan_id?:?int,to_plan_id?:?int,subscription_id?:?int,payment_id?:?int,reason?:?string,meta?:?array} $data */
    public static function record(string $type, int $userId, array $data = []): ?self
    {
        try {
            return static::create([
                'user_id'         => $userId,
                'type'            => $type,
                'from_plan_id'    => $data['from_plan_id'] ?? null,
                'to_plan_id'      => $data['to_plan_id'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,
                'payment_id'      => $data['payment_id'] ?? null,
                'reason'          => isset($data['reason']) ? mb_substr((string) $data['reason'], 0, 160) : null,
                'meta'            => $data['meta'] ?? null,
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('plan_events: no se pudo registrar el evento', ['type' => $type, 'user_id' => $userId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** ¿Ya existe este evento? (para no repetir avisos). Si la consulta falla, devuelve false. */
    public static function happened(string $type, int $userId, ?int $subscriptionId = null): bool
    {
        try {
            return static::where('user_id', $userId)
                ->where('type', $type)
                ->when($subscriptionId, fn ($q) => $q->where('subscription_id', $subscriptionId))
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
