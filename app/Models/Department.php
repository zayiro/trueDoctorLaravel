<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    // Indicamos que el ID no es autoincremental
    public $incrementing = false;

    // Indicamos que la llave primaria es un string (Código DIVIPOLA)
    protected $keyType = 'string';

    protected $fillable = ['id', 'name'];

    // Relación: Un departamento tiene muchos municipios
    public function cities()
    {
        return $this->hasMany(City::class, 'department_id', 'id');
    }
}
