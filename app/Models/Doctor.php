<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Doctor extends Model
{
    protected $fillable = [
        'medical_license',
        'phone',
        'experience_years',
        'language',
        'bio',
        'rating',
        'reviews_count',
        'identification',
        'plan'
    ];

    // Relación con el usuario (para nombre y foto)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Indica a Laravel que busque por 'slug' en las rutas en lugar de 'id'
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Lógica automática al crear el registro
     */
    protected static function booted()
    {
        static::creating(function ($doctor) {
            // Generamos el slug a partir del nombre del usuario relacionado
            // Resultado ejemplo: "dr-gregory-house-4a2b1"
            // Buscamos el nombre del usuario si no viene ya cargado
            $name = $doctor->user ? $doctor->user->name : 'doctor';
            $doctor->slug = Str::slug($name) . '-' . Str::lower(Str::random(5));
        });
    }

    // Relación con especialidad
    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')->withTimestamps();
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

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Atributo para obtener el promedio fácilmente
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    public function services() 
    {
        return $this->hasMany(Service::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

