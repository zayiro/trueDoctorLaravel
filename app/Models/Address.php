<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Address extends Model
{
    use SoftDeletes;

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'doctor_id', 
        'clinic_id', 
        'name', 
        'address',
        'phone',
        'city_id',
        'status',
        'type',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'status'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * 🔒 SCOPE: Filtra de manera inteligente las sedes según el contexto del usuario.
     * Ideal para búsquedas globales en el panel de control.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$user) {
            return $query;
        }

        // Si es Clínica Pura: Solo ve sus sedes institucionales (Producción)
        if ($user->role === 'clinic') {
            return $query->where('clinic_id', $user->clinic->id);
        }

        // Si es Doctor: Evaluamos el switch de entorno
        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            // Caso A: Contexto Institucional de Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                return $query->where('clinic_id', $context['id']);
            }

            // Caso B: Contexto Consultorio Particular (Producción)
            return $query->where('doctor_id', $doctorProfileId)
                         ->whereNull('clinic_id');
        }

        return $query;
    }

    /**
     * Relación inversa uno a muchos con el Médico (si la sede es una consulta privada).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * La Clínica institucional dueña de este consultorio o sede técnica.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * Relación con la Ciudad / Municipio al que pertenece geográficamente la sede.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Relación uno a muchos con el cronograma de turnos y bloques horarios asignados.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'address_id'); 
    }

    /**
     * Relación muchos a muchos con el catálogo de servicios de salud autorizados para esta sede.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'address_service')
                    ->withPivot('price', 'duration')
                    ->withTimestamps();
    }

    /**
     * Relación uno a muchos con el histórico de citas y reservas registradas en este consultorio.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'address_id');
    }
}
