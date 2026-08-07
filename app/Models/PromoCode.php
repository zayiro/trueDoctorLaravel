<?php

namespace App\Models;

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
    ];

    // Indica a Laravel que trate estos campos como fechas/fechas con hora automáticamente
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
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
        // 1. Verificar si el cupón está activo manualmente
        if (!$this->is_active) {
            return false;
        }

        // 2. Si el código es individual, asegurar que le pertenezca al usuario que lo intenta usar
        if ($this->user_id !== null && $this->user_id !== $userId) {
            return false;
        }

        // 3. Verificar si ya alcanzó el límite de usos globales
        if ($this->max_uses !== null && $this->uses >= $this->max_uses) {
            return false;
        }

        // 4. Verificar rango de fechas (si aplica)
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }
}
