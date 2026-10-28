<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Clinic;
use App\Models\ClinicSetting;
use App\Models\Plan;
use App\Repositories\AvailabilityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClinicObserver
{
    private AvailabilityRepository $cacheRepo;

    public function __construct(AvailabilityRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * Handle the Clinic "created" event.
     * 
     * Se ejecuta automáticamente tras persistir la clínica en la base de datos.
     * Crea los recursos asociados en una transacción atómica para garantizar consistencia.
     * 
     * 🔒 CORRECCIONES:
     * - Transacción atómica (si falla algo, se hace rollback de todo)
     * - Validación de duplicados de ClinicSetting
     * - Logging de auditoría
     * - Manejo de errores con fallback
     */
    public function created(Clinic $clinic): void
    {
        try {
            DB::transaction(function () use ($clinic) {
                // 1. Buscar el plan institucional por defecto
                $institutionalPlan = Plan::where('slug', 'clinic_gold')->first();
                $planId = $institutionalPlan?->id ?? 1;

                // 2. 🔒 VALIDACIÓN: Evitar duplicados de ClinicSetting
                $existingSetting = ClinicSetting::where('clinic_id', $clinic->id)->first();
                
                if ($existingSetting) {
                    Log::warning('ClinicObserver: ClinicSetting ya existe, omitiendo creación', [
                        'clinic_id' => $clinic->id,
                        'setting_id' => $existingSetting->id,
                    ]);
                } else {
                    // 3. Inicializar la configuración por defecto de la clínica corporativa
                    ClinicSetting::create([
                        'clinic_id'                  => $clinic->id,
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

                // 4. Autogenerar la sede virtual institucional (si no existe)
                $this->ensureVirtualAddress($clinic);
            });

            Log::info('ClinicObserver: Clínica creada con recursos asociados', [
                'clinic_id' => $clinic->id,
                'clinic_name' => $clinic->user?->name ?? 'N/A',
            ]);
        } catch (Throwable $e) {
            Log::error('ClinicObserver: Error al crear recursos de la clínica', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No relanzamos la excepción para no romper el flujo de creación de la clínica
            // Los recursos se pueden crear manualmente después si es necesario
        }
    }

    /**
     * Handle the Clinic "updated" event.
     * 
     * Se ejecuta cuando se actualiza una clínica.
     * Útil para invalidar caché si cambió algo relevante.
     */
    public function updated(Clinic $clinic): void
    {
        // Si cambió el estado de la clínica (active/inactive)
        if ($clinic->wasChanged('active')) {
            Log::info('ClinicObserver: Estado de clínica cambiado', [
                'clinic_id' => $clinic->id,
                'old_active' => $clinic->getOriginal('active'),
                'new_active' => $clinic->active,
            ]);

            // Invalidar caché de disponibilidad de todas las sedes de la clínica
            $this->invalidateClinicCache($clinic);
        }

        // Si cambió el validation_status
        if ($clinic->wasChanged('validation_status')) {
            Log::info('ClinicObserver: Validation status de clínica cambiado', [
                'clinic_id' => $clinic->id,
                'old_status' => $clinic->getOriginal('validation_status'),
                'new_status' => $clinic->validation_status,
            ]);
        }
    }

    /**
     * Handle the Clinic "deleted" event.
     * 
     * Se ejecuta cuando se elimina una clínica (soft delete).
     * Limpia recursos asociados para evitar huérfanos.
     */
    public function deleted(Clinic $clinic): void
    {
        try {
            DB::transaction(function () use ($clinic) {
                // 1. Desactivar todas las sedes de la clínica
                $clinic->addresses()->update(['status' => false]);

                // 2. Desactivar todos los horarios en las sedes de la clínica
                DB::table('schedules')
                    ->whereIn('address_id', function ($query) use ($clinic) {
                        $query->select('id')
                            ->from('addresses')
                            ->where('clinic_id', $clinic->id);
                    })
                    ->update(['is_active' => false, 'updated_at' => now()]);

                // 3. Desvincular todos los doctores de la clínica
                $clinic->doctors()->detach();
            });

            // 4. Invalidar caché de disponibilidad
            $this->invalidateClinicCache($clinic);

            Log::info('ClinicObserver: Clínica eliminada y recursos limpiados', [
                'clinic_id' => $clinic->id,
            ]);
        } catch (Throwable $e) {
            Log::error('ClinicObserver: Error al limpiar recursos de clínica eliminada', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Asegura que la clínica tenga una sede virtual para telemedicina.
     * Si ya existe, no hace nada. Si no existe, la crea.
     */
    private function ensureVirtualAddress(Clinic $clinic): void
    {
        // Verificar si ya tiene una sede virtual
        $hasVirtualAddress = $clinic->addresses()
            ->where('type', 'virtual')
            ->whereNull('doctor_id')
            ->exists();

        if ($hasVirtualAddress) {
            return;
        }

        // Crear la sede virtual
        try {
            $clinic->createVirtualAddress();
            
            Log::info('ClinicObserver: Sede virtual creada', [
                'clinic_id' => $clinic->id,
            ]);
        } catch (Throwable $e) {
            Log::error('ClinicObserver: Error al crear sede virtual', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Relanzar para que la transacción haga rollback
        }
    }

    /**
     * Invalida el caché de disponibilidad para todas las sedes de la clínica.
     */
    private function invalidateClinicCache(Clinic $clinic): void
    {
        try {
            // Obtener todos los doctores aprobados de la clínica
            $doctorIds = $clinic->doctors()
                ->where('clinic_doctor.status', 'approved')
                ->pluck('doctor_id')
                ->all();

            // Obtener todas las sedes de la clínica
            $addressIds = $clinic->addresses()
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            // Invalidar caché para cada sede
            foreach ($addressIds as $addressId) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    $clinic->id,
                    $doctorIds,
                    $addressId
                );
            }
        } catch (Throwable $e) {
            Log::error('ClinicObserver: Error al invalidar caché de clínica', [
                'clinic_id' => $clinic->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}