<?php

declare(strict_types=1);

namespace App\Services\Promo;

use App\Models\PromoCode;
use App\Models\User;

/**
 * Decide a nombre de quién se crean los códigos de un usuario y si puede crearlos ahora.
 *   - Clínica: códigos de la clínica.
 *   - Doctor particular (plan individual, aprobado, trabajando en modo particular): códigos del doctor.
 *   - Doctor que trabaja para una clínica (o en contexto de clínica): no crea códigos.
 */
final class PromoCodeOwnerResolver
{
    /**
     * @return array{type: string|null, id: int|null, can_create: bool, reason: string|null}
     */
    public function resolve(User $user): array
    {
        if ($user->role === 'clinic' && $user->clinic) {
            return ['type' => PromoCode::OWNER_CLINIC, 'id' => (int) $user->clinic->id, 'can_create' => true, 'reason' => null];
        }

        if ($user->role === 'doctor' && $user->doctor) {
            $doctor = $user->doctor;
            $base = ['type' => PromoCode::OWNER_DOCTOR, 'id' => (int) $doctor->id];

            if (($doctor->validation_status ?? null) !== 'approved') {
                return $base + ['can_create' => false, 'reason' => 'Tu perfil debe estar aprobado para crear códigos promocionales.'];
            }

            if ($user->doctorSettings?->plan_id === null) {
                return $base + ['can_create' => false, 'reason' => 'Los códigos promocionales los crea la clínica en la que trabajas.'];
            }

            if ((session('doctor_context')['type'] ?? 'particular') === 'clinic') {
                return $base + ['can_create' => false, 'reason' => 'Estás trabajando en el modo de una clínica. Cambia a tu consulta particular para gestionar tus códigos.'];
            }

            return $base + ['can_create' => true, 'reason' => null];
        }

        return ['type' => null, 'id' => null, 'can_create' => false, 'reason' => 'No tienes permisos para gestionar códigos promocionales.'];
    }
}
