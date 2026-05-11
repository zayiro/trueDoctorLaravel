<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    protected $fillable = ['name', 'code', 'active'];

    // Relación: Una aseguradora tiene muchos pacientes
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
