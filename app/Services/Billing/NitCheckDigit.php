<?php

namespace App\Services\Billing;

/**
 * Dígito de verificación (DV) del NIT colombiano y utilidades para separar «número + DV».
 * Sin dependencias de Laravel: se puede probar sola.
 */
class NitCheckDigit
{
    /** Ponderadores oficiales, de derecha a izquierda. */
    private const WEIGHTS = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    /** DV de un número de NIT (sin DV). Null si el número no es válido para calcularlo. */
    public static function for(string $number): ?string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';
        $len = strlen($digits);
        if ($len < 5 || $len > 15) {
            return null;
        }

        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            $sum += (int) $digits[$len - 1 - $i] * self::WEIGHTS[$i];
        }
        $r = $sum % 11;

        return (string) ($r > 1 ? 11 - $r : $r);
    }

    /**
     * Separa un NIT guardado sin guion en [número, DV].
     * Si el último dígito coincide con el DV calculado del resto, se asume que ya lo trae.
     * Es solo una SUGERENCIA: la persona la confirma en pantalla.
     *
     * @return array{0:string,1:?string}
     */
    public static function split(string $raw): array
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits === '') {
            return ['', null];
        }

        if (strlen($digits) >= 6) {
            $body = substr($digits, 0, -1);
            $last = substr($digits, -1);
            if (self::for($body) === $last) {
                return [$body, $last];
            }
        }

        return [$digits, self::for($digits)];
    }
}
