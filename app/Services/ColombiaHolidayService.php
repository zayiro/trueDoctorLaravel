<?php

namespace App\Services;

use Carbon\Carbon;

class ColombiaHolidayService
{
    /**
     * Calcula y retorna un array indexado con todos los festivos de Colombia para un año específico.
     * Uso: $festivos = ColombiaHolidayService::getHolidays(2026);
     */
    public static function getHolidays(int $year): array
    {
        // A. Festivos con Fecha Fija Absoluta
        $fixedHolidays = [
            "$year-01-01", // Año Nuevo
            "$year-05-01", // Día del Trabajo
            "$year-07-20", // Día de la independencia
            "$year-08-07", // Batalla de Boyacá
            "$year-12-08", // Inmaculada Concepción
            "$year-12-25", // Navidad
        ];

        // B. Festivos regidos por la Ley Emiliani (Se mueven al siguiente Lunes si no son Lunes)
        $emilianiDates = [
            "$year-01-06", // Reyes Magos
            "$year-03-19", // San José
            "$year-06-08", // Corpus christi
            "$year-06-15", // Sagrado Corazón de Jesús
            "$year-06-29", // San Pedro y San Pablo
            "$year-07-13", // Día de la Virgen de Chiquinquirá
            "$year-08-15", // Asunción de la Virgen
            "$year-10-12", // Día de la Raza
            "$year-11-01", // Todos los Santos
            "$year-11-11", // Independencia de Cartagena
        ];

        // C. Cálculo de la Semana Santa Variable (Fórmula de Gauss integrada nativa)
        $daysAfterMarch21 = easter_days($year);
        $easterSunday = Carbon::createFromDate($year, 3, 21)->addDays($daysAfterMarch21);
        
        $juevesSanto = (clone $easterSunday)->subDays(3)->toDateString();
        $viernesSanto = (clone $easterSunday)->subDays(2)->toDateString();
        
        // Fiestas móviles calculadas que también se arrastran al Lunes por Ley Emiliani
        $ascension = (clone $easterSunday)->addDays(43)->toDateString();     // Ascensión
        $corpusChristi = (clone $easterSunday)->addDays(64)->toDateString(); // Corpus Christi
        $sagradoCorazon = (clone $easterSunday)->addDays(71)->toDateString(); // Sagrado Corazón

        $colombianHolidays = [];

        // Inyectar fechas fijas
        foreach ($fixedHolidays as $date) { 
            $colombianHolidays[$date] = true; 
        }

        // Inyectar fechas Emiliani móviles calculadas
        foreach (array_merge($emilianiDates, [$ascension, $corpusChristi, $sagradoCorazon]) as $date) {
            $carbonDate = Carbon::parse($date);
            if ($carbonDate->dayOfWeek !== Carbon::MONDAY) {
                $carbonDate->next(Carbon::MONDAY);
            }
            $colombianHolidays[$carbonDate->toDateString()] = true;
        }

        // Inyectar Semana Santa fija
        $colombianHolidays[$juevesSanto] = true;
        $colombianHolidays[$viernesSanto] = true;

        return $colombianHolidays;
    }
}
