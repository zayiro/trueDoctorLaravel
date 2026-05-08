<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'department_id', 'name', 'slug', 'state'];

    // Relación: Un municipio pertenece a un departamento
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Relación: Un municipio puede tener muchos pacientes residentes
    public function patients()
    {
        return $this->hasMany(Patient::class, 'city_id');
    }

    // Relación inversa: Una ciudad tiene muchas direcciones
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
