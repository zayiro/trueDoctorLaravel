<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorPayout extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';

    protected $fillable = [
        'appointment_id',
        'payment_id',
        'batch_id',
        'payable_id',
        'payable_type',
        'total_charged',
        'wompi_fee',
        'platform_commission',
        'amount_to_pay',
        'status',
        'transfer_reference',
        'due_date',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at'  => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function batch()
    {
        return $this->belongsTo(PayoutBatch::class, 'batch_id');
    }

    public function payable()
    {
        return $this->morphTo();
    }

    /** Quien registró la transferencia. */
    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
