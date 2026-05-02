<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'identification',
        'phone',        
        'birth_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
