<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cuentas creadas SIN que la persona elija contraseña (paciente en la reserva / Chat IA / alta por el personal,
 * y administradores nuevos): clave aleatoria que nadie conoce + enlace de «crea tu contraseña» en el
 * correo de bienvenida (el que ya envía UserObserver; no se envía un segundo correo).
 *
 * Uso: 'password' => PasswordSetup::randomFor($email) al crear el usuario. El correo de bienvenida pregunta
 * isExpected($user) para mostrar el botón. Solo vive durante la petición actual (el correo se envía en esa misma petición).
 */
class PasswordSetup
{
    /** @var array<string, true> */
    private static array $expected = [];

    /** @var array<string, string> */
    private static array $urls = [];

    /** Marca el correo como «debe crear su contraseña» y devuelve una clave aleatoria ya cifrada. */
    public static function randomFor(string $email): string
    {
        self::$expected[self::key($email)] = true;

        return Hash::make(Str::random(40));
    }

    public static function isExpected(User $user): bool
    {
        return isset(self::$expected[self::key((string) $user->email)]);
    }

    /**
     * Enlace de un solo uso para crear la contraseña. Si por algo no se puede generar, devuelve la página
     * de «¿Olvidaste tu contraseña?» para que el correo salga igual y la persona pueda pedir uno.
     */
    public static function url(User $user): string
    {
        $k = self::key((string) $user->email);

        if (! isset(self::$urls[$k])) {
            try {
                $token = Password::broker()->createToken($user);
                self::$urls[$k] = route('password.setup', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
            } catch (Throwable $e) {
                report($e);
                self::$urls[$k] = route('password.request');
            }
        }

        return self::$urls[$k];
    }

    /** Minutos de vigencia del enlace (los mismos de «olvidé mi contraseña»). */
    public static function minutes(): int
    {
        return (int) config('auth.passwords.' . config('auth.defaults.passwords', 'users') . '.expire', 60);
    }

    public static function flush(): void
    {
        self::$expected = [];
        self::$urls = [];
    }

    private static function key(string $email): string
    {
        return strtolower(trim($email));
    }
}
