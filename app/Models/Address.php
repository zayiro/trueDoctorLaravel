<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'doctor_id', 
        'clinic_id', // 🔥 AGREGADO: Indispensable para habilitar las sedes institucionales en el SaaS
        'name', 
        'address',
        'phone',
        'city_id',
        'status',
        'type',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     * 🔥 REEMPLAZO SEGURO: Mapeamos deleted_at aquí eliminando la propiedad obsoleta $dates
     */
    protected $casts = [
        'status'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación inversa uno a muchos con el Médico (si la sede es una consulta privada).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * 🔥 NUEVA RELACIÓN: La Clínica institucional dueña de este consultorio o sede técnica.
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
