<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Doctor extends Model
{
    protected $fillable = [
        'slug', 'user_id', 'medical_license', 'phone',
        'experience_years', 'languages', 'bio', 'rating',
        'reviews_count', 'identification', 'gender',
        'validation_status', 'identity_card_path',
        'professional_card_path', 'active'
        // NOTA: 'plan_id' NO está en fillable para evitar manipulación desde el frontend
    ];

    protected $casts = [
        'languages' => 'json', 
        'active'    => 'boolean',
    ];
    
    public function createVirtualAddress()
    {
        $exists = $this->addresses()
            ->where('type', 'virtual')
            ->whereNull('clinic_id')
            ->exists();
        
        if ($exists) {
            return $this->addresses()->where('type', 'virtual')->whereNull('clinic_id')->first();
        }

        $firstCity = City::first();
        
        if (!$firstCity) {
            abort(500, 'Error: Debe inicializar las ciudades antes de generar sedes virtuales.');
        }
        
        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina',
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
        static::creating(function ($doctor) {
            $name = 'doctor';
            
            if ($doctor->user) {
                $name = $doctor->user->name;
            } elseif ($doctor->user_id) {
                $name = User::find($doctor->user_id)?->name ?? 'doctor';
            }
            
            do {
                $code = Str::slug($name) . '-' . strtoupper(Str::random(5));
            } while (self::where('slug', $code)->exists());

            $doctor->slug = $code; 
        });

        static::updated(function ($doctor) {
            if ($doctor->wasChanged('phone')) {
                $doctor->addresses()
                    ->where('type', 'virtual')
                    ->whereNull('clinic_id')
                    ->update(['phone' => $doctor->phone]);
            }
        });
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_doctor')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(DoctorSetting::class, 'doctor_id');
    }

    public function plan(): HasOneThrough
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
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'doctor_id');
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
     * Busca un médico por su número de cédula/documento.
     * Utilizado en el flujo de invitación de médicos a clínicas.
     * 
     * Ejemplo:
     * $doctor = Doctor::byIdentification('1234567890')->first();
     */
    public function scopeByIdentification($query, string $identification)
    {
        $cleanIdentification = str_replace('-', '', $identification);
        return $query->where('identification', $cleanIdentification);
    }
    public function canAddMoreAddresses(): bool
    {
        $plan = $this->plan;
        if (!$plan) return true; // Resiliencia para el Staff

        $limit = $plan->max_addresses ?? 0;
        $currentCount = $this->addresses()->whereNull('clinic_id')->count();
        return $currentCount < $limit;
    }

    public function services()
    {
        return Service::whereHas('specialties', function ($query) {
            $query->where('service_specialty.user_id', $this->user_id);
        });
    }

    public function canAddMoreServices(): bool
    {
        $plan = $this->plan;
        if (!$plan) return true; // Resiliencia para el Staff

        $limit = $plan->max_services ?? 0;

        $currentTotal = DB::table('service_specialty')
            ->where('user_id', $this->user_id)
            ->distinct('service_id')
            ->count();

        return $currentTotal < $limit;
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->exists() ? round($this->reviews()->avg('rating'), 1) : 0.0;
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class, 'doctor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'doctor_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'doctor_id');
    }

    public function canDo($feature)
    {
        $plan = $this->plan;
        return $plan ? (bool) $plan->$feature : false;
    }

    public function expertises() 
    {
        return $this->hasMany(MedicalExpertise::class, 'doctor_id');
    }

    public function gallery()
    {
        return $this->morphMany(GalleryImage::class, 'galleryable')
            ->where('active', true)
            ->orderBy('order');
    }
}
