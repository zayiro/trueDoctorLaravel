<?php

declare(strict_types=1);

namespace App\Payments;

use App\Models\User;

/**
 * Interruptor de despliegue de la capa de pagos nueva.
 *
 *  PAYMENTS_V2=false                       -> apagada para todos (por defecto).
 *  PAYMENTS_V2=true y PAYMENTS_V2_USER_IDS vacío   -> encendida para todos.
 *  PAYMENTS_V2=true y PAYMENTS_V2_USER_IDS=1,7     -> solo para esos usuarios (pruebas en producción).
 */
final class PaymentsFeature
{
    public static function enabledFor(?User $user): bool
    {
        if (!config('payments.v2.enabled')) {
            return false;
        }

        $allowed = (array) config('payments.v2.allowed_user_ids', []);

        if ($allowed === []) {
            return true;
        }

        return $user !== null && in_array((int) $user->id, $allowed, true);
    }
}
