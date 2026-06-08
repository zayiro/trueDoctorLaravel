<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Service extends Model
{
    protected $touches = ['addresses'];
    
    protected $fillable = [
        'name', 
        'price', 
        'type', 
        'duration',
        'active'
    ];    

    /**
     * 🔒 SCOPE: Filtra los servicios habilitados comercialmente según el espacio de trabajo activo.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$user) {
            return $query;
        }

        // Si es Clínica: Servicios del catálogo propio institucional (Producción)
        if ($user->role === 'clinic') {
            return $query->whereHas('specialties', function ($q) use ($user) {
                $q->where('service_specialty.user_id', $user->id);
            });
        }

        // Si es Doctor: Evaluamos el contexto del entorno
        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            // Caso A: Contexto Institucional de Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinicId = (int)$context['id'];

                return $query->whereHas('specialties', function ($q) use ($clinicId, $doctorProfileId) {
                    // 1. Buscamos el user_id de la clínica aliada dueña de la vinculación comercial
                    $clinicUserId = DB::table('clinics')->where('id', $clinicId)->value('user_id');
                    
                    // 2. Buscamos las especialidades que el médico y la clínica comparten
                    $sharedSpecialtyIds = DB::table('doctor_specialty')
                        ->where('doctor_id', $doctorProfileId)
                        ->whereIn('specialty_id', function($subQuery) use ($clinicId) {
                            $subQuery->select('specialty_id')->from('clinic_specialty')->where('clinic_id', $clinicId);
                        })
                        ->pluck('specialty_id')
                        ->toArray();

                    // 3. Forzamos el aislamiento estricto por Tenant y Especialidad Médica Compartida
                    $q->where('service_specialty.user_id', $clinicUserId)
                      ->whereIn('service_specialty.specialty_id', $sharedSpecialtyIds);
                });
            }

            // Caso B: Contexto Consultorio Particular (Producción estándar)
            return $query->whereHas('specialties', function ($q) use ($user) {
                $q->where('service_specialty.user_id', $user->id);
            });
        }

        return $query;
    }
    /**
     * Relación Muchos a Muchos con Especialidades aislada por el Tenant logueado.
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'service_specialty')
                    ->withPivot('user_id')
                    ->withTimestamps();
    }

    /**
     * Sedes físicas o virtuales en las cuales se presta este servicio.
     * Inyecta explícitamente las columnas transaccionales de la tabla intermedia.
     */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_service')
                    ->withPivot(['price', 'duration'])
                    ->withTimestamps();
    }

    /**
     * Un servicio tiene muchas citas médicas registradas en el SaaS.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
