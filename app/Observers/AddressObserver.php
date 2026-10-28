<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Address;
use App\Models\Service;
use App\Repositories\AvailabilityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressObserver
{
    private AvailabilityRepository $cacheRepo;

    public function __construct(AvailabilityRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * Handle the Address "created" event.
     * 
     * 🔒 CORRECCIÓN CRÍTICA: Solo vincula servicios virtuales que pertenezcan
     * al tenant (doctor o clínica) que está creando la sede.
     */
    public function created(Address $address): void
    {
        // Solo actuamos si la sede es de tipo VIRTUAL
        if ($address->type !== 'virtual') {
            return;
        }

        try {
            DB::transaction(function () use ($address) {
                // 🔒 CORRECCIÓN: Filtrar servicios virtuales por tenant
                $tenantVirtualServices = $this->getTenantVirtualServices($address);

                if ($tenantVirtualServices->isEmpty()) {
                    Log::info('AddressObserver: No hay servicios virtuales para el tenant', [
                        'address_id' => $address->id,
                        'clinic_id' => $address->clinic_id,
                        'doctor_id' => $address->doctor_id,
                    ]);
                    return;
                }

                // Estructurar el array para la tabla pivote
                $attachData = [];
                foreach ($tenantVirtualServices as $service) {
                    $attachData[$service->id] = [
                        'price'    => 0.00,  // Precio base por defecto
                        'duration' => 20,    // Duración estimada base en minutos
                    ];
                }

                // Vinculación atómica en la tabla pivote address_service
                $address->services()->attach($attachData);

                Log::info('AddressObserver: Servicios virtuales vinculados a nueva sede', [
                    'address_id' => $address->id,
                    'services_count' => count($attachData),
                    'clinic_id' => $address->clinic_id,
                    'doctor_id' => $address->doctor_id,
                ]);
            });

            // 🔥 INVALIDAR CACHÉ de disponibilidad
            $this->invalidateCacheForNewAddress($address);
        } catch (\Exception $e) {
            Log::error('AddressObserver: Error al vincular servicios virtuales', [
                'address_id' => $address->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Obtiene los servicios virtuales que pertenecen al tenant de la sede.
     * 
     *  Aislamiento multi-tenant:
     * - Si la sede es de una clínica: solo servicios de esa clínica
     * - Si la sede es de un doctor: solo servicios de ese doctor
     */
    private function getTenantVirtualServices(Address $address)
    {
        $query = Service::where('type', 'virtual')
            ->where('active', true)
            ->whereHas('specialties', function ($q) use ($address) {
                if ($address->clinic_id) {
                    // Sede de clínica: buscar servicios de la clínica
                    $clinicUserId = DB::table('clinics')
                        ->where('id', $address->clinic_id)
                        ->value('user_id');
                    
                    if ($clinicUserId) {
                        $q->where('service_specialty.user_id', $clinicUserId);
                    }
                } elseif ($address->doctor_id) {
                    // Sede de doctor: buscar servicios del doctor
                    $doctorUserId = DB::table('doctors')
                        ->where('id', $address->doctor_id)
                        ->value('user_id');
                    
                    if ($doctorUserId) {
                        $q->where('service_specialty.user_id', $doctorUserId);
                    }
                }
            });

        return $query->get();
    }

    /**
     * Invalida el caché de disponibilidad para la nueva sede.
     */
    private function invalidateCacheForNewAddress(Address $address): void
    {
        try {
            $doctorIds = [];
            
            if ($address->doctor_id) {
                $doctorIds[] = $address->doctor_id;
            } elseif ($address->clinic_id) {
                // Obtener doctores de la clínica
                $doctorIds = DB::table('clinic_doctor')
                    ->where('clinic_id', $address->clinic_id)
                    ->where('status', 'approved')
                    ->pluck('doctor_id')
                    ->all();
            }

            if (!empty($doctorIds)) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    $address->clinic_id,
                    $doctorIds,
                    $address->id
                );
            }
        } catch (\Exception $e) {
            Log::error('AddressObserver: Error al invalidar caché', [
                'address_id' => $address->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Address "updated" event.
     * 
     * Si una sede cambia de physical a virtual (o viceversa), 
     * se deben ajustar los servicios vinculados.
     */
    public function updated(Address $address): void
    {
        // Si cambió el tipo de sede
        if ($address->wasChanged('type')) {
            $oldType = $address->getOriginal('type');
            $newType = $address->type;

            Log::info('AddressObserver: Tipo de sede cambiado', [
                'address_id' => $address->id,
                'old_type' => $oldType,
                'new_type' => $newType,
            ]);

            // Si cambió de physical a virtual, vincular servicios virtuales
            if ($oldType === 'physical' && $newType === 'virtual') {
                $this->created($address); // Reutilizar lógica de creación
            }

            // Si cambió de virtual a physical, desvincular servicios virtuales
            if ($oldType === 'virtual' && $newType === 'physical') {
                $address->services()->detach();
                
                Log::info('AddressObserver: Servicios desvinculados al cambiar a physical', [
                    'address_id' => $address->id,
                ]);
            }
        }
    }

    /**
     * Handle the Address "deleted" event.
     * 
     * Laravel automáticamente elimina los registros de address_service
     * por la foreign key cascade, pero registramos el evento para auditoría.
     */
    public function deleted(Address $address): void
    {
        Log::info('AddressObserver: Sede eliminada', [
            'address_id' => $address->id,
            'type' => $address->type,
            'clinic_id' => $address->clinic_id,
            'doctor_id' => $address->doctor_id,
        ]);

        // Invalidar caché si la sede tenía doctores asociados
        try {
            $doctorIds = [];
            
            if ($address->doctor_id) {
                $doctorIds[] = $address->doctor_id;
            } elseif ($address->clinic_id) {
                $doctorIds = DB::table('clinic_doctor')
                    ->where('clinic_id', $address->clinic_id)
                    ->where('status', 'approved')
                    ->pluck('doctor_id')
                    ->all();
            }

            if (!empty($doctorIds)) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    $address->clinic_id,
                    $doctorIds,
                    $address->id
                );
            }
        } catch (\Exception $e) {
            Log::error('AddressObserver: Error al invalidar caché en deleted', [
                'address_id' => $address->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}