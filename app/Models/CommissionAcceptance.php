<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Constancia de que un médico/clínica aceptó la comisión de servicio del pago en línea de citas presenciales:
 * quién, cuándo, a qué porcentaje y con qué versión de las condiciones.
 */
class CommissionAcceptance extends Model
{
    public const TERMS_VERSION = 'v1';

    protected $fillable = [
        'payable_type', 'payable_id', 'user_id', 'mode', 'rate_percent', 'terms_version', 'ip', 'user_agent', 'accepted_at',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:2',
        'accepted_at'  => 'datetime',
    ];

    public static function isAcceptedFor(Model $owner, float $rate): bool
    {
        return static::query()
            ->where('payable_type', $owner->getMorphClass())
            ->where('payable_id', $owner->getKey())
            ->where('terms_version', self::TERMS_VERSION)
            ->where('rate_percent', round($rate, 2))
            ->exists();
    }

    public static function latestFor(Model $owner): ?self
    {
        return static::query()
            ->where('payable_type', $owner->getMorphClass())
            ->where('payable_id', $owner->getKey())
            ->latest('accepted_at')
            ->first();
    }

    public static function record(Model $owner, string $mode, float $rate, ?Request $request = null): self
    {
        return static::create([
            'payable_type'  => $owner->getMorphClass(),
            'payable_id'    => $owner->getKey(),
            'user_id'       => auth()->id(),
            'mode'          => $mode,
            'rate_percent'  => round($rate, 2),
            'terms_version' => self::TERMS_VERSION,
            'ip'            => $request?->ip(),
            'user_agent'    => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'accepted_at'   => now(),
        ]);
    }
}
