<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Unavailability extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'doctor_id',
        'address_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * 🔒 SCOPE: Filtra las ausencias y bloqueos programados según el espacio de trabajo activo.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$user) {
            return $query;
        }

        // 1. Si es Clínica Pura: Captura ausencias de sedes de su propiedad (Producción)
        if ($user->role === 'clinic') {
            return $query->whereHas('address', function ($q) use ($user) {
                $q->where('clinic_id', $user->clinic->id);
            });
        }

        // 2. Si es Doctor: Evaluamos su conmutador de entorno activo
        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            // Caso A: Contexto Institucional de Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinicId = (int)$context['id'];
                
                return $query->where('doctor_id', $doctorProfileId)
                             ->whereHas('address', function ($q) use ($clinicId) {
                                 $q->where('clinic_id', $clinicId);
                             });
            }

            // Caso B: Contexto Consultorio Particular (Producción estándar)
            return $query->where('doctor_id', $doctorProfileId)
                         ->where(function ($q) {
                             $q->whereNull('address_id') // Bloqueos globales propios
                               ->orWhereHas('address', function ($subQ) {
                                   $subQ->whereNull('clinic_id'); // Bloqueos en sedes privadas
                               });
                         });
        }

        return $query;
    }

    /**
     * Relación inversa con el médico especialista asignado.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación inversa con la sede o consultorio donde aplica el bloqueo temporal.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
}
