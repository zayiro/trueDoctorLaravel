<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Mail\AbandonedAccountWarningMail;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cuentas de paciente que nunca se usaron: se avisa por correo y, un día después, se eliminan.
 *
 * «Nunca se usó» = rol paciente, correo sin verificar, sin datos de perfil, sin historia clínica ni documentos,
 * sin compras ni pagos aprobados, sin sesiones del Chat IA pagadas y sin ninguna cita real (solo reservas
 * vencidas sin pagar), y sin «volver de verdad» (ver RecordEngagement) en los últimos días.
 * Ante cualquier duda la cuenta NO se elimina.
 */
class AbandonedAccountCleaner
{
    private const ACTIVITY = 'COALESCE(u.last_engaged_at, u.created_at)';

    /** @var array<string, bool> */
    private array $exists = [];

    /** Cuentas a las que toca avisar hoy (máximo configurado por corrida). */
    public function warnList(?int $limit = null): Collection
    {
        $limit ??= (int) config('account_cleanup.max_warnings_per_run', 30);

        $rows = $this->base()
            ->whereRaw(self::ACTIVITY . ' <= ?', [now()->subDays((int) config('account_cleanup.warn_after_days', 13))])
            ->where(fn ($w) => $w->whereNull('u.cleanup_notified_at')->orWhereRaw('u.cleanup_notified_at < ' . self::ACTIVITY))
            ->orderBy('u.id')
            ->limit($limit * 4)
            ->get($this->columns());

        // Las que fallaron hace poco se saltan unos días para no bloquear a las demás con el tope diario.
        return $rows->reject(fn ($r) => Cache::has('accounts.cleanup.failed.' . $r->id))->take($limit)->values();
    }

    /** Cuentas ya avisadas, que no volvieron y cumplen el plazo para eliminarse. */
    public function deleteList(): Collection
    {
        return $this->deleteQuery()->orderBy('u.id')->get($this->columns());
    }

    /** Envía el aviso. Solo se marca como avisada si el correo salió. */
    public function warn(object $row): bool
    {
        $user = User::find($row->id);

        if (!$user || !$user->email) {
            return false;
        }

        try {
            $url = URL::temporarySignedRoute('account.keep', now()->addDays((int) config('account_cleanup.keep_link_days', 3)), ['user' => $user->id]);
            Mail::to($user->email)->send(new AbandonedAccountWarningMail($user, $url));
        } catch (Throwable $e) {
            Cache::put('accounts.cleanup.failed.' . $user->id, 1, now()->addDays(3));
            Log::warning('accounts.cleanup.warn_failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return false;
        }

        DB::table('users')->where('id', $user->id)->update(['cleanup_notified_at' => now()]);
        Log::info('accounts.cleanup.warned', ['user_id' => $user->id]);

        return true;
    }

    /** Elimina la cuenta (vuelve a comprobar todo dentro de la transacción). */
    public function delete(int $userId): bool
    {
        try {
            return DB::transaction(function () use ($userId) {
                $row = $this->deleteQuery()->where('u.id', $userId)->first(['u.id', 'p.id as patient_id']);

                if (!$row) {
                    return false;
                }

                $ph = $this->placeholder();

                // Las reservas vencidas se conservan para auditoría, sin ningún dato de la persona.
                $detached = DB::table('appointments')->where('patient_id', $row->patient_id)
                    ->update(['patient_id' => $ph['patient_id'], 'created_by_user_id' => $ph['user_id'], 'notes' => null]);
                DB::table('appointments')->where('created_by_user_id', $userId)->update(['created_by_user_id' => $ph['user_id']]);

                $user = User::find($userId);

                if (!$user) {
                    return false;
                }

                $email = (string) $user->email;
                $this->purgeLoose($user, $email);
                $user->delete(); // el paciente y sus dependencias se van en cascada; los roles los suelta Spatie

                Log::info('accounts.cleanup.deleted', ['user_id' => $userId, 'patient_id' => $row->patient_id, 'appointments_detached' => $detached]);

                return true;
            });
        } catch (Throwable $e) {
            Log::error('accounts.cleanup.delete_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** @return array{user_id: int, patient_id: int} */
    public function placeholder(): array
    {
        $email = (string) config('account_cleanup.placeholder_email');
        $uid = DB::table('users')->where('email', $email)->value('id');

        if (!$uid) {
            $uid = DB::table('users')->insertGetId([
                'name' => (string) config('account_cleanup.placeholder_name'),
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'role' => 'patient',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pid = DB::table('patients')->where('user_id', $uid)->value('id');

        if (!$pid) {
            $pid = DB::table('patients')->insertGetId(['user_id' => $uid, 'phone' => '', 'active' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        return ['user_id' => (int) $uid, 'patient_id' => (int) $pid];
    }

    // ------------------------------------------------------------------

    private function columns(): array
    {
        return ['u.id', 'u.name', 'u.email', 'u.created_at', 'u.last_engaged_at', 'u.cleanup_notified_at'];
    }

    private function deleteQuery(): Builder
    {
        return $this->base()
            ->whereRaw(self::ACTIVITY . ' <= ?', [now()->subDays((int) config('account_cleanup.delete_after_days', 14))])
            ->whereNotNull('u.cleanup_notified_at')
            ->whereRaw('u.cleanup_notified_at >= ' . self::ACTIVITY)
            ->where('u.cleanup_notified_at', '<=', now()->subHours((int) config('account_cleanup.hours_between_warning_and_deletion', 23)));
    }

    /** Cuentas de paciente «fantasma» (sin mirar plazos). */
    private function base(): Builder
    {
        $q = DB::table('users as u')
            ->join('patients as p', 'p.user_id', '=', 'u.id')
            ->where('u.role', 'patient')
            ->whereNull('u.email_verified_at')
            ->where('u.email', '!=', (string) config('account_cleanup.placeholder_email'));

        // «Perfil vacío» = cada dato sigue en su valor inicial (NULL, vacío o el valor por defecto de la columna).
        $defaults = $this->patientDefaults();
        foreach ((array) config('account_cleanup.profile_columns', []) as $col) {
            if ($this->has('patients', $col)) {
                $def = $defaults[$col] ?? null;
                $q->where(function ($w) use ($col, $def) {
                    $w->whereNull("p.$col")->orWhere("p.$col", '');
                    if ($def !== null && $def !== '') {
                        $w->orWhere("p.$col", $def);
                    }
                });
            }
        }

        // Datos del paciente que se perderían en cascada o que están protegidos por la base de datos.
        foreach (['affected_appointments', 'certificates', 'conversations', 'patient_access_logs', 'patient_allergies', 'patient_consents',
            'patient_family_histories', 'patient_histories', 'patient_history_attachments', 'patient_medications', 'patient_surgeries', 'signed_documents'] as $t) {
            $this->noRows($q, $t, 'patient_id', 'p.id');
        }

        // Cosas que cuelgan del usuario.
        foreach ([['exam_analyses', 'user_id'], ['reviews', 'user_id'], ['billing_profiles', 'user_id'], ['patient_access_logs', 'user_id'],
            ['messages', 'sender_id'], ['appointment_files', 'uploaded_by'], ['clinics', 'user_id'], ['doctors', 'user_id'],
            ['subscriptions', 'doctor_id'], ['service_specialty', 'user_id']] as [$t, $c]) {
            $this->noRows($q, $t, $c, 'u.id');
        }

        if ($this->has('ai_chat_sessions', 'user_id')) {
            $q->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('ai_chat_sessions as s')->whereColumn('s.user_id', 'u.id')
                ->where('s.status', '!=', AiChatSession::STATUS_AWAITING_PAYMENT));
        }

        if ($this->has('payments', 'payer_id')) {
            $q->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('payments as pay')->whereColumn('pay.payer_id', 'u.id')->where('pay.status', 'approved'));
        }

        if ($this->has('promo_codes', 'user_id')) {
            $q->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('promo_codes as pc')->whereColumn('pc.user_id', 'u.id')->where('pc.uses', '>', 0));
        }

        // Citas: solo se admiten reservas vencidas, sin pagar, sin pagos aprobados, pagos a doctores ni devoluciones.
        $morph = (new Appointment)->getMorphClass();
        $payouts = $this->has('doctor_payouts', 'appointment_id');
        $refunds = $this->has('payment_refunds', 'appointment_id');

        $q->whereNotExists(function ($s) use ($morph, $payouts, $refunds) {
            $s->select(DB::raw(1))->from('appointments as a')->whereColumn('a.patient_id', 'p.id')
                ->where(function ($w) use ($morph, $payouts, $refunds) {
                    $w->where('a.status', '!=', 'expired')
                        ->orWhereNotNull('a.paid_at')
                        ->orWhere('a.payment_status', 'paid')
                        ->orWhereExists(fn ($e) => $e->select(DB::raw(1))->from('payments as pm')->whereColumn('pm.payable_id', 'a.id')
                            ->where('pm.payable_type', $morph)->where('pm.status', 'approved'));
                    if ($payouts) {
                        $w->orWhereExists(fn ($e) => $e->select(DB::raw(1))->from('doctor_payouts as dp')->whereColumn('dp.appointment_id', 'a.id'));
                    }
                    if ($refunds) {
                        $w->orWhereExists(fn ($e) => $e->select(DB::raw(1))->from('payment_refunds as pr')->whereColumn('pr.appointment_id', 'a.id'));
                    }
                });
        });

        // Citas que este usuario creó para OTRO paciente.
        $q->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('appointments as a2')->whereColumn('a2.created_by_user_id', 'u.id')->whereColumn('a2.patient_id', '!=', 'p.id'));

        return $q;
    }

    /** @var array<string, ?string>|null */
    private ?array $defaults = null;

    /** Valor por defecto de cada columna de patients (null = sin valor por defecto), tomado de la base de datos. */
    private function patientDefaults(): array
    {
        if ($this->defaults !== null) {
            return $this->defaults;
        }

        $out = [];

        foreach (DB::select('select COLUMN_NAME c, COLUMN_DEFAULT d from information_schema.COLUMNS where TABLE_SCHEMA = ? and TABLE_NAME = ?', [DB::getDatabaseName(), 'patients']) as $r) {
            $d = $r->d;

            if ($d !== null) {
                $d = (string) $d;

                if (strcasecmp($d, 'NULL') === 0) {
                    $d = null;
                } elseif (strlen($d) >= 2 && $d[0] === "'" && substr($d, -1) === "'") {
                    $d = str_replace(["\\'", "''"], "'", substr($d, 1, -1));
                }
            }

            $out[$r->c] = $d;
        }

        return $this->defaults = $out;
    }

    private function noRows(Builder $q, string $table, string $col, string $ref): void
    {
        if ($this->has($table, $col)) {
            $q->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from($table)->whereColumn("$table.$col", $ref));
        }
    }

    private function has(string $table, string $column): bool
    {
        $k = $table . '.' . $column;

        return $this->exists[$k] ??= Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    /** Restos sin llave foránea: tokens de restablecer contraseña, notificaciones y tokens de API. */
    private function purgeLoose(User $user, string $email): void
    {
        $table = (string) config('auth.passwords.' . config('auth.defaults.passwords', 'users') . '.table', 'password_reset_tokens');

        if (Schema::hasTable($table)) {
            DB::table($table)->where('email', $email)->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->where('tokenable_type', $user->getMorphClass())->where('tokenable_id', $user->id)->delete();
        }
    }
}
