<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Anota cuándo un paciente «volvió de verdad» para que la limpieza de cuentas sin uso no lo toque.
 * Laravel lo descubre solo (app/Listeners con handle...() tipado): no hay que registrarlo en AppServiceProvider.
 * Nunca debe romper el inicio de sesión: cualquier error se reporta y se ignora.
 */
class RecordEngagement
{
    /** Un ingreso cuenta solo si pasaron unos minutos desde crear la cuenta (la reserva inicia sesión sola al crearla). */
    public function handleLogin(Login $event): void
    {
        try {
            $user = $event->user;

            if (($user->role ?? null) !== 'patient') {
                return;
            }

            $created = $user->created_at ?? null;
            $grace = (int) config('account_cleanup.return_grace_minutes', 10);

            if ($created !== null && now()->lt(Carbon::parse($created)->addMinutes($grace))) {
                return;
            }

            $this->mark($user->getAuthIdentifier());
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Crear (o restablecer) la contraseña con el enlace del correo siempre cuenta. */
    public function handlePasswordReset(PasswordReset $event): void
    {
        try {
            if (($event->user->role ?? null) === 'patient') {
                $this->mark($event->user->getAuthIdentifier());
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function mark(mixed $id): void
    {
        DB::table('users')->where('id', $id)->update(['last_engaged_at' => now(), 'cleanup_notified_at' => null]);
    }
}
