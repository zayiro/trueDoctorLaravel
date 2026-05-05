<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'plan',
        'max_addresses',        
        'max_services',
        'price'
    ];
}
