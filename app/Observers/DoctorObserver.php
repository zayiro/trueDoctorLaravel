<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Doctor;
use App\Models\DoctorSetting;
use App\Models\Plan;
use App\Repositories\AvailabilityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DoctorObserver
{
    private AvailabilityRepository $cacheRepo;

    public function __construct(AvailabilityRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * Handle the Doctor "created" event.
     * 
     * Se ejecuta automáticamente tras persistir el doctor en la base de datos.
     * Crea los recursos asociados en una transacción atómica para garantizar consistencia.
     * 
     * 🔒 CORRECCIONES:
     * - Transacción atómica (si falla algo, se hace rollback de todo)
     * - Validación de duplicados de DoctorSetting
     * - Logging de auditoría
     * - Manejo de errores con fallback
     */
    public function created(Doctor $doctor): void
    {
        try {
            DB::transaction(function () use ($doctor) {
                // 1. Buscar el plan gratuito por defecto
                $defaultPlan = Plan::where('slug', 'free')->first();
                $planId = $defaultPlan?->id ?? 1;

                // 2. 🔒 VALIDACIÓN: Evitar duplicados de DoctorSetting
                $existingSetting = DoctorSetting::where('doctor_id', $doctor->id)->first();
                
                if ($existingSetting) {
                    Log::warning('DoctorObserver: DoctorSetting ya existe, omitiendo creación', [
                        'doctor_id' => $doctor->id,
                        'setting_id' => $existingSetting->id,
                    ]);
                } else {
                    // 3. Crear la configuración por defecto del doctor
                    DoctorSetting::create([
                        'doctor_id'                  => $doctor->id,
                        'plan_id'                    => $planId,
                        'accepts_online_payments'    => false,
                        'currency'                   => 'COP',
                        'min_notice_hours'           => 2,
                        'max_advance_days'           => 30,
                        'requires_approval'          => false,
                        'buffer_time_minutes'        => 0,
                        'max_appointments_per_day'   => null,
                        'allow_patient_cancellation' => true,
                        'cancellation_notice_hours'  => 2,
                        'allow_patient_rescheduling' => true,
                        'virtual_meeting_platform'   => 'internal',
                        'google_calendar_sync'       => false,
                        'email_notifications'        => true,
                        'whatsapp_notifications'     => false,
                    ]);
                }

                // 4. Autogenerar la sede virtual (si no existe)
                $this->ensureVirtualAddress($doctor);
            });

            Log::info('DoctorObserver: Doctor creado con recursos asociados', [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->user?->name ?? 'N/A',
            ]);
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al crear recursos del doctor', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No relanzamos la excepción para no romper el flujo de creación del doctor
            // Los recursos se pueden crear manualmente después si es necesario
        }
    }

    /**
     * Handle the Doctor "updated" event.
     * 
     * Se ejecuta cuando se actualiza un doctor.
     * Útil para invalidar caché si cambió algo relevante.
     */
    public function updated(Doctor $doctor): void
    {
        // Si cambió el estado de validación
        if ($doctor->wasChanged('validation_status')) {
            Log::info('DoctorObserver: Validation status de doctor cambiado', [
                'doctor_id' => $doctor->id,
                'old_status' => $doctor->getOriginal('validation_status'),
                'new_status' => $doctor->validation_status,
            ]);
        }

        // Si cambió el estado activo
        if ($doctor->wasChanged('active')) {
            Log::info('DoctorObserver: Estado activo de doctor cambiado', [
                'doctor_id' => $doctor->id,
                'old_active' => $doctor->getOriginal('active'),
                'new_active' => $doctor->active,
            ]);

            // Invalidar caché de disponibilidad de todas las sedes del doctor
            $this->invalidateDoctorCache($doctor);
        }
    }

    /**
     * Handle the Doctor "deleted" event.
     * 
     * Se ejecuta cuando se elimina un doctor (soft delete).
     * Limpia recursos asociados para evitar huérfanos.
     */
    public function deleted(Doctor $doctor): void
    {
        try {
            DB::transaction(function () use ($doctor) {
                // 1. Desactivar todas las sedes del doctor
                $doctor->addresses()->update(['status' => false]);

                // 2. Desactivar todos los horarios en las sedes del doctor
                DB::table('schedules')
                    ->where('doctor_id', $doctor->id)
                    ->update(['is_active' => false, 'updated_at' => now()]);

                // 3. Desvincular el doctor de todas las clínicas
                $doctor->clinics()->detach();
            });

            // 4. Invalidar caché de disponibilidad
            $this->invalidateDoctorCache($doctor);

            Log::info('DoctorObserver: Doctor eliminado y recursos limpiados', [
                'doctor_id' => $doctor->id,
            ]);
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al limpiar recursos de doctor eliminado', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Doctor "restored" event.
     * 
     * Se ejecuta cuando se restaura un doctor (soft delete restore).
     * Reactiva recursos asociados.
     */
    public function restored(Doctor $doctor): void
    {
        try {
            DB::transaction(function () use ($doctor) {
                // 1. Reactivar todas las sedes del doctor
                $doctor->addresses()->update(['status' => true]);

                // 2. Reactivar todos los horarios en las sedes del doctor
                DB::table('schedules')
                    ->where('doctor_id', $doctor->id)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            });

            Log::info('DoctorObserver: Doctor restaurado y recursos reactivados', [
                'doctor_id' => $doctor->id,
            ]);
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al restaurar recursos de doctor', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Doctor "force deleted" event.
     * 
     * Se ejecuta cuando se elimina permanentemente un doctor.
     * Limpia todos los recursos de forma definitiva.
     */
    public function forceDeleted(Doctor $doctor): void
    {
        try {
            DB::transaction(function () use ($doctor) {
                // 1. Eliminar todas las sedes del doctor (hard delete)
                $doctor->addresses()->forceDelete();

                // 2. Eliminar todos los horarios del doctor
                DB::table('schedules')->where('doctor_id', $doctor->id)->delete();

                // 3. Eliminar la configuración del doctor
                DoctorSetting::where('doctor_id', $doctor->id)->delete();

                // 4. Desvincular el doctor de todas las clínicas
                DB::table('clinic_doctor')->where('doctor_id', $doctor->id)->delete();
            });

            Log::info('DoctorObserver: Doctor eliminado permanentemente y recursos limpiados', [
                'doctor_id' => $doctor->id,
            ]);
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al limpiar recursos de doctor eliminado permanentemente', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Asegura que el doctor tenga una sede virtual para telemedicina.
     * Si ya existe, no hace nada. Si no existe, la crea.
     */
    private function ensureVirtualAddress(Doctor $doctor): void
    {
        // Verificar si ya tiene una sede virtual
        $hasVirtualAddress = $doctor->addresses()
            ->where('type', 'virtual')
            ->whereNull('clinic_id')
            ->exists();

        if ($hasVirtualAddress) {
            return;
        }

        // Crear la sede virtual
        try {
            $doctor->createVirtualAddress();
            
            Log::info('DoctorObserver: Sede virtual creada', [
                'doctor_id' => $doctor->id,
            ]);
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al crear sede virtual', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Relanzar para que la transacción haga rollback
        }
    }

    /**
     * Invalida el caché de disponibilidad para todas las sedes del doctor.
     */
    private function invalidateDoctorCache(Doctor $doctor): void
    {
        try {
            // Obtener todas las sedes del doctor
            $addressIds = $doctor->addresses()
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            // Invalidar caché para cada sede
            foreach ($addressIds as $addressId) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    null, // clinic_id = null (doctor independiente)
                    [$doctor->id],
                    $addressId
                );
            }
        } catch (Throwable $e) {
            Log::error('DoctorObserver: Error al invalidar caché de doctor', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}