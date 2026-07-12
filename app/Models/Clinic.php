<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Clinic extends Model 
{
    protected $fillable = [
        'slug', 'user_id', 'nit', 'reps_code',
        'phone', 'country_code', 'bio', 'experience_years', 'languages', 'rating', 
        'reviews_count', 'validation_status',
        'identity_card_path', 'reps_code_card_path', 'active'
    ];

    protected $casts = [
        'active'        => 'boolean',
        'rating'        => 'float',
        'reviews_count' => 'integer',
        'languages'     => 'json',
    ];
    public function createVirtualAddress()
    {
        $exists = $this->addresses()->where('type', 'virtual')->exists();
        
        if ($exists) {
            return $this->addresses()->where('type', 'virtual')->first();
        }

        $firstCity = City::first();
        
        if (!$firstCity) {
            abort(500, 'Error del sistema: Debe inicializar las ciudades (DIVIPOLA) antes de generar sedes virtuales.');
        }

        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina (Institucional)',
            'address'   => 'Plataforma Online',
            'type'      => 'virtual',
            'phone'     => $this->phone ?? 'N/A', 
            'city_id'   => $firstCity->id, 
            'status'    => true,
        ]);
    }
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::creating(function ($clinic) {
            $name = 'centro-medico';
            
            if ($clinic->user) {
                $name = $clinic->user->name;
            } elseif ($clinic->user_id) {
                $name = User::find($clinic->user_id)?->name ?? 'clinica';
            }
            
            do {
                $code = Str::slug($name) . '-' . strtoupper(Str::random(5));
            } while (self::where('slug', $code)->exists());

            $clinic->slug = $code; 
        });

        static::updated(function ($clinic) {
            if ($clinic->wasChanged('phone')) {
                $clinic->addresses()
                    ->where('type', 'virtual')
                    ->whereNull('doctor_id') 
                    ->update(['phone' => $clinic->phone]);
            }
        });
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ClinicSetting::class, 'clinic_id');
    }

    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            ClinicSetting::class,
            'clinic_id', 
            'id',        
            'id',        
            'plan_id'    
        );
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'clinic_id');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'clinic_doctor')
                    ->withPivot('status')
                    ->withTimestamps();
    }
    public function services() 
    {
        return Service::whereHas('specialties', function ($query) {
            $query->where('service_specialty.user_id', $this->user_id);
        });
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'clinic_specialty')->withTimestamps();
    }

    public function canAddMoreAddresses(): bool
    {
        $plan = $this->plan;
        if (!$plan) return false;

        return $this->addresses()->count() < $plan->max_addresses;
    }

    public function canAddMoreServices(): bool
    {
        $plan = $this->plan;
        if (!$plan) return false;

        $currentServicesCount = DB::table('service_specialty')
            ->where('user_id', $this->user_id)
            ->distinct('service_id')
            ->count();

        return $currentServicesCount < $plan->max_services;
    }
    public function canAddMoreDoctors(): bool
    {
        $plan = $this->plan;
        if (!$plan) return false;

        $maxDoctors = $plan->max_doctors ?? 5; 
        $currentDoctorsCount = $this->doctors()->count();

        return $currentDoctorsCount < $maxDoctors;
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'clinic_id');
    }

    public function gallery()
    {
        return $this->morphMany(GalleryImage::class, 'galleryable')
            ->where('active', true)
            ->orderBy('order');
    }

    public function getCountryNameAttribute()
    {
        $countries = [
            'co' => 'Colombia',
            'mx' => 'México',
            'ar' => 'Argentina',
            'us' => 'Estados Unidos',
            // ... más países
        ];
        
        return $countries[strtolower($this->country_code)] ?? 'Desconocido';
    }
}
