<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Honeypot\ProtectAgainstSpam;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Protege los POST públicos que pueden crear cuentas (config/signup_protection.php → protected_paths):
 * redes bloqueadas, límite por IP y por red, y trampa honeypot con tiempo mínimo.
 * Cualquier otra petición pasa intacta. Si algo se rechaza, la persona vuelve al formulario con un mensaje amable.
 * Una cuenta cuenta para el límite SOLO si de verdad se creó un usuario (un inicio de sesión no cuenta).
 */
class ProtectSignup
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('signup_protection.enabled', true)
            || !$request->isMethod('POST')
            || !$this->isProtected($request)
            || $request->attributes->get('signup_protection_seen')) {
            return $next($request);
        }
        // Puede estar enganchado dos veces (grupo web y rutas de Fortify): solo actúa una vez por petición.
        $request->attributes->set('signup_protection_seen', true);

        $ip = (string) $request->ip();

        // 1) Redes bloqueadas
        $blocked = array_values(array_filter((array) config('signup_protection.blocked_networks', [])));
        if ($ip !== '' && $blocked && IpUtils::checkIp($ip, $blocked)) {
            return $this->refuse($request, 'blocked_network', $ip);
        }

        // 2) Límite por IP y por red (solo cuentan las cuentas creadas)
        $ipKey = 'signup:ip:' . $ip;
        $netKey = 'signup:net:' . $this->network($ip);
        if (RateLimiter::tooManyAttempts($ipKey, (int) config('signup_protection.max_per_ip_per_hour', 3))
            || RateLimiter::tooManyAttempts($netKey, (int) config('signup_protection.max_per_network_per_hour', 4))) {
            return $this->refuse($request, 'rate_limited', $ip);
        }

        // 3) Honeypot (campo trampa + tiempo mínimo). Si lo rechaza, no se llama al siguiente paso.
        $created = false;
        Event::listen('eloquent.created: ' . User::class, function () use (&$created) {
            $created = true;
        });
        $passed = false;
        $response = app(ProtectAgainstSpam::class)->handle($request, function ($req) use ($next, &$passed) {
            $passed = true;

            return $next($req);
        });
        if (!$passed) {
            return $this->refuse($request, 'honeypot', $ip);
        }

        // 4) Si se creó un usuario, cuenta para los límites
        if ($created) {
            RateLimiter::hit($ipKey, 3600);
            RateLimiter::hit($netKey, 3600);
        }

        return $response;
    }

    private function isProtected(Request $request): bool
    {
        $paths = array_values(array_filter((array) config('signup_protection.protected_paths', ['register'])));

        return $paths !== [] && $request->is(...$paths);
    }

    private function refuse(Request $request, string $reason, string $ip)
    {
        Log::warning('signup.refused', ['reason' => $reason, 'path' => $request->path(), 'ip' => $ip]);
        $message = (string) config('signup_protection.message');

        $response = redirect()->back(302, [], $request->is('register') ? route('register') : url('/'))
            ->withInput($request->except(['password', 'password_confirmation', 'login_password']))
            ->withErrors(['email' => $message]);

        // Las demás pantallas muestran los avisos con la clave «error»; el registro de Fortify, con los errores del formulario.
        return $request->is('register') ? $response : $response->with('error', $message);
    }

    /** Red de la IP: /24 en IPv4, /48 en IPv6. */
    private function network(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return implode('.', array_slice(explode('.', $ip), 0, 3)) . '.0/24';
        }
        $bin = @inet_pton($ip);

        return $bin !== false ? bin2hex(substr($bin, 0, 6)) . '::/48' : 'desconocida';
    }
}
