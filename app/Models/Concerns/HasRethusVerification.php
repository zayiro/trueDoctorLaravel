<?php

namespace App\Models\Concerns;

use App\Models\RethusVerificationLog;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estado de la verificación ReTHUS de un Doctor.
 * Estados guardados: none, requested, verified, mismatch, expired.
 * «Vigente» = verified y sin vencer (el vencimiento se calcula al instante; el comando diario solo avisa y lo registra).
 */
trait HasRethusVerification
{
    /** Marca interna (propiedad real, no atributo de la base) para registrar el reinicio automático. */
    protected bool $rethusAutoReset = false;

    /** Laravel lo llama solo al crear el modelo. Solo el tipo de documento es asignable en masa; el resto se escribe desde el servicio. */
    public function initializeHasRethusVerification(): void
    {
        $this->mergeFillable(['identification_type']);
        $this->mergeCasts([
            'rethus_requested_at'         => 'datetime',
            'rethus_verified_at'          => 'datetime',
            'rethus_expires_at'           => 'datetime',
            'rethus_expiring_notified_at' => 'datetime',
        ]);
    }

    public static function bootHasRethusVerification(): void
    {
        // Si cambian los datos con los que se verificó, la verificación deja de valer (hay que verificar de nuevo).
        static::updating(function ($doctor) {
            if ($doctor->getOriginal('rethus_status') === 'verified'
                && !$doctor->isDirty('rethus_status')
                && $doctor->isDirty(['identification', 'identification_type', 'medical_license'])) {
                $doctor->rethus_status = 'none';
                $doctor->rethus_verified_at = null;
                $doctor->rethus_expires_at = null;
                $doctor->rethus_confirmed_text = null;
                $doctor->rethus_expiring_notified_at = null;
                $doctor->rethusAutoReset = true;
            }
        });

        static::updated(function ($doctor) {
            if (!empty($doctor->rethusAutoReset)) {
                $doctor->rethusAutoReset = false;
                RethusVerificationLog::create([
                    'doctor_id' => $doctor->id,
                    'action'    => 'reset',
                    'note'      => 'Cambiaron el documento, el tipo de documento o la licencia: la verificación anterior dejó de valer.',
                ]);
            }
        });
    }

    public function rethusLogs(): HasMany
    {
        return $this->hasMany(RethusVerificationLog::class)->latest('id');
    }

    /** none | requested | verified | mismatch | expired */
    public function rethusState(): string
    {
        $s = (string) ($this->rethus_status ?: 'none');
        if ($s === 'verified' && $this->rethus_expires_at && $this->rethus_expires_at->isPast()) {
            return 'expired';
        }

        return in_array($s, ['none', 'requested', 'verified', 'mismatch', 'expired'], true) ? $s : 'none';
    }

    /** ¿Tiene verificación vigente? (lo que habilita el badge público y, más adelante, las incapacidades). */
    public function hasValidRethus(): bool
    {
        return $this->rethusState() === 'verified';
    }

    public function rethusExpiresSoon(): bool
    {
        $days = (int) config('rethus.expiring_notice_days', 30);

        return $this->rethusState() === 'verified'
            && $this->rethus_expires_at !== null
            && $this->rethus_expires_at->lte(now()->addDays($days));
    }

    /** ¿Puede pedir la verificación? Necesita tipo y número de documento y licencia, y no tenerla vigente ni en revisión. */
    public function canRequestRethus(): bool
    {
        return in_array($this->rethusState(), ['none', 'mismatch', 'expired'], true)
            && trim((string) $this->identification) !== ''
            && trim((string) $this->identification_type) !== ''
            && trim((string) $this->medical_license) !== '';
    }

    /** Qué le falta para poder pedirla (texto para el doctor), o null si nada. */
    public function rethusMissingForRequest(): ?string
    {
        $missing = [];
        if (trim((string) $this->identification_type) === '') { $missing[] = 'el tipo de documento'; }
        if (trim((string) $this->identification) === '') { $missing[] = 'el número de documento'; }
        if (trim((string) $this->medical_license) === '') { $missing[] = 'la licencia médica'; }

        return $missing ? 'Completa en tu perfil ' . implode(', ', $missing) . ' para poder solicitar la verificación.' : null;
    }
}
