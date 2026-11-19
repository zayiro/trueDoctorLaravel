<?php

declare(strict_types=1);

namespace App\Services\Promo;

use App\Models\Appointment;
use App\Models\PromoCode;

/**
 * Búsqueda de un código promocional para una cita, respetando de quién es el código:
 *   - OpenDoctor: sirve para cualquier cita.
 *   - Doctor: solo sus citas particulares (sin clínica).
 *   - Clínica: solo citas de esa clínica.
 */
final class PromoCodeService
{
    public function findForAppointment(?string $rawCode, Appointment $appointment, ?int $userId): ?PromoCode
    {
        $code = strtoupper(trim((string) $rawCode));

        if ($code === '') {
            return null;
        }

        $promo = PromoCode::forModule(PromoCode::MODULE_APPOINTMENT)->where('code', $code)->first();

        if (!$promo || !$promo->isValid($userId)) {
            return null;
        }

        return $this->appliesToAppointment($promo, $appointment) ? $promo : null;
    }

    public function appliesToAppointment(PromoCode $promo, Appointment $appointment): bool
    {
        return match ($promo->owner_type) {
            null                     => true,
            PromoCode::OWNER_DOCTOR  => $appointment->clinic_id === null && (int) $appointment->doctor_id === (int) $promo->owner_id,
            PromoCode::OWNER_CLINIC  => $appointment->clinic_id !== null && (int) $appointment->clinic_id === (int) $promo->owner_id,
            default                  => false,
        };
    }
}
