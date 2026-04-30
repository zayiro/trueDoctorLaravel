<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'medical_license',
        'phone',
        'experience_years',
        'language',
        'bio',
        'plan'
    ];

    // Relación con el usuario (para nombre y foto)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con especialidad
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    // Relación con sedes/consultorios
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function scopeFilterByCity($query, $city)
    {
        if ($city) {
            return $query->whereHas('addresses', function($q) use ($city) {
                $q->where('address', 'like', "%$city%");
            });
        }
    }

    public function canAddMoreAddresses(): bool
    {
        $limite = ($this->plan === 'avanzado') ? 10 : 2; // 10 para avanzado, 2 para básico
        return $this->addresses()->count() < $limite;
    }
}

