<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentDuration extends Model
{
    protected $fillable = [
        'minutes',
        'additional_price',
        'active',
    ];

    protected $casts = [
        'minutes' => 'integer',
        'additional_price' => 'float',
        'active' => 'boolean',
    ];
}
