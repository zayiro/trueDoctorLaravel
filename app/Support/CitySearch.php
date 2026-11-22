<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El autocompletado de ciudades del buscador manda códigos de otro catálogo (p. ej. 3687238 para Cali),
 * mientras que las sedes guardan el código de la tabla cities (DANE, p. ej. 76001).
 * Para no depender del código, además del que llega se busca la ciudad local con el mismo nombre.
 */
final class CitySearch
{
    /** @return array<int, string> códigos de ciudad con los que buscar sedes */
    public static function ids(string $cityId, ?string $name = null, ?string $countryCode = null): array
    {
        $ids = [$cityId];
        $name = trim((string) $name);
        $country = strtoupper(trim((string) $countryCode));

        if ($name !== '' && ($country === '' || $country === 'CO')) {
            $slug = Str::slug($name);
            if ($slug !== '') {
                foreach (DB::table('cities')->where('slug', $slug)->pluck('id') as $id) {
                    $ids[] = (string) $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
