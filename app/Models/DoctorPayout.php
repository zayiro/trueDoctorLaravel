<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorPayout extends Model
{
    protected $fillable = [
        'appointment_id',
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
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at'  => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }
}