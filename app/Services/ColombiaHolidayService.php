<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ColombiaHolidayService
{
    /**
     * Festivos de Colombia de un año: ['AAAA-MM-DD' => true].
     * Uso: $festivos = ColombiaHolidayService::getHolidays(2026);
     */
    public static function getHolidays(int $year): array
    {
        $holidays = [];

        // A. Fechas fijas
        foreach (['01-01', '05-01', '07-20', '08-07', '12-08', '12-25'] as $monthDay) {
            $holidays["$year-$monthDay"] = true;
        }

        // B. Ley Emiliani con fecha base fija: si no cae en lunes, pasan al lunes siguiente
        //    (Reyes, San José, San Pedro y San Pablo, Asunción, Día de la Raza, Todos los Santos, Independencia de Cartagena)
        foreach (['01-06', '03-19', '06-29', '08-15', '10-12', '11-01', '11-11'] as $monthDay) {
            $holidays[self::toMonday(CarbonImmutable::parse("$year-$monthDay"))] = true;
        }

        // C. Semana Santa y fiestas móviles (se calculan desde el Domingo de Pascua)
        $easter = CarbonImmutable::create($year, 3, 21)->addDays(easter_days($year));

        $holidays[$easter->subDays(3)->toDateString()] = true; // Jueves Santo
        $holidays[$easter->subDays(2)->toDateString()] = true; // Viernes Santo

        // Ascensión (jueves +39), Corpus Christi (jueves +60) y Sagrado Corazón (viernes +68):
        // por la Ley Emiliani se trasladan al lunes siguiente (+43, +64 y +71).
        foreach ([39, 60, 68] as $daysAfterEaster) {
            $holidays[self::toMonday($easter->addDays($daysAfterEaster))] = true;
        }

        return $holidays;
    }

    /**
     * Festivos de todos los años que toca un rango de fechas.
     * Sirve cuando el horizonte de agenda cruza el fin de año (por ejemplo, diciembre → enero).
     */
    public static function getHolidaysBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        $holidays = [];

        for ($year = $from->year; $year <= $to->year; $year++) {
            $holidays += self::getHolidays($year);
        }

        return $holidays;
    }

    /** Ley Emiliani: si la fecha no es lunes, pasa al lunes siguiente. */
    private static function toMonday(CarbonImmutable $date): string
    {
        return $date->dayOfWeekIso === 1
            ? $date->toDateString()
            : $date->next(CarbonImmutable::MONDAY)->toDateString();
    }
}