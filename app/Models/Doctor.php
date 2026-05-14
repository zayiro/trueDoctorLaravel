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
        'identification'
    ];

    //(Cuando no tenga sedes), el sistema cree una sede técnica.
    public function createVirtualAddress()
    {
        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina',
            'address'   => 'Plataforma Online',
            'type'      => 'virtual',
            'phone'     => $this->phone ?? 'N/A', // Usa el del doctor si existe
            'city_id'   => '11001',
            'status'    => true,
        ]);
    }

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

        static::updated(function ($doctor) {
            // Verificamos si el teléfono fue modificado
            if ($doctor->wasChanged('phone')) {
                // Buscamos la sede virtual por su nombre o dirección fija
                $doctor->addresses()
                    ->where('address', 'Plataforma Online')
                    ->update(['phone' => $doctor->phone]);
            }
        });
    }

    public function settings()
    {
        return $this->hasOne(DoctorSetting::class, 'doctor_id');
    }

    // Relación directa al Plan (Saltando por doctor_settings)
    public function plan()
    {
        return $this->hasOneThrough(
            Plan::class,
            DoctorSetting::class,
            'doctor_id', // FK en doctor_settings
            'id',        // FK en plans
            'id',        // Local key en doctors
            'plan_id'    // Local key en doctor_settings
        );
    }

    // Relación con especialidad
    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')->withTimestamps();
    }

    // También asegúrate de tener la relación con sedes:
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

    /**
     * Valida si puede agregar más direcciones según su plan actual.
     */
    public function canAddMoreAddresses(): bool
    {
        // 1. Obtenemos el límite desde el plan vinculado en settings
        // Si no hay plan, asumimos 0 o un valor base.
        $limit = $this->plan->max_addresses ?? 0;

        // 2. Contamos sus direcciones actuales
        $currentCount = $this->addresses()->count();

        return $currentCount < $limit;
    }

    /**
     * Relación: Obtiene todos los servicios a través de las direcciones.
     */
    public function services()
    {
        return $this->hasManyThrough(Service::class, Address::class);
    }

    public function canAddMoreServices(): bool
    {
        $limit = $this->plan->max_services ?? 0;

        // Contamos servicios únicos asociados a las direcciones del doctor
        $currentTotal = Service::whereHas('addresses', function($query) {
            $query->where('doctor_id', $this->id);
        })->count();

        return $currentTotal < $limit;
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

    public function appointments()
    {
        // Citas a través de las sedes (addresses)
        return $this->hasManyThrough(
            Appointment::class, 
            Address::class,
            'doctor_id',  // FK en tabla addresses
            'address_id', // FK en tabla appointments
            'id',         // Local key en tabla doctors
            'id'          // Local key en tabla addresses
        );
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    // Método útil: $user->canDo('can_search_patients')
    public function canDo($feature)
    {
        // Accedemos al plan a través de settings
        $plan = $this->settings?->plan;
        
        return $plan ? (bool) $plan->$feature : false;
    }

    public function expertises() 
    {
        return $this->hasMany(MedicalExpertise::class);
    }
}
