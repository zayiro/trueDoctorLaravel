<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'reward',
        'max_uses',
        'uses',
        'starts_at',
        'expires_at',
        'is_active',
        'applies_to',
    ];

    public const MODULE_APPOINTMENT   = 'appointment';
    public const MODULE_EXAM          = 'exam';
    public const MODULE_SKIN_ANALYSIS = 'skin_analysis';

    public const MODULE_LABELS = [
        self::MODULE_APPOINTMENT   => 'Citas médicas',
        self::MODULE_EXAM          => 'Exámenes médicos',
        self::MODULE_SKIN_ANALYSIS => 'Análisis de piel',
    ];

    public const MODULES = [
        self::MODULE_APPOINTMENT,
        self::MODULE_EXAM,
        self::MODULE_SKIN_ANALYSIS,
    ];

    // Indica a Laravel que trate estos campos como fechas/fechas con hora automáticamente
    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
        'applies_to' => 'array',
    ];

    // Un código pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si el cupón es válido de forma general y para un usuario específico.
     * 
     * @param int|null $userId ID del usuario que intenta aplicar el código.
     */
    public function isValid(?int $userId = null): bool
    {
        // Si el código es individual, debe pertenecer al usuario que lo intenta usar
        if ($this->user_id !== null && $this->user_id !== $userId) {
            return false;
        }

        return $this->isRedeemable();
    }

    /**
     * ¿El código se puede usar ahora? (activo, con cupo y dentro de su vigencia)
     */
    public function isRedeemable(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->max_uses !== null && $this->uses >= $this->max_uses) {
            return false;
        }
        if ($this->starts_at && now()->lt(Carbon::parse($this->starts_at))) {
            return false;
        }
        if ($this->expires_at && now()->gt(Carbon::parse($this->expires_at))) {
            return false;
        }

        return true;
    }

    /**
     * Descuento en pesos sobre un precio. 'percent' = porcentaje; cualquier otro tipo se trata como monto fijo.
     */
    public function discountFor(int $price): int
    {
        $discount = $this->type === 'percent'
            ? (int) round($price * (float) $this->reward / 100)
            : (int) round((float) $this->reward);

        return max(0, min($discount, $price));
    }

    /** Solo códigos que sirven para un módulo. Un código sin módulos no sirve en ninguno. */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->whereJsonContains('applies_to', $module);
    }

    public function appliesTo(string $module): bool
    {
        return in_array($module, $this->applies_to ?? [], true);
    }
}
