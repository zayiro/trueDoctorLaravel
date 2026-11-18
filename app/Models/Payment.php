<?php

namespace App\Models;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un intento de pago. Todo el dinero va en centavos (enteros).
 *
 * total_in_cents = base_amount_in_cents - discount_in_cents + commission_in_cents
 * (lo que el cliente paga). provider_fee_in_cents es solo contable.
 *
 * Ojo con los nombres: aquí `payable` es LO QUE SE PAGA (cita, suscripción, examen...) y `payee` es
 * quien recibirá el dinero. En doctor_payouts `payable` significa lo contrario (el beneficiario).
 */
class Payment extends Model
{
    protected $fillable = [
        'reference',
        'provider',
        'provider_transaction_id',
        'kind',
        'method',
        'status',
        'review_reason',
        'payable_type',
        'payable_id',
        'payer_id',
        'payee_type',
        'payee_id',
        'base_amount_in_cents',
        'commission_in_cents',
        'discount_in_cents',
        'provider_fee_in_cents',
        'total_in_cents',
        'currency',
        'promo_code_id',
        'payload',
        'approved_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'kind'                  => PaymentKind::class,
            'method'                => PaymentMethod::class,
            'status'                => PaymentTransactionStatus::class,
            'payload'               => 'array',
            'base_amount_in_cents'  => 'integer',
            'commission_in_cents'   => 'integer',
            'discount_in_cents'     => 'integer',
            'provider_fee_in_cents' => 'integer',
            'total_in_cents'        => 'integer',
            'approved_at'           => 'datetime',
            'expires_at'            => 'datetime',
        ];
    }

    /** Lo que se está pagando (Appointment, Subscription, ...). */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Quien recibirá el dinero (reservado para el reparto al médico; hoy casi siempre null). */
    public function payee(): MorphTo
    {
        return $this->morphTo();
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentTransactionStatus::Pending->value);
    }

    /** Total en pesos, solo para mostrar. */
    public function totalAmount(): float
    {
        return $this->total_in_cents / 100;
    }
}
