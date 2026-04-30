<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'slug', 'state'];

    // Relación inversa: Una ciudad tiene muchas direcciones
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
