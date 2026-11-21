<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Cierra la sesión de una cuenta que el admin desactivó mientras estaba logueada
 * (el login ya bloquea a las cuentas desactivadas; esto cubre las sesiones abiertas).
 * Falla "abierto": si el caché o la base fallan, nadie queda bloqueado por error.
 */
class EnsureAccountIsActive
{
    public const MESSAGE = 'Tu cuenta ha sido desactivada. Contacta al administrador para más información.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$this->isActiveCached($user)) {
            try {
                Auth::logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
            } catch (Throwable) {
                // seguimos: lo importante es no dejarlo pasar
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => self::MESSAGE], 403);
            }

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }

    private function isActiveCached(User $user): bool
    {
        try {
            return (bool) Cache::remember('account_active:' . $user->id, 60, fn () => self::isActive($user));
        } catch (Throwable) {
            return true;
        }
    }

    public static function isActive(User $user): bool
    {
        return match ($user->role) {
            'patient' => (bool) ($user->patient?->active ?? true),
            'doctor'  => (bool) ($user->doctor?->active ?? true),
            'clinic'  => (bool) ($user->clinic?->active ?? true),
            default   => true,
        };
    }
}
