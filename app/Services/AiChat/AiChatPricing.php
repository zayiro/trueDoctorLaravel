<?php

namespace App\Services\AiChat;

/**
 * Precio de una sesión de chat: el precio base es lo que le queda a OpenDoctor y el cliente paga además
 * la comisión de la pasarela (porcentaje + valor fijo, con IVA sobre la comisión de Wompi).
 *
 * total = (base + fijo × (1 + IVA)) / (1 − porcentaje × (1 + IVA)), redondeado hacia arriba a la centena (así el neto nunca queda por debajo del precio base).
 * Todo en pesos enteros. Usa sus propios ajustes del chat (no el `wompi_fee` general de citas y análisis).
 */
class AiChatPricing
{
    /** @return array{base:int, commission:int, total:int} */
    public static function quote(int $minutes): array
    {
        $base = AiChatSettings::price($minutes);
        [$pct, $fixed, $factor] = self::rates();

        $denominator = 1 - ($pct / 100) * $factor;

        if ($denominator <= 0.2) {
            throw new \RuntimeException('La tarifa de la pasarela configurada no es válida.');
        }

        $total = (int) (ceil((($base + $fixed * $factor) / $denominator) / 100) * 100);
        $total = max($total, $base);

        return [
            'base' => $base,
            'commission' => $total - $base,
            'total' => $total,
        ];
    }

    /** Lo que Wompi se queda de un cobro (solo contable), en pesos. */
    public static function providerFee(int $total): int
    {
        [$pct, $fixed, $factor] = self::rates();

        return (int) round((($total * $pct / 100) + $fixed) * $factor);
    }

    /** @return array{0: float, 1: float, 2: float} [porcentaje, fijo, 1 + IVA] */
    private static function rates(): array
    {
        $pct = (float) AiChatSettings::value('ai_chat_wompi_percent');
        $fixed = (float) AiChatSettings::value('ai_chat_wompi_fixed_fee');
        $iva = (float) AiChatSettings::value('ai_chat_wompi_iva_percent');

        return [$pct, $fixed, 1 + $iva / 100];
    }
}
