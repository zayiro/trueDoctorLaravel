<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Appointment extends Model
{
    use SoftDeletes; // 🔒 CRÍTICO: Las citas médicas nunca se borran físicamente, solo se marcan como eliminadas para auditoría.

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'reference',
        'patient_id',
        'doctor_id',
        'clinic_id', 
        'service_id',
        'address_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'price',
        'promo_code',
        'discount_amount',
        'status',
        'payment_status',
        'wompi_reference',
        'commission_amount',
        'doctor_amount',
        'platform_amount',
        'paid_at',
        'channel',
        'meeting_link',
        'zoom_meeting_id',
        'zoom_start_url',
        'zoom_started',
        'notes',
        'email_sent',
        'reschedule_count',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'date'             => 'date',
        'start_time'       => 'string', // 🔒 Asegura que siempre se maneje como hora válida
        'end_time'         => 'string',
        'duration'         => 'integer',        // 🔒 Evita tener que hacer (int) en el controlador
        'price'            => 'float',
        'status'           => \App\Enums\AppointmentStatus::class,
        'payment_status'   => \App\Enums\PaymentStatus::class,
        'reschedule_count' => 'integer',
        'paid_at'          => 'datetime',
        'zoom_started'     => 'boolean',
        'email_sent'       => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($appointment) {            
            do {
                // Usa la zona horaria del negocio para el prefijo
                $prefix = Carbon::now(config('app.timezone', 'America/Bogota'))->format('ymdH');                
                $random = strtoupper(Str::random(3));                                
                $code = $prefix . "-" . $random;
            } while (self::withTrashed()->where('reference', $code)->exists()); // Verifica incluso en soft deletes

            $appointment->reference = $code;
        });
    }

    /**
     * Determina si la cita actual cuenta con una videollamada de Zoom activa.
     */
    public function hasZoom(): bool
    {
        return !is_null($this->zoom_meeting_id);
    }

    /**
     * 🔒 MUTADOR: Encripta el enlace de inicio del doctor antes de guardarlo
     */
    public function setZoomStartUrlAttribute($value)
    {
        $this->attributes['zoom_start_url'] = $value ? Crypt::encryptString($value) : null;
    }
    
    /**
     * 🔓 ACCESSOR: Descifra de forma segura el enlace de inicio del doctor
     */
    public function getZoomStartUrlAttribute($value)
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value; // Fallback seguro si el dato es texto plano viejo
        }
    }

    /**
     * 🔒 MUTADOR: Encripta el enlace del paciente antes de guardarlo
     */
    public function setMeetingLinkAttribute($value)
    {
        $this->attributes['meeting_link'] = $value ? Crypt::encryptString($value) : null;
    }
    
    /**
     * 🔓 ACCESSOR: Descifra de forma segura el enlace de acceso del paciente
     */
    public function getMeetingLinkAttribute($value)
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value; // Fallback seguro
        }
    }

    /**
     * 🔥 RELACIÓN: La Clínica institucional donde el paciente agendó su espacio.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }    

    /**
     * Relación con el Servicio médico contratado.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Relación con la Sede física o consultorio virtual donde ocurre la cita.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * Relación con el Especialista Médico que atenderá la consulta.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación con el Paciente dueño de la reserva.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * 🔒 CORRECCIÓN CRÍTICA: Relación correcta al User del paciente a través de la tabla patients.
     * (Antes era un belongsTo(User::class) que fallaba porque no existe user_id en appointments).
     */
    public function patientUser(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Patient::class,
            'id',       // Foreign key on patients table (patient_id en appointments apunta a esto)
            'id',       // Foreign key on users table
            'patient_id', // Local key on appointments table
            'user_id'     // Local key on patients table
        );
    }

    /**
     * 📋 RELACIÓN CON EVENTOS: Event Sourcing
     * Obtiene todos los eventos registrados para esta cita
     */
    public function events(): HasMany
    {
        return $this->hasMany(\App\Models\AppointmentEvent::class, 'appointment_id');
    }

    /**
     * Relación con archivos adjuntos de la cita (Zoom, etc.)
     */
    public function files(): HasMany
    { 
        return $this->hasMany(AppointmentFile::class, 'appointment_id'); 
    }

    /**
     * Obtiene el estado de la cita con la primera letra en mayúscula (Attribute).
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => ucfirst($this->status->value));
    }

    /**
     * Scope local para segmentar las citas según el entorno multi-tenant actual.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = auth()->user();
        $context = session('doctor_context');

        if (!$context) {
            return $query;
        }

        if ($context['type'] === 'particular') {
            return $query->where('doctor_id', $user->doctor->id)->whereNull('clinic_id');
        }

        if ($context['type'] === 'clinic') {
            return $query->where('clinic_id', $context['id']);
        }

        return $query;
    }
}