<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Setting;

/**
 * Comisión de la pasarela (Wompi) en UN solo lugar: la tarifa que se edita en Admin → Configuraciones.
 * Fórmula real de Wompi: porcentaje + valor fijo por transacción, con IVA sobre esa comisión.
 * (Las claves conservan el prefijo ai_chat_ porque nacieron en el Chat de Salud IA.)
 */
final class GatewayFee
{
    public static function percent(): float
    {
        return self::read('ai_chat_wompi_percent', 2.65);
    }

    public static function fixed(): float
    {
        return self::read('ai_chat_wompi_fixed_fee', 700);
    }

    public static function iva(): float
    {
        return self::read('ai_chat_wompi_iva_percent', 19);
    }

    /**
     * Lo que se le suma al cliente para que, después de que Wompi cobre su tarifa, a quien vende le quede el monto completo.
     * Pesos enteros; el total se redondea hacia arriba a la centena. Monto 0 (orden gratis) = 0.
     */
    public static function surcharge(int|float $baseAmountPesos): int
    {
        $base = (float) $baseAmountPesos;

        if ($base <= 0) {
            return 0;
        }

        $factor = 1 + self::iva() / 100;
        $divisor = 1 - (self::percent() / 100) * $factor;

        if ($divisor <= 0) {
            return 0;
        }

        $total = ceil((($base + self::fixed() * $factor) / $divisor) / 100) * 100;

        return max(0, (int) ($total - $base));
    }

    /** Lo que Wompi cobra (estimado) sobre un total ya cobrado, en centavos. Total 0 = 0. */
    public static function providerFee(int $totalCents): int
    {
        if ($totalCents <= 0) {
            return 0;
        }

        $factor = 1 + self::iva() / 100;

        return (int) round($totalCents * (self::percent() / 100) * $factor + self::fixed() * 100 * $factor);
    }

    private static function read(string $key, float $default): float
    {
        $value = Setting::get($key);

        return is_numeric($value) ? (float) $value : $default;
    }
}
