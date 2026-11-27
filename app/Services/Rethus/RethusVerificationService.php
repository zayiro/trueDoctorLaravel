<?php

namespace App\Services\Rethus;

use App\Mail\RethusStatusMail;
use App\Models\Doctor;
use App\Models\RethusVerificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reglas de la verificación ReTHUS: pedirla (doctor), verificar o rechazar (administrador) y vencimientos.
 * La consulta oficial la hace una persona (ReTHUS exige captcha); aquí solo queda el resultado y su bitácora.
 */
class RethusVerificationService
{
    /** El doctor pide la verificación. @return array{ok: bool, message: string} */
    public function request(Doctor $doctor): array
    {
        if ($missing = $doctor->rethusMissingForRequest()) {
            return ['ok' => false, 'message' => $missing];
        }
        if (!$doctor->canRequestRethus()) {
            $state = $doctor->rethusState();

            return ['ok' => false, 'message' => $state === 'requested'
                ? 'Ya recibimos tu solicitud: está en revisión.'
                : 'Tu registro ya está verificado y vigente.'];
        }

        DB::transaction(function () use ($doctor) {
            $doctor->forceFill([
                'rethus_status'          => 'requested',
                'rethus_requested_at'    => now(),
                'rethus_mismatch_reason' => null,
            ])->save();
            $this->log($doctor, 'requested', null);
        });

        return ['ok' => true, 'message' => 'Recibimos tu solicitud. Verificaremos tu registro en ReTHUS y te avisaremos por correo.'];
    }

    /** El administrador confirma que el registro aparece activo en ReTHUS. */
    public function verify(Doctor $doctor, ?int $adminUserId, string $confirmedText, ?string $note = null): void
    {
        DB::transaction(function () use ($doctor, $adminUserId, $confirmedText, $note) {
            $doctor->forceFill([
                'rethus_status'               => 'verified',
                'rethus_verified_at'          => now(),
                'rethus_expires_at'           => now()->addMonths((int) config('rethus.validity_months', 12)),
                'rethus_verified_by_user_id'  => $adminUserId,
                'rethus_confirmed_text'       => mb_substr(trim($confirmedText), 0, 255),
                'rethus_note'                 => $note !== null && trim($note) !== '' ? trim($note) : null,
                'rethus_mismatch_reason'      => null,
                'rethus_expiring_notified_at' => null,
            ])->save();
            $this->log($doctor, 'verified', $adminUserId, $confirmedText, $note);
        });

        $this->notify($doctor, 'verified');
    }

    /** El administrador marca que no coincide (la verificación queda sin efecto y el badge se oculta). */
    public function mismatch(Doctor $doctor, ?int $adminUserId, string $reason, ?string $note = null): void
    {
        if (!array_key_exists($reason, (array) config('rethus.mismatch_reasons', []))) {
            $reason = 'data_error';
        }

        DB::transaction(function () use ($doctor, $adminUserId, $reason, $note) {
            $doctor->forceFill([
                'rethus_status'               => 'mismatch',
                'rethus_verified_at'          => null,
                'rethus_expires_at'           => null,
                'rethus_verified_by_user_id'  => $adminUserId,
                'rethus_confirmed_text'       => null,
                'rethus_note'                 => $note !== null && trim($note) !== '' ? trim($note) : null,
                'rethus_mismatch_reason'      => $reason,
                'rethus_expiring_notified_at' => null,
            ])->save();
            $this->log($doctor, 'mismatch', $adminUserId, null, trim($reason . ' ' . (string) $note));
        });

        $this->notify($doctor, 'mismatch');
    }

    /**
     * Tarea diaria: marca como vencidas las verificaciones que ya pasaron su fecha (y avisa)
     * y avisa una sola vez a quienes vencen pronto.
     * @return array{expired: int, expiring: int}
     */
    public function runDailyChecks(): array
    {
        $expired = 0; $expiring = 0;

        Doctor::query()->where('rethus_status', 'verified')->whereNotNull('rethus_expires_at')->where('rethus_expires_at', '<', now())
            ->with('user')->get()->each(function (Doctor $d) use (&$expired) {
                $d->forceFill(['rethus_status' => 'expired'])->save();
                $this->log($d, 'expired', null, null, 'Venció la verificación.');
                $this->notify($d, 'expired');
                $expired++;
            });

        $days = (int) config('rethus.expiring_notice_days', 30);
        Doctor::query()->where('rethus_status', 'verified')->whereNotNull('rethus_expires_at')
            ->where('rethus_expires_at', '>=', now())->where('rethus_expires_at', '<=', now()->addDays($days))
            ->whereNull('rethus_expiring_notified_at')->with('user')->get()->each(function (Doctor $d) use (&$expiring) {
                $d->forceFill(['rethus_expiring_notified_at' => now()])->save();
                $this->notify($d, 'expiring');
                $expiring++;
            });

        return ['expired' => $expired, 'expiring' => $expiring];
    }

    private function log(Doctor $doctor, string $action, ?int $byUserId, ?string $confirmed = null, ?string $note = null): void
    {
        RethusVerificationLog::create([
            'doctor_id'      => $doctor->id,
            'action'         => $action,
            'by_user_id'     => $byUserId,
            'confirmed_text' => $confirmed !== null ? mb_substr($confirmed, 0, 255) : null,
            'note'           => $note,
        ]);
    }

    /** El correo nunca debe deshacer ni frenar la verificación. */
    public function notify(Doctor $doctor, string $kind): bool
    {
        try {
            $email = trim((string) ($doctor->user?->email ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            Mail::to($email)->send(new RethusStatusMail($doctor, $kind));

            return true;
        } catch (\Throwable $e) {
            Log::warning('rethus.mail.failed', ['doctor_id' => $doctor->id, 'kind' => $kind, 'error' => substr($e->getMessage(), 0, 300)]);

            return false;
        }
    }
}
