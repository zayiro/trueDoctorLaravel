<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * Porcentaje de comisión de servicio que paga el paciente por pagar en línea.
 *
 * Precedencia: porcentaje especial del médico/clínica (si lo tiene) → porcentaje general del admin.
 * El general se elige por modalidad (virtual / presencial) y por tipo (médico / clínica).
 * Si el general presencial no está definido, se usa el virtual (misma comisión).
 */
final class CommissionRates
{
    public const DEFAULT_RATE = 15.0;

    public static function forAppointment(Appointment $appointment): float
    {
        $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');
        $isClinic  = $appointment->clinic_id !== null;
        $settings  = $isClinic ? $appointment->clinic?->settings : $appointment->doctor?->settings;

        return self::resolve($isClinic, $isVirtual, $settings);
    }

    /** @param string $modality 'virtual' | 'presential' */
    public static function forOwner(Model $owner, string $modality = 'presential'): float
    {
        return self::resolve($owner instanceof Clinic, $modality === 'virtual', $owner->settings ?? null);
    }

    public static function resolve(bool $isClinic, bool $isVirtual, ?Model $settings): float
    {
        $special = $settings
            ? ($isVirtual ? $settings->getAttribute('special_virtual_commission') : $settings->getAttribute('special_presential_commission'))
            : null;

        if ($special !== null && $special !== '') {
            return max(0.0, min(100.0, (float) $special));
        }

        $owner = $isClinic ? 'clinic' : 'doctor';

        if (!$isVirtual) {
            $presential = Setting::where('key', "presential_commission_{$owner}")->value('value');
            if ($presential !== null && $presential !== '') {
                return (float) $presential;
            }
        }

        $virtual = Setting::where('key', "virtual_commission_{$owner}")->value('value');

        return ($virtual !== null && $virtual !== '') ? (float) $virtual : self::DEFAULT_RATE;
    }
}
