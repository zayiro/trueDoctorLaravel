<?php

// app/Http/Middleware/BlockSqlInjection.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BlockSqlInjection
{
    private array $patterns = [
        '/extractvalue\s*\(/i',
        '/updatexml\s*\(/i',
        '/union\s+select/i',
        '/select\s+.+\s+from/i',
        '/insert\s+into/i',
        '/drop\s+table/i',
        '/exec\s*\(/i',
        '/0x[0-9a-f]{4,}/i',
        '/concat\s*\(0x/i',
        '/sleep\s*\(\d+\)/i',
        '/benchmark\s*\(/i',
        '/<script>/i',
        '/javascript:/i',
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // 1. Si la IP ya está baneada, rechazar directo
        if (Cache::has("banned_ip:{$ip}")) {
            Log::warning("IP baneada bloqueada: {$ip}");
            abort(403);
        }

        // 2. Revisar todos los inputs
        $allInput = implode(' ', array_map(
            fn($v) => is_string($v) ? $v : '',
            $request->all()
        ));

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $allInput)) {

                // Incrementar contador de intentos (ventana de 1 hora)
                $key      = "sqli_attempts:{$ip}";
                $attempts = Cache::increment($key);
                Cache::put($key, $attempts, now()->addHours(1));

                Log::error("SQLi detectado", [
                    'ip'      => $ip,
                    'url'     => $request->fullUrl(),
                    'payload' => substr($allInput, 0, 200),
                    'attempt' => $attempts,
                ]);

                // Después de 3 intentos, banear 24 horas y notificar
                if ($attempts >= 3) {
                    Cache::put("banned_ip:{$ip}", true, now()->addHours(24));
                    Log::critical("IP baneada automáticamente: {$ip}");

                    Mail::raw(
                        "IP {$ip} baneada automáticamente.\nURL: {$request->fullUrl()}\nPayload: " . substr($allInput, 0, 300),
                        fn($m) => $m->to(config('mail.admin_address'))
                                    ->subject('🚨 Ataque SQLi detectado - opendoctor.online')
                    );
                }

                abort(400, 'Solicitud inválida.');
            }
        }

        return $next($request);
    }
}