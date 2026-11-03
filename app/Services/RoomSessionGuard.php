<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Sesión ÚNICA por persona y cita en la sala de videollamada.
 *
 * Política "la última gana": al abrir la sala se emite un token nuevo, que reemplaza al anterior.
 * La pestaña o dispositivo anterior lo descubre en su siguiente latido y se cierra sola.
 * Así, recargar la página o recuperarse de un cierre inesperado nunca deja a nadie fuera de su consulta
 * (con la política "la primera gana" una pestaña caída bloquearía la sala hasta que expirara).
 *
 * Usa la caché de Laravel: en un servidor único sirve cualquier driver persistente (file, database, redis);
 * con varios servidores detrás de un balanceador debe ser una caché compartida (redis o database).
 */
class RoomSessionGuard
{
    /**
     * Segundos sin latido tras los cuales una sesión se considera caída.
     * El cliente envía un latido cada 5 s; este margen tolera pestañas en segundo plano.
     */
    public const TTL_SECONDS = 45;

    /**
     * Registra una sesión nueva y devuelve su token. Reemplaza a la sesión anterior de la misma persona.
     */
    public function claim(int $appointmentId, int $userId): string
    {
        $token = (string) Str::uuid();

        Cache::put($this->key($appointmentId, $userId), $token, self::TTL_SECONDS);

        return $token;
    }

    /**
     * Latido: devuelve true si el token sigue siendo la sesión vigente (y renueva su vigencia).
     * Si la sesión expiró por inactividad y nadie más la reclamó, se recupera.
     */
    public function touch(int $appointmentId, int $userId, string $token): bool
    {
        $key     = $this->key($appointmentId, $userId);
        $current = Cache::get($key);

        if ($current === null) {
            // add() solo escribe si la clave no existe: si otra sesión se adelantó, devuelve false
            return Cache::add($key, $token, self::TTL_SECONDS);
        }

        if (!hash_equals((string) $current, $token)) {
            return false;
        }

        Cache::put($key, $token, self::TTL_SECONDS);

        return true;
    }

    private function key(int $appointmentId, int $userId): string
    {
        return "room_session:{$appointmentId}:{$userId}";
    }
}