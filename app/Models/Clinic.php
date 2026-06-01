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

class Clinic extends Model {
    protected $fillable = [
        'slug',
        'user_id', 
        'slug',
        'name', 
        'nit', 
        'reps_code',
        'phone', 
        'bio',
        'experience_years',
        'rating', 
        'reviews_count',
        'validation_status',
        'identity_card_path',
        'reps_code_card_path',
        'active'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos de PHP.
     * Mantiene la precisión decimal del rating y el tipo booleano del estado activo.
     */
    protected $casts = [
        'active'        => 'boolean',
        'rating'        => 'float',
        'reviews_count' => 'integer',
    ];

    /**
     * Crea una sede técnica de telemedicina institucional solo si no existe una previa.
     */
    public function createVirtualAddress()
    {
        // BLINDAJE ANTI-DUPLICADOS INSTITUCIONALES
        $exists = $this->addresses()->where('type', 'virtual')->exists();
        
        if ($exists) {
            return $this->addresses()->where('type', 'virtual')->first();
        }

        // Buscamos de forma segura la primera ciudad cargada en el SaaS
        $firstCity = City::first();
        $cityId = $firstCity ? $firstCity->id : null;

        return $this->addresses()->create([
            'name'      => 'Atención Virtual / Telemedicina (Institucional)',
            'address'   => 'Plataforma Online',
            'type'      => 'virtual',
            'phone'     => $this->phone ?? 'N/A', 
            'city_id'   => $cityId, 
            'status'    => true,
        ]);
    }

    /**
     * Relación con el usuario administrador dueño de la clínica.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

        /**
     * Disparadores automáticos del ciclo de vida del modelo corporativo (Model Booting).
     */
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
                $code = Str::slug($name) . '-' . strtoupper(Str::random(4));
            } while (self::where('slug', $code)->exists()); // Evita duplicados en el ecosistema

            // 🔒 SOLUCIÓN DE RAÍZ: Asignar el código generado a la propiedad física del modelo
            $clinic->slug = $code; 
        });

        static::updated(function ($clinic) {
            if ($clinic->wasChanged('phone')) {
                // 🔒 SEGURIDAD MULTI-TENANT: Sincroniza el teléfono EXCLUSIVAMENTE en la dirección virtual de la clínica.
                // Evitamos alterar los registros de los doctores independientes adscritos (doctor_id debe ser null)
                $clinic->addresses()
                    ->where('type', 'virtual')
                    ->whereNull('doctor_id') 
                    ->update(['phone' => $clinic->phone]);
            }
        });
    }

    /**
     * Relación uno a uno con la configuración de la clínica.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(ClinicSetting::class, 'clinic_id');
    }

    /**
     * 🔥 CORREGIDO: Retorno correcto del tipo de relación HasOneThrough
     */
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

        /**
     * Atributo para obtener el promedio de calificación fácilmente.
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->exists() ? round($this->reviews()->avg('rating'), 1) : 0.0;
    }

    /**
     * Sedes o consultorios físicos y virtuales creados por la clínica.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'clinic_id');
    }

    /**
     * Médicos profesionales vinculados a la nómina de la clínica.
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'clinic_doctor')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    /**
     * 🔥 REFACTORIZACIÓN CRÍTICA: Mapeo correcto del catálogo de servicios de la clínica.
     * Extrae de forma limpia los servicios únicos distribuidos en la infraestructura institucional.
     */
    public function services() 
    {
        $addressIds = $this->addresses()->pluck('id')->toArray();
        return Service::whereHas('addresses', function ($query) use ($addressIds) {
            $query->whereIn('address_id', $addressIds);
        });
    }

    /**
     * Especialidades médicas activas en el centro médico.
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'clinic_specialty')->withTimestamps();
    }

    /**
     * Calificaciones y testimonios de pacientes (Polimorfismo).
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Campañas de marketing institucional de la clínica.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'clinic_id');
    }

    /**
     * VERIFICACIÓN SAAS: ¿Puede añadir más sedes según su plan activo?
     */
    public function canAddMoreAddresses(): bool
    {
        $plan = $this->plan()->first();
        if (!$plan) return false;

        return $this->addresses()->count() < $plan->max_addresses;
    }

    /**
     * VERIFICACIÓN SAAS: ¿Puede añadir más servicios según su plan activo?
     */
    public function canAddMoreServices(): bool
    {
        $plan = $this->plan()->first();
        if (!$plan) return false;

        // Cuenta los servicios únicos distribuidos entre todas sus sedes
        $currentServicesCount = DB::table('address_service')
            ->whereIn('address_id', $this->addresses()->pluck('id'))
            ->distinct('service_id')
            ->count();

        return $currentServicesCount < $plan->max_services;
    }

        /**
     * VERIFICACIÓN SAAS: ¿Puede añadir más médicos a su nómina según su plan activo?
     * Mapeo inteligente basado en el slug del plan actual para evitar alterar migraciones.
     */
    public function canAddMoreDoctors(): bool
    {
        $plan = $this->plan()->first();
        if (!$plan) {
            return false;
        }

        // Definimos los límites directamente por el slug del plan
        $limits = [
            'free'    => 1,   // El plan básico solo permite el médico administrador
            'premium' => 5,   // Capacidad intermedia para clínicas pequeñas
            'gold'    => 50,  // Capacidad extendida para centros médicos grandes
        ];

        // Obtenemos el límite asignado al slug (por defecto 1 si no coincide)
        $maxDoctors = $limits[$plan->slug] ?? 1;

        // Contamos la nómina actual (tanto pendientes como aprobados)
        $currentDoctorsCount = $this->doctors()->count();

        return $currentDoctorsCount < $maxDoctors;
    }
}
