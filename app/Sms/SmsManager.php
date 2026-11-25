<?php

declare(strict_types=1);

namespace App\Sms;

use App\Models\Setting;
use App\Sms\Contracts\SmsGateway;
use App\Sms\Gateways\TwilioSmsGateway;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Elige el proveedor de SMS. El proveedor principal y el de respaldo salen de los ajustes del admin
 * (settings: sms_provider, sms_fallback). Si no hay nada guardado o el valor no existe, se usa config('sms.default') (Twilio).
 *
 * Para agregar un proveedor:
 *   1. Crear app/Sms/Gateways/MiProveedorSmsGateway.php (implements SmsGateway) con sus llaves en config/services.php y .env.
 *   2. Agregar UNA línea en REGISTRY:  'miproveedor' => ['Mi Proveedor', MiProveedorSmsGateway::class],
 *   3. Elegirlo en Administración → Configuración → Proveedor de SMS.
 */
final class SmsManager
{
    /** clave => [etiqueta que ve el admin, clase] */
    private const REGISTRY = [
        'twilio' => ['Twilio', TwilioSmsGateway::class],
    ];

    /** @var array<string, array{0:string, 1:SmsGateway}> proveedores registrados en tiempo de ejecución (pruebas) */
    private array $extra = [];

    /** @var array<string, SmsGateway> */
    private array $built = [];

    public function extend(string $name, string $label, SmsGateway $gateway): void
    {
        $this->extra[$name] = [$label, $gateway];
    }

    /** @return array<string,string> clave => etiqueta */
    public function options(): array
    {
        $out = [];
        foreach (self::REGISTRY as $name => [$label]) {
            $out[$name] = $label;
        }
        foreach ($this->extra as $name => [$label]) {
            $out[$name] = $label;
        }

        return $out;
    }

    public function driver(string $name): SmsGateway
    {
        if (isset($this->extra[$name])) {
            return $this->extra[$name][1];
        }
        if (isset(self::REGISTRY[$name])) {
            $class = self::REGISTRY[$name][1];

            return $this->built[$name] ??= new $class();
        }

        throw new InvalidArgumentException("Proveedor de SMS no soportado: {$name}");
    }

    public function configured(string $name): bool
    {
        try {
            return $this->driver($name)->isConfigured();
        } catch (Throwable) {
            return false;
        }
    }

    public function primaryName(): string
    {
        $v = $this->setting('sms_provider');

        return $v !== null && isset($this->options()[$v]) ? $v : $this->defaultName();
    }

    public function fallbackName(): ?string
    {
        $v = $this->setting('sms_fallback');

        return $v !== null && isset($this->options()[$v]) && $v !== $this->primaryName() ? $v : null;
    }

    /** ¿Hay al menos un proveedor (principal o respaldo) con sus llaves puestas? */
    public function hasConfiguredProvider(): bool
    {
        foreach ([$this->primaryName(), $this->fallbackName()] as $name) {
            if ($name !== null && $this->configured($name)) {
                return true;
            }
        }

        return false;
    }

    /** Envía por el principal y, si falla o no está configurado, por el respaldo. Devuelve la clave del proveedor que lo envió, o null. */
    public function send(string $to, string $text): ?string
    {
        foreach ([$this->primaryName(), $this->fallbackName()] as $name) {
            if ($name === null) {
                continue;
            }
            try {
                $gateway = $this->driver($name);
                if (!$gateway->isConfigured()) {
                    Log::warning('sms.provider_not_configured', ['provider' => $name]);
                    continue;
                }
                if ($gateway->send($to, $text)) {
                    return $name;
                }
                Log::warning('sms.provider_failed', ['provider' => $name]);
            } catch (Throwable $e) {
                Log::warning('sms.provider_error', ['provider' => $name, 'message' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function defaultName(): string
    {
        $d = (string) config('sms.default', 'twilio');

        return isset($this->options()[$d]) ? $d : 'twilio';
    }

    private function setting(string $key): ?string
    {
        try {
            $v = Setting::get($key);

            return is_string($v) && $v !== '' ? $v : null;
        } catch (Throwable) {
            return null; // base de datos no disponible: se usa el proveedor por defecto
        }
    }
}
