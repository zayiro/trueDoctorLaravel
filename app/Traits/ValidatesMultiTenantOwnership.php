<?php

namespace App\Traits;

use App\Models\Address;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait ValidatesMultiTenantOwnership
{
    /**
     * 🔒 Valida que una sede pertenezca al usuario autenticado (doctor o clínica).
     * Soporta contexto institucional para doctores en clínicas aliadas.
     *
     * @param Address $address
     * @param User|null $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateAddressOwnership(Address $address, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        $context = session('doctor_context');

        // Caso 1: Doctor en contexto institucional de clínica aliada
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            if ($address->clinic_id !== (int)$context['id']) {
                abort(403, 'No tienes permiso sobre esta sede de la clínica corporativa.');
            }
            return true;
        }

        // Caso 2: Clínica pura
        if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
            abort(403, 'No tienes permiso sobre esta sede institucional.');
        }

        // Caso 3: Doctor independiente
        if ($user->role === 'doctor' && $address->doctor_id !== $user->doctor->id) {
            abort(403, 'No tienes permiso sobre esta sede privada.');
        }

        return true;
    }

    /**
     * 🔒 Valida que un servicio pertenezca al usuario autenticado.
     * Verifica la tabla pivote service_specialty con aislamiento por user_id.
     *
     * @param Service $service
     * @param User|null $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateServiceOwnership(Service $service, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        $belongsToOwner = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$belongsToOwner) {
            abort(403, 'No tienes permisos para modificar este servicio médico.');
        }

        return true;
    }

    /**
     * 🔒 Valida que una especialidad pertenezca al usuario autenticado.
     * Para doctores: Verifica doctor_specialty.
     * Para clínicas: Verifica clinic_specialty.
     *
     * @param Specialty $specialty
     * @param User|null $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateSpecialtyOwnership(Specialty $specialty, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        $context = session('doctor_context');

        // Caso 1: Doctor en contexto institucional
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            $clinicId = (int)$context['id'];

            // Validar que la especialidad sea compartida entre doctor y clínica
            $isShared = DB::table('doctor_specialty')
                ->where('doctor_id', $user->doctor->id)
                ->where('specialty_id', $specialty->id)
                ->whereIn('specialty_id', function($q) use ($clinicId) {
                    $q->select('specialty_id')->from('clinic_specialty')->where('clinic_id', $clinicId);
                })
                ->exists();

            if (!$isShared) {
                abort(403, 'Esta especialidad no es compartida entre tu perfil y la clínica activa.');
            }

            return true;
        }

        // Caso 2: Clínica pura
        if ($user->role === 'clinic') {
            $hasSpecialty = DB::table('clinic_specialty')
                ->where('clinic_id', $user->clinic->id)
                ->where('specialty_id', $specialty->id)
                ->exists();

            if (!$hasSpecialty) {
                abort(403, 'Tu clínica no tiene esta especialidad registrada.');
            }

            return true;
        }

        // Caso 3: Doctor independiente
        if ($user->role === 'doctor') {
            $hasSpecialty = DB::table('doctor_specialty')
                ->where('doctor_id', $user->doctor->id)
                ->where('specialty_id', $specialty->id)
                ->exists();

            if (!$hasSpecialty) {
                abort(403, 'No tienes esta especialidad registrada en tu perfil.');
            }

            return true;
        }

        return true;
    }

    /**
     * 🔒 Valida que todas las sedes seleccionadas pertenezcan al usuario autenticado.
     * Útil para sincronización masiva en address_service.
     *
     * @param array $addressIds
     * @param User|null $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateAddressesOwnership(array $addressIds, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        $context = session('doctor_context');

        foreach ($addressIds as $addressId) {
            $address = Address::find($addressId);
            if (!$address) {
                abort(404, "La sede con ID {$addressId} no existe.");
            }

            $this->validateAddressOwnership($address, $user);
        }

        return true;
    }

    /**
     * 🔒 Valida que todas las especialidades seleccionadas pertenezcan al usuario autenticado.
     * Útil para sincronización masiva en service_specialty.
     *
     * @param array $specialtyIds
     * @param User|null $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateSpecialtiesOwnership(array $specialtyIds, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        foreach ($specialtyIds as $specialtyId) {
            $specialty = Specialty::find($specialtyId);
            if (!$specialty) {
                abort(404, "La especialidad con ID {$specialtyId} no existe.");
            }

            $this->validateSpecialtyOwnership($specialty, $user);
        }

        return true;
    }

    /**
     * 🔒 Valida que una sede sea del tipo correcto para el servicio.
     * Previene vincular servicios 'physical' a sedes 'virtual' y viceversa.
     *
     * @param Address $address
     * @param string $serviceType ('physical' o 'virtual')
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validateAddressTypeForService(Address $address, string $serviceType): bool
    {
        if ($serviceType === 'physical' && $address->type !== 'physical') {
            abort(422, "Los servicios físicos solo pueden vincularse a sedes físicas.");
        }

        if ($serviceType === 'virtual' && $address->type !== 'virtual') {
            abort(422, "Los servicios virtuales solo pueden vincularse a sedes virtuales.");
        }

        return true;
    }

    /**
     * 🔒 Impide que un doctor realice acciones de escritura en contexto institucional.
     * Lanza excepción si intenta modificar sedes/servicios de la clínica.
     *
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function denyIfInstitutionalContext(): bool
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            abort(403, 'Acción denegada. Los recursos institucionales solo pueden ser modificados por los administradores de la clínica.');
        }

        return true;
    }
}
