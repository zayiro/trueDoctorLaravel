<?php

declare(strict_types=1);

namespace App\Services\Patients;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registro de accesos a la ficha de un paciente (quién, a quién, cuándo y qué hizo).
 * Nunca interrumpe el flujo: si falla, solo deja un aviso en el log.
 */
class PatientAccessLog
{
    /** Abrir la ficha varias veces seguidas se registra una sola vez dentro de esta ventana. */
    public const VIEW_WINDOW_MINUTES = 10;

    public static function currentAction(): string
    {
        try {
            return (string) (request()->route()?->getName() ?: 'consola');
        } catch (\Throwable $e) {
            return 'consola';
        }
    }

    public static function record(int $patientId, ?string $action = null, ?int $doctorId = null, ?int $clinicId = null): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return;
            }
            $action = $action ?: self::currentAction();

            if ($action === 'partner.patients.show') {
                $recent = DB::table('patient_access_logs')
                    ->where('user_id', $user->id)->where('patient_id', $patientId)->where('action', $action)
                    ->where('created_at', '>=', now()->subMinutes(self::VIEW_WINDOW_MINUTES))
                    ->exists();
                if ($recent) {
                    return;
                }
            }

            DB::table('patient_access_logs')->insert([
                'user_id'    => $user->id,
                'doctor_id'  => $doctorId,
                'clinic_id'  => $clinicId,
                'patient_id' => $patientId,
                'action'     => substr($action, 0, 80),
                'ip'         => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255) ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('patient_access_logs: no se pudo registrar el acceso', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
        }
    }
}
