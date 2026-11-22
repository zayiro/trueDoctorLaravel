<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialCommissionChange extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'old_value' => 'float',
        'new_value' => 'float',
    ];
}
