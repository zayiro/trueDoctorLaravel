<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_specialty')->withTimestamps();
    }
}
