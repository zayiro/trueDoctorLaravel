<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cada servicio pasa a pertenecer a UNA sola sede (y por tanto a un solo profesional/clínica).
 *
 * Antes, `services` era un catálogo compartido: varios profesionales (y varias sedes) usaban la misma fila,
 * así que editar o borrar afectaba a todos. Esta migración:
 *   1. Agrega SoftDeletes a services (borrar un servicio no rompe el historial de citas).
 *   2. Separa los servicios compartidos: la primera sede conserva la fila original y cada sede adicional recibe
 *      una copia independiente (con su precio/duración del pivote, las especialidades de SU dueño y sus citas).
 *   3. Elimina de cada servicio los vínculos de especialidad de usuarios que no son el dueño de la sede.
 *
 * ⚠️ Respalda las tablas services, address_service, service_specialty y appointments antes de ejecutarla.
 * Es idempotente: si ya no quedan servicios compartidos, no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'deleted_at')) {
            Schema::table('services', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        DB::transaction(function () {
            $owners = [];
            $stats  = ['services_cloned' => 0, 'appointments_moved' => 0, 'foreign_links_removed' => 0];

            // user_id del dueño (doctor o clínica) de una sede. El query builder incluye sedes eliminadas lógicamente.
            $ownerUserId = function (int $addressId) use (&$owners): ?int {
                if (array_key_exists($addressId, $owners)) {
                    return $owners[$addressId];
                }

                $address = DB::table('addresses')->where('id', $addressId)->first(['doctor_id', 'clinic_id']);
                $userId  = null;

                if ($address?->doctor_id) {
                    $userId = DB::table('doctors')->where('id', $address->doctor_id)->value('user_id');
                } elseif ($address?->clinic_id) {
                    $userId = DB::table('clinics')->where('id', $address->clinic_id)->value('user_id');
                }

                return $owners[$addressId] = $userId ? (int) $userId : null;
            };

            // 1) Servicios compartidos entre varias sedes: una copia independiente por sede adicional
            $sharedIds = DB::table('address_service')
                ->select('service_id')
                ->groupBy('service_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('service_id');

            foreach ($sharedIds as $serviceId) {
                $service = DB::table('services')->where('id', $serviceId)->first();
                $pivots  = DB::table('address_service')->where('service_id', $serviceId)->orderBy('id')->get();
                $pivots->shift(); // la primera sede conserva el servicio original

                foreach ($pivots as $pivot) {
                    $newId = DB::table('services')->insertGetId([
                        'name'       => $service->name,
                        'type'       => $service->type,
                        'active'     => $service->active,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('address_service')->where('id', $pivot->id)->update(['service_id' => $newId]);

                    // Especialidades del dueño de ESA sede
                    $userId = $ownerUserId((int) $pivot->address_id);
                    if ($userId) {
                        $specialtyIds = DB::table('service_specialty')
                            ->where('service_id', $serviceId)
                            ->where('user_id', $userId)
                            ->pluck('specialty_id');

                        foreach ($specialtyIds as $specialtyId) {
                            DB::table('service_specialty')->insert([
                                'service_id'   => $newId,
                                'specialty_id' => $specialtyId,
                                'user_id'      => $userId,
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        }
                    }

                    // Las citas de esa sede siguen apuntando a "su" servicio
                    $stats['appointments_moved'] += DB::table('appointments')
                        ->where('service_id', $serviceId)
                        ->where('address_id', $pivot->address_id)
                        ->update(['service_id' => $newId]);

                    $stats['services_cloned']++;
                }
            }

            // 2) Servicios de una sola sede: quitar vínculos de especialidad de usuarios que no son el dueño
            $singleIds = DB::table('address_service')
                ->select('service_id')
                ->groupBy('service_id')
                ->havingRaw('COUNT(*) = 1')
                ->pluck('service_id');

            foreach ($singleIds as $serviceId) {
                $addressId = (int) DB::table('address_service')->where('service_id', $serviceId)->value('address_id');
                $userId    = $ownerUserId($addressId);

                if ($userId) {
                    $stats['foreign_links_removed'] += DB::table('service_specialty')
                        ->where('service_id', $serviceId)
                        ->where('user_id', '!=', $userId)
                        ->delete();
                }
            }

            Log::info('Migración servicios independientes por sede', $stats);
        });
    }

    /**
     * La separación de datos no es reversible (las copias ya son independientes). Se conserva la columna deleted_at.
     */
    public function down(): void
    {
        //
    }
};