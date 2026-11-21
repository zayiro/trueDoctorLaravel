<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reembolso de una cita pagada en línea que se canceló.
 * Mientras el dinero no se devuelva en Wompi queda "pending"; al registrarlo pasa a "completed".
 * El dinero va en centavos.
 */
class PaymentRefund extends Model
{
    public const PENDING   = 'pending';
    public const COMPLETED = 'completed';

    protected $fillable = [
        'payment_id',
        'appointment_id',
        'patient_user_id',
        'amount_in_cents',
        'status',
        'cancelled_by',
        'reason',
        'notes',
        'provider_reference',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_in_cents' => 'integer',
            'processed_at'    => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withTrashed();
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /** Monto en pesos para mostrar. */
    public function amount(): float
    {
        return $this->amount_in_cents / 100;
    }
}
