<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Clinic extends Model {
    protected $fillable = [
        'user_id', 
        'name', 
        'nit', 
        'plan', 
        'phone', 
        'rating', 
        'reviews_count'
    ];

    public function user() {
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
        static::creating(function ($clinic) {
            // Generamos el slug a partir del nombre del usuario relacionado
            // Resultado ejemplo: "clinica-imbanaco-4a2b1"
            // Buscamos el nombre del usuario si no viene ya cargado
            $name = $clinic->user ? $clinic->user->name : 'clinica';
            $clinic->slug = Str::slug($name) . '-' . Str::lower(Str::random(5));
        });
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
}

