<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Doctor extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'user_id', // 🔥 OBLIGATORIO: Permite asociar el perfil del médico con su cuenta de usuario principal
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

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'languages' => 'json', 
        'active'    => 'boolean',
    ];

    /**
     * Crea una sede técnica de telemedicina privada solo si no existe una previa.
     */
    public function createVirtualAddress()
    {
        // 🔥 CORREGIDO: Filtramos de forma estricta que la sede virtual sea autónoma (sin clinic_id)
        $exists = $this->addresses()
            ->where('type', 'virtual')
            ->whereNull('clinic_id')
            ->exists();
        
        if ($exists) {
            return $this->addresses()->where('type', 'virtual')->whereNull('clinic_id')->first();
        }
        
        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina',
            'address'   => 'Plataforma Online',
            'type'      => 'virtual',
            'phone'     => $this->phone ?? 'N/A', 
            'city_id'   => 1, // ID numérico limpio para la relación de ciudades
            'status'    => true,
        ]);
    }

    /**
     * Relación con el usuario maestro de autenticación (para nombre y foto).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Clínicas corporativas para las cuales trabaja o presta servicios este médico.
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_doctor')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Disparadores automáticos del ciclo de vida del modelo.
     */
    protected static function booted()
    {
        static::creating(function ($doctor) {
            $name = 'doctor';
            
            if ($doctor->user) {
                $name = $doctor->user->name;
            } elseif ($doctor->user_id) {
                $name = User::find($doctor->user_id)?->name ?? 'doctor';
            }

            $cleanId = Str::slug($doctor->identification);
            $doctor->slug = Str::slug($name) . '-' . $cleanId;
        });

        static::updated(function ($doctor) {
            if ($doctor->wasChanged('phone')) {
                // 🔥 CORREGIDO: Aseguramos que solo altere el teléfono de SU sede virtual particular
                $doctor->addresses()
                    ->where('type', 'virtual')
                    ->whereNull('clinic_id')
                    ->update(['phone' => $doctor->phone]);
            }
        });
    }

    /**
     * Relación uno a uno con la configuración del médico.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(DoctorSetting::class, 'doctor_id');
    }

        /**
     * Acceso directo al plan de suscripción activo a través de su configuración.
     */
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

    /**
     * Relación muchos a muchos con el catálogo global de especialidades médicas.
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')->withTimestamps();
    }

    /**
     * Relación uno a muchos con sus consultorios y sedes autónomas privadas.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'doctor_id');
    }

    /**
     * Filtro local para motores de búsqueda avanzados por aproximación de ciudad.
     */
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
        // Cuenta únicamente las sedes privadas de su consulta autónoma
        $currentCount = $this->addresses()->whereNull('clinic_id')->count();
        return $currentCount < $limit;
    }

    /**
     * 🔥 REFACTORIZACIÓN CRÍTICA: Mapeo correcto del catálogo de servicios del médico.
     * Al ser una tabla pivote muchos a muchos (address_service), se resuelve mediante queries relacionales de Eloquent.
     */
    public function services()
    {
        $addressIds = $this->addresses()->pluck('id')->toArray();
        return Service::whereHas('addresses', function ($query) use ($addressIds) {
            $query->whereIn('address_id', $addressIds);
        });
    }

    /**
     * VERIFICACIÓN SAAS: ¿Puede añadir más servicios según su plan activo?
     */
    public function canAddMoreServices(): bool
    {
        $plan = $this->plan()->first();
        if (!$plan) return false;

        $limit = $plan->max_services ?? 0;

        // Cuenta los servicios únicos distribuidos entre todas sus sedes privadas
        $currentTotal = DB::table('address_service')
            ->whereIn('address_id', $this->addresses()->pluck('id'))
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
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    /**     
     * Relación uno a muchos con las citas médicas agendadas de forma privada.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /**
     * Relación uno a muchos con las ausencias o bloqueos de agenda del médico.
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class, 'doctor_id');
    }

    /**
     * Relación indirecta uno a muchos hacia los horarios semanales de sus sucursales.
     */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(Schedule::class, Address::class, 'doctor_id', 'address_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'doctor_id');
    }

    public function canDo($feature)
    {
        $plan = $this->settings?->plan;
        return $plan ? (bool) $plan->$feature : false;
    }

    public function expertises() 
    {
        return $this->hasMany(MedicalExpertise::class, 'doctor_id');
    }
}
