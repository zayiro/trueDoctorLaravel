<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificado electrónico emitido por un doctor a partir de una cita atendida.
 * El contenido sensible (texto, diagnóstico y datos con los que se emitió) se guarda cifrado.
 */
class Certificate extends Model
{
    public const TYPE_ATTENDANCE = 'attendance';
    public const TYPE_MEDICAL = 'medical';

    public const STATUS_ISSUED = 'issued';
    public const STATUS_REVOKED = 'revoked';

    // Sin 0, 1, I, L ni O para que nadie los confunda al copiar el código a mano.
    public const CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    protected $fillable = [
        'code', 'type', 'status',
        'appointment_id', 'doctor_id', 'patient_id', 'patient_history_id', 'signed_document_id',
        'consultation_at', 'issued_at', 'issued_by_ip', 'doctor_declaration_at',
        'body', 'diagnosis_text', 'cie10_code', 'diagnosis_authorized', 'diagnosis_authorized_at',
        'snapshot',
        'revoked_at', 'revoked_reason', 'revoked_by_user_id',
        'emailed_at',
    ];

    protected $casts = [
        'body'                    => 'encrypted',
        'diagnosis_text'          => 'encrypted',
        'snapshot'                => 'encrypted:array',
        'diagnosis_authorized'    => 'boolean',
        'consultation_at'         => 'datetime',
        'issued_at'               => 'datetime',
        'doctor_declaration_at'   => 'datetime',
        'diagnosis_authorized_at' => 'datetime',
        'revoked_at'              => 'datetime',
        'emailed_at'              => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function signedDocument(): BelongsTo
    {
        return $this->belongsTo(SignedDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function typeLabel(): string
    {
        return (string) config('certificates.types.' . $this->type . '.label', 'Certificado');
    }

    /**
     * Deja el código en su forma canónica (OD-XXXX-XXXX-XXXX) o devuelve null si no tiene forma de código.
     * Acepta minúsculas, espacios y guiones de más.
     */
    public static function normalizeCode(?string $raw): ?string
    {
        $clean = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));
        $prefix = strtoupper((string) config('certificates.code_prefix', 'OD'));

        if ($clean === '' || !str_starts_with($clean, $prefix)) {
            return null;
        }

        $rest = substr($clean, strlen($prefix));

        if (!preg_match('/^[2-9A-HJKMNP-Z]{12}$/', $rest)) {
            return null;
        }

        return $prefix . '-' . substr($rest, 0, 4) . '-' . substr($rest, 4, 4) . '-' . substr($rest, 8, 4);
    }

    /** Genera un código aleatorio con la forma OD-XXXX-XXXX-XXXX (no secuencial). */
    public static function generateCode(): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $chars = '';

        for ($i = 0; $i < 12; $i++) {
            $chars .= $alphabet[random_int(0, $max)];
        }

        return strtoupper((string) config('certificates.code_prefix', 'OD'))
            . '-' . substr($chars, 0, 4) . '-' . substr($chars, 4, 4) . '-' . substr($chars, 8, 4);
    }
}
