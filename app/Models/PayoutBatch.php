<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    public const DRAFT     = 'draft';
    public const APPROVED  = 'approved';
    public const PAID      = 'paid';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'number', 'status', 'cutoff_at', 'pay_date', 'payouts_count', 'beneficiaries_count', 'total_amount',
        'created_by', 'approved_by', 'approved_at', 'paid_at', 'cancelled_at',
    ];

    protected $casts = [
        'cutoff_at'    => 'datetime',
        'pay_date'     => 'date',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function payouts()
    {
        return $this->hasMany(DoctorPayout::class, 'batch_id');
    }

    public function events()
    {
        return $this->hasMany(\App\Models\PayoutBatchEvent::class, 'batch_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::DRAFT, self::APPROVED], true);
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Borrador', 'approved' => 'Aprobado', 'paid' => 'Pagado', 'cancelled' => 'Cancelado'][$this->status] ?? $this->status;
    }
}
