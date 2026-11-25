<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Registro de la aceptación de Términos de Servicio y autorización de tratamiento de datos.
 *
 * VERSION: súbela (por ejemplo a la fecha del cambio) cada vez que cambien los textos legales
 * (terms.blade.php / privacy.blade.php). Así se sabe qué versión aceptó cada persona.
 */
class TermsAcceptance
{
    public const VERSION = '2026-11-25';

    /** Guarda fecha y versión. Nunca rompe el registro: si algo falla, solo deja un aviso en el log. */
    public static function record($user): void
    {
        try {
            $user->forceFill([
                'terms_accepted_at' => now(),
                'terms_version'     => self::VERSION,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('terms.record_failed', ['user_id' => $user->id ?? null, 'message' => $e->getMessage()]);
        }
    }
}
