<?php

namespace App\Services\AiChat;

use App\Models\Setting;

/**
 * Lectura tipada de los ajustes del Chat de Salud con IA.
 * Si una llave no existe en `settings`, se usa el valor de arranque de config/ai_chat.php.
 */
class AiChatSettings
{
    public static function value(string $key)
    {
        $default = config("ai_chat.defaults.{$key}");
        $stored = Setting::get($key);

        return ($stored === null || $stored === '') ? $default : $stored;
    }

    public static function int(string $key): int
    {
        return (int) static::value($key);
    }

    /** Precio base (neto para OpenDoctor) de la sesión, en COP. */
    public static function price(int $minutes): int
    {
        static::assertDuration($minutes);

        return static::int("ai_chat_price_{$minutes}");
    }

    /** Tope de mensajes del paciente para esa duración. */
    public static function maxMessages(int $minutes): int
    {
        static::assertDuration($minutes);

        return static::int("ai_chat_max_messages_{$minutes}");
    }

    public static function salesPaused(): bool
    {
        return static::int('ai_chat_sales_paused') === 1;
    }

    /** @return array<int,int> duración => precio */
    public static function prices(): array
    {
        $out = [];
        foreach (config('ai_chat.durations') as $minutes) {
            $out[$minutes] = static::price((int) $minutes);
        }

        return $out;
    }

    private static function assertDuration(int $minutes): void
    {
        if (! in_array($minutes, config('ai_chat.durations'), true)) {
            throw new \InvalidArgumentException("Duración de chat no válida: {$minutes}");
        }
    }
}
