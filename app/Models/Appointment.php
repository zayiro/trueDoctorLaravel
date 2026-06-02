<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class Appointment extends Model
{
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
        'status',
        'channel',
        'meeting_link',     // Enlace para el paciente (o fallback interno)
        'zoom_meeting_id',  // ID identificador de Zoom
        'zoom_start_url',   // Enlace de inicio para el Doctor
        'notes',
        'email_sent',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     * Garantiza que la fecha sea un objeto Carbon y el precio mantenga precisión flotante.
     */
    protected $casts = [
        'date'  => 'date',
        'price' => 'float',
    ];

    protected static function booted()
    {
        static::creating(function ($appointment) {            
            do {
                $prefix = Carbon::now()->format('ymdH');                
                $random = strtoupper(Str::random(3));                                
                $code = $prefix . "-" . $random;
            } while (self::where('reference', $code)->exists()); // Evita duplicados

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
     * Descifra de forma segura el enlace de inicio del doctor
     */
    public function getZoomStartUrlAttribute($value)
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // 💡 Si falla porque era texto plano viejo, retorna el valor original sin romper la app
            return $value; 
        }
    }

    /**
     * Descifra de forma segura el enlace de acceso del paciente
     */
    public function getMeetingLinkAttribute($value)
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // 💡 Si falla porque era texto plano viejo, retorna el valor original sin romper la app
            return $value; 
        }
    }

    /**
     * 🔥 NUEVA RELACIÓN: La Clínica institucional donde el paciente agendó su espacio.
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

    // Relación con el Paciente (Usuario)
    public function user()
    {
        return $this->belongsTo(User::class);
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
}
