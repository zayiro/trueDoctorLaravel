<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'meeting_link_password',
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
     * Minutos antes del inicio en que se habilita "Entrar a la sala".
     * El profesional (doctor/clínica) entra antes que el paciente para poder iniciar la reunión.
     */
    public const ROOM_OPENS_MINUTES_BEFORE = 15;
    public const PARTNER_ROOM_OPENS_MINUTES_BEFORE = 30;

    /**
     * Acción de telemedicina que corresponde mostrar en la agenda.
     * Requiere service y address cargados (eager loading) para no generar consultas extra.
     *
     * @param string $audience 'patient' (por defecto) o 'partner' (doctor / clínica)
     *
     *   pay    → (patient) cita virtual con pago pendiente; incluye url de pago
     *   unpaid → (partner) cita virtual cuyo pago el paciente aún no completa; sin acción posible
     *   enter  → virtual, pagada y dentro de la ventana horaria (live = la cita ya empezó)
     *   wait   → virtual, pagada, pero la sala aún no abre
     *   null   → sin acción (presencial, cancelada/completada o ya terminó)
     *
     * @return array{type: string, label: string, url?: string, live?: bool}|null
     */
    public function roomAction(string $audience = 'patient'): ?array
    {
        $status = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

        if (!in_array($status, [\App\Enums\AppointmentStatus::PENDING->value, \App\Enums\AppointmentStatus::CONFIRMED->value], true)) {
            return null;
        }

        $isVirtual = ($this->service?->type === 'virtual') || ($this->address?->type === 'virtual');
        if (!$isVirtual) {
            return null;
        }

        $payment = $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : (string) $this->payment_status;

        if ($payment !== \App\Enums\PaymentStatus::PAID->value) {
            if ($audience === 'partner') {
                return [
                    'type'  => 'unpaid',
                    'label' => 'Pago pendiente del paciente',
                ];
            }

            return [
                'type'  => 'pay',
                'label' => 'Completar pago',
                'url'   => route('appointments.preview', ['id' => $this->id]),
            ];
        }

        if ($status !== \App\Enums\AppointmentStatus::CONFIRMED->value) {
            return null;
        }

        $opensBefore = $audience === 'partner'
            ? self::PARTNER_ROOM_OPENS_MINUTES_BEFORE
            : self::ROOM_OPENS_MINUTES_BEFORE;

        $tz     = config('app.timezone', 'America/Bogota');
        $start  = Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->start_time, $tz);
        $end    = $start->copy()->addMinutes((int) $this->duration);
        $opens  = $start->copy()->subMinutes($opensBefore);
        $now    = Carbon::now($tz);

        if ($now->greaterThanOrEqualTo($end)) {
            return null;
        }

        if ($now->lessThan($opens)) {
            return [
                'type'  => 'wait',
                'label' => 'Sala disponible desde las ' . $opens->format('g:i A'),
            ];
        }

        return [
            'type'  => 'enter',
            'label' => 'Entrar a la sala',
            'url'   => route('appointments.room', ['appointment' => $this->id]),
            'live'  => $now->greaterThanOrEqualTo($start),
        ];
    }

    /**
     * Cita virtual activa (pendiente/confirmada) cuyo pago aún no está confirmado.
     * No depende de la hora: solo del tipo de cita, su estado y el pago.
     */
    public function isAwaitingPayment(): bool
    {
        return ($this->roomAction('patient')['type'] ?? null) === 'pay';
    }

    /**
     * Cita activa: pendiente o confirmada.
     */
    public function isActive(): bool
    {
        $status = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

        return in_array($status, [\App\Enums\AppointmentStatus::PENDING->value, \App\Enums\AppointmentStatus::CONFIRMED->value], true);
    }

    /**
     * ¿Puede el profesional marcarla como completada?
     * Activa, ya comenzó y, si es virtual, con el pago confirmado.
     */
    public function canBeCompleted(): bool
    {
        return $this->isActive()
            && !$this->isAwaitingPayment()
            && Carbon::now(config('app.timezone', 'America/Bogota'))->greaterThanOrEqualTo($this->starts_at);
    }

    /**
     * ¿Puede reprogramarse? Activa y, si es virtual, con el pago confirmado.
     */
    public function canBeRescheduled(): bool
    {
        return $this->isActive() && !$this->isAwaitingPayment();
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
     * 🔒 MUTADOR: Encripta la contraseña de la reunión de Zoom antes de guardarla.
     * Es idempotente: si el valor ya es un payload cifrado de Laravel, no se vuelve a cifrar.
     */
    public function setMeetingLinkPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['meeting_link_password'] = null;
            return;
        }

        try {
            Crypt::decryptString($value);
            $this->attributes['meeting_link_password'] = $value; // ya estaba cifrado
        } catch (DecryptException $e) {
            $this->attributes['meeting_link_password'] = Crypt::encryptString((string) $value);
        }
    }

    /**
     * 🔓 ACCESSOR: Descifra de forma segura la contraseña de la reunión de Zoom
     */
    public function getMeetingLinkPasswordAttribute($value)
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value; // Fallback seguro si el dato es texto plano
        }
    }

    /**
     * Fecha y hora de INICIO como Carbon, ya combinadas y en la zona horaria de la app.
     *
     * Úsalo en vez de concatenar strings: `date` tiene cast a Carbon, así que
     * `$appointment->date . ' ' . $appointment->start_time` produce "2026-11-03 00:00:00 07:00:00" (inválido).
     */
    protected function startsAt(): Attribute
    {
        return Attribute::get(fn () => Carbon::parse(
            Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->start_time,
            config('app.timezone', 'America/Bogota')
        ));
    }

    /**
     * Fecha y hora de FIN como Carbon (usa end_time; si no existe, start + duration).
     */
    protected function endsAt(): Attribute
    {
        return Attribute::get(function () {
            $tz = config('app.timezone', 'America/Bogota');

            if ($this->end_time) {
                return Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->end_time, $tz);
            }

            return $this->starts_at->copy()->addMinutes((int) $this->duration);
        });
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
        // withTrashed: un servicio eliminado (SoftDeletes) debe seguir visible en el historial de citas
        return $this->belongsTo(Service::class, 'service_id')->withTrashed();
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

    /**
     * Relación 1 a 1 con la historia clínica oficial de esta cita.
     */
    public function patientHistory(): HasOne
    {
        return $this->hasOne(PatientHistory::class, 'appointment_id');
    }

    /**
     * Calcula el límite máximo (3 horas después de starts_at) para diligenciar el SOAP principal.
     */
    protected function soapDeadline(): Attribute
    {
        return Attribute::get(
            fn (): Carbon => $this->starts_at->copy()->addHours(3)
        );
    }

    /**
     * Determina si la cita ya inició y no está cancelada (habilita el enlace clínico para el médico).
     */
    public function canAccessClinicalRecord(): bool
    {
        $status = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;

        if ($status === \App\Enums\AppointmentStatus::CANCELLED->value) {
            return false;
        }

        return Carbon::now(config('app.timezone', 'America/Bogota'))->greaterThanOrEqualTo($this->starts_at);
    }

    /**
     * Determina si el médico aún está dentro de la ventana de 3 horas para llenar o editar el borrador SOAP.
     */
    public function isWithinSoapWindow(): bool
    {
        if (!$this->canAccessClinicalRecord()) {
            return false;
        }

        $now = Carbon::now(config('app.timezone', 'America/Bogota'));
        $isNotSigned = $this->patientHistory?->status !== 'signed';

        return $now->lessThanOrEqualTo($this->soap_deadline) && $isNotSigned;
    }

    /**
     * Determina si ya pasaron las 3 horas (o la historia ya está firmada),
     * habilitando únicamente el modo de notas extemporáneas (máximo 3).
     */
    public function isExtemporaneousMode(): bool
    {
        if (!$this->canAccessClinicalRecord()) {
            return false;
        }

        $now = Carbon::now(config('app.timezone', 'America/Bogota'));
        $isSigned = $this->patientHistory?->status === 'signed';

        return $now->greaterThan($this->soap_deadline) || $isSigned;
    }
}