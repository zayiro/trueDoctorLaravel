<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'doctor_id',
        'plan_id',
        'wompi_transaction_id',
        'wompi_reference',
        'status',
        'amount_in_cents',
        'currency',
        'paid_at',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}