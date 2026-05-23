<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Doctor extends Model
{
    protected $fillable = [
        'medical_license',
        'phone',
        'experience_years',
        'languages',
        'bio',
        'rating',
        'reviews_count',
        'identification',
        'gender',
        'validation_status',
        'identity_card_path',
        'professional_card_path',
        'active'
    ];

    protected $casts = [
        'languages' => 'json', 
        'active' => 'boolean',
    ];

    // (Cuando no tenga sedes), el sistema cree una sede técnica.
    public function createVirtualAddress()
    {
        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina',
            'address'   => 'Plataforma Online',
            'type'      => 'virtual',
            'phone'     => $this->phone ?? 'N/A', 
            'city_id'   => '11001',
            'status'    => true,
        ]);
    }

    // Relación con el usuario (para nombre y foto)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::creating(function ($doctor) {
            $name = 'doctor';
            
            if ($doctor->user) {
                $name = $doctor->user->name;
            } elseif ($doctor->user_id) {
                $name = User::find($doctor->user_id)?->name ?? 'doctor';
            }

            // Limpiamos la identificación por si tiene espacios o puntos
            $cleanId = Str::slug($doctor->identification);

            // El slug quedará hermoso: 'dr-juan-perez-10203040'
            $doctor->slug = Str::slug($name) . '-' . $cleanId;
        });

        static::updated(function ($doctor) {
            if ($doctor->wasChanged('phone')) {
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

    public function plan()
    {
        return $this->hasOneThrough(
            Plan::class,
            DoctorSetting::class,
            'doctor_id', 
            'id',        
            'id',        
            'plan_id'    
        );
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')->withTimestamps();
    }

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
        $limit = $this->plan->max_addresses ?? 0;
        $currentCount = $this->addresses()->count();
        return $currentCount < $limit;
    }

    public function services()
    {
        return $this->hasManyThrough(Service::class, Address::class);
    }

    public function canAddMoreServices(): bool
    {
        $limit = $this->plan->max_services ?? 0;
        $currentTotal = Service::whereHas('addresses', function($query) {
            $query->where('doctor_id', $this->id);
        })->count();

        return $currentTotal < $limit;
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    /**     
     * Al usar hasMany, incluimos tanto citas presenciales como virtuales (address_id = null)
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /**
     * NUEVA RELACIÓN: Ausencias o bloqueos de agenda del médico.
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class, 'doctor_id');
    }

    /**
     * NUEVA RELACIÓN: Acceso directo a todos los horarios de sus sucursales.
     */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(Schedule::class, Address::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function canDo($feature)
    {
        $plan = $this->settings?->plan;
        return $plan ? (bool) $plan->$feature : false;
    }

    public function expertises() 
    {
        return $this->hasMany(MedicalExpertise::class);
    }
}
