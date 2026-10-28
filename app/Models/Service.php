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

    /**
     * 🔒 CORRECCIÓN: 'price' y 'duration' NO existen en la tabla services.
     * Viven en la tabla pivote 'address_service' (relación addresses()).
     */
    protected $fillable = [
        'name',
        'type',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
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

        if ($user->role === 'clinic') {
            return $query->whereHas('specialties', function ($q) use ($user) {
                $q->where('service_specialty.user_id', $user->id);
            });
        }

        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinicId = (int) $context['id'];
                return $query->whereHas('specialties', function ($q) use ($clinicId, $doctorProfileId) {
                    $clinicUserId = DB::table('clinics')->where('id', $clinicId)->value('user_id');
                    $sharedSpecialtyIds = DB::table('doctor_specialty')
                        ->where('doctor_id', $doctorProfileId)
                        ->whereIn('specialty_id', function ($subQuery) use ($clinicId) {
                            $subQuery->select('specialty_id')->from('clinic_specialty')->where('clinic_id', $clinicId);
                        })
                        ->pluck('specialty_id')
                        ->toArray();

                    $q->where('service_specialty.user_id', $clinicUserId)
                      ->whereIn('service_specialty.specialty_id', $sharedSpecialtyIds);
                });
            }

            return $query->whereHas('specialties', function ($q) use ($user) {
                $q->where('service_specialty.user_id', $user->id);
            });
        }

        return $query;
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'service_specialty')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_service')
            ->withPivot(['price', 'duration'])
            ->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}