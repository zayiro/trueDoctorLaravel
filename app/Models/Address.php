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
        'latitude',
        'longitude',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'status'     => 'boolean',
        'latitude'   => 'float',
        'longitude'  => 'float',
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
     * 🔒 SCOPE: sede reservable = activa.
     * Las sedes eliminadas (SoftDeletes) ya las excluye Eloquent en cualquier consulta
     * sobre Address o a través de una relación (whereHas), pero NO en consultas que
     * parten de otra tabla (por ejemplo, Schedule::pluck('address_id')).
     * Por eso toda búsqueda de disponibilidad debe llegar a las sedes por esta vía.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('status'), true);
    }

    /**
     * ¿La sede tiene georreferencia (latitud y longitud)?
     * Las sedes virtuales y las anteriores a la georreferencia no la tienen.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * 🔒 SCOPE: sedes dentro de un radio (km) de un punto, con la distancia en la columna `distance_km`.
     *
     * Primero acota por "caja" (usa el índice latitude/longitude) y luego calcula la distancia exacta
     * con la ley esférica de los cosenos (portable a MariaDB y MySQL). Solo incluye sedes con coordenadas.
     * Ordena de la más cercana a la más lejana.
     *
     * Ejemplo (sedes físicas reservables a ≤ 10 km):
     *   Address::bookable()->where('type', 'physical')->near($lat, $lng, 10)->get();
     *
     * Límite conocido: la caja no contempla el cruce del meridiano 180° (irrelevante en Latinoamérica).
     */
    public function scopeNear(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $model = $query->getModel();
        $lat   = $model->qualifyColumn('latitude');
        $lng   = $model->qualifyColumn('longitude');

        $kmPerDegree = 111.045;
        $latDelta    = $radiusKm / $kmPerDegree;
        $lngDelta    = $radiusKm / ($kmPerDegree * max(cos(deg2rad($latitude)), 0.0001));

        $distance = "(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS({$lat})) * COS(RADIANS({$lng}) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS({$lat})))))";

        if (empty($query->getQuery()->columns)) {
            $query->select($model->qualifyColumn('*'));
        }

        return $query
            ->whereNotNull($lat)
            ->whereNotNull($lng)
            ->whereBetween($lat, [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween($lng, [$longitude - $lngDelta, $longitude + $lngDelta])
            ->whereRaw("{$distance} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->selectRaw("{$distance} AS distance_km", [$latitude, $longitude, $latitude])
            ->orderBy('distance_km');
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