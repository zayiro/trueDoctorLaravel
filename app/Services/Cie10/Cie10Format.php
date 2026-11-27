<?php

declare(strict_types=1);

namespace App\Services\Cie10;

/**
 * Forma canónica de un código CIE-10: letra + 2 dígitos, y desde el cuarto carácter con punto (J029 → J02.9).
 * La tabla de SISPRO rellena con «X» las categorías de 3 caracteres (I10X, R05X, A09X): esa X no es parte del código (I10, R05, A09).
 */
final class Cie10Format
{
    /** Devuelve el código en forma canónica, o null si no tiene forma de código CIE-10. */
    public static function normalize(?string $raw): ?string
    {
        $s = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        if (!preg_match('/^[A-Z]\d{2}[A-Z0-9]{0,2}$/', $s)) {
            return null;
        }

        if (preg_match('/^([A-Z]\d{2})X$/', $s, $m)) {
            return $m[1];
        }

        return strlen($s) > 3 ? substr($s, 0, 3) . '.' . substr($s, 3) : $s;
    }
}
