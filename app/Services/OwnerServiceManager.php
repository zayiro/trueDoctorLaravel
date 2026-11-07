<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Service;
use App\Repositories\AvailabilityRepository;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona los servicios de un profesional o clínica.
 *
 * Regla de negocio: cada servicio pertenece a UNA sola sede y es independiente de los de los demás profesionales.
 *  - Crear un servicio "en varias sedes" crea una fila INDEPENDIENTE por sede (el profesional lo configura una vez).
 *  - Editar o eliminar un servicio afecta únicamente a esa sede.
 *  - Todo se acota al propietario: nunca se leen ni se escriben servicios de otro profesional.
 */
class OwnerServiceManager
{
    public function __construct(private readonly AvailabilityRepository $availabilityCache)
    {
    }

    /**
     * Crea el servicio en las sedes indicadas (una fila independiente por sede).
     *
     * @param  array $data name, type, duration, specialties[] y, según el tipo: price_virtual (virtual)
     *                     o address_ids[] + prices[addressId] (physical)
     * @return Collection<int, Service>
     */
    public function create(Doctor|Clinic $owner, int $userId, array $data): Collection
    {
        $targets = $this->resolveTargets($owner, $data);

        $services = DB::transaction(function () use ($targets, $data, $userId) {
            $created = collect();

            foreach ($targets as $target) {
                $this->assertNameAvailable($target['address'], $data['name'], $data['type']);

                $service = Service::create([
                    'name'   => $data['name'],
                    'type'   => $data['type'],
                    'active' => true,
                ]);

                $service->addresses()->attach($target['address']->id, [
                    'price'    => round($target['price'], 2),
                    'duration' => (int) $data['duration'],
                ]);

                $service->specialties()->attach($this->specialtyPivot($data['specialties'], $userId));

                $created->push($service);
            }

            return $created;
        });

        foreach ($targets as $target) {
            $this->invalidateCache($owner, $target['address']);
        }

        return $services;
    }

    /**
     * Edita el servicio SOLO en su sede: nombre, duración, precio y especialidades.
     * El tipo y la sede no cambian.
     */
    public function update(Service $service, Doctor|Clinic $owner, int $userId, array $data): Service
    {
        $address = $this->addressOf($service, $owner);

        if ($data['name'] !== $service->name) {
            $this->assertNameAvailable($address, $data['name'], $service->type, $service->id);
        }

        DB::transaction(function () use ($service, $address, $userId, $data) {
            $service->update(['name' => $data['name']]);

            $service->addresses()->updateExistingPivot($address->id, [
                'price'    => round((float) $data['price'], 2),
                'duration' => (int) $data['duration'],
            ]);

            // El servicio es exclusivo de esta sede: sincronizar es seguro
            $service->specialties()->sync($this->specialtyPivot($data['specialties'], $userId));
        });

        $this->invalidateCache($owner, $address);

        return $service->refresh();
    }

    /**
     * Elimina el servicio SOLO de su sede (eliminación lógica: el historial de citas se conserva).
     *
     * @throws DomainException si hay citas próximas con este servicio
     */
    public function delete(Service $service, Doctor|Clinic $owner): void
    {
        $address = $this->addressOf($service, $owner);

        $hasUpcoming = Appointment::where('service_id', $service->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now(config('app.timezone', 'America/Bogota'))->toDateString())
            ->exists();

        if ($hasUpcoming) {
            throw new DomainException('No puedes eliminar este servicio porque tiene citas próximas. Cancélalas o reprográmalas primero.');
        }

        $service->delete();

        $this->invalidateCache($owner, $address);
    }

    /**
     * La sede del servicio, verificando que pertenece al propietario.
     */
    public function addressOf(Service $service, Doctor|Clinic $owner): Address
    {
        $addresses = $service->addresses()->withTrashed()->get();

        abort_if($addresses->count() !== 1, 409, 'Este servicio aún no está separado por sede. Ejecuta la migración de servicios independientes.');

        $address = $addresses->first();
        $field   = $owner instanceof Clinic ? 'clinic_id' : 'doctor_id';

        abort_unless((int) $address->{$field} === (int) $owner->id, 403, 'No tienes permisos para modificar este servicio médico.');

        return $address;
    }

    /**
     * Sedes de destino con su precio. Valida que todas pertenezcan al propietario.
     *
     * @return array<int, array{address: Address, price: float}>
     */
    private function resolveTargets(Doctor|Clinic $owner, array $data): array
    {
        if ($data['type'] === 'virtual') {
            $address = $owner->addresses()->where('type', 'virtual')->first() ?? $owner->createVirtualAddress();

            return [['address' => $address, 'price' => (float) $data['price_virtual']]];
        }

        $ids       = collect($data['address_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $addresses = $owner->addresses()->where('type', 'physical')->whereIn('addresses.id', $ids)->get()->keyBy('id');

        abort_if($addresses->count() !== $ids->count(), 403, 'Una o más sedes seleccionadas no pertenecen a tu consultorio.');

        return $ids->map(fn (int $id) => [
            'address' => $addresses[$id],
            'price'   => (float) $data['prices'][$id],
        ])->all();
    }

    /**
     * No puede haber dos servicios con el mismo nombre y tipo en la misma sede.
     */
    private function assertNameAvailable(Address $address, string $name, string $type, ?int $ignoreServiceId = null): void
    {
        $exists = Service::query()
            ->where('name', $name)
            ->where('type', $type)
            ->when($ignoreServiceId, fn ($query) => $query->where('services.id', '!=', $ignoreServiceId))
            ->whereHas('addresses', fn ($query) => $query->where('addresses.id', $address->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => "Ya tienes un servicio llamado \"{$name}\" en {$address->name}.",
            ]);
        }
    }

    private function specialtyPivot(array $specialtyIds, int $userId): array
    {
        return collect($specialtyIds)
            ->mapWithKeys(fn ($id) => [(int) $id => ['user_id' => $userId]])
            ->all();
    }

    private function invalidateCache(Doctor|Clinic $owner, Address $address): void
    {
        $doctorIds = $owner instanceof Doctor
            ? [$owner->id]
            : Schedule::where('address_id', $address->id)->whereNotNull('doctor_id')->distinct()->pluck('doctor_id')->all();

        if (!empty($doctorIds)) {
            $this->availabilityCache->invalidateAvailabilityCache(
                $owner instanceof Clinic ? $owner->id : null,
                $doctorIds,
                $address->id
            );
        }
    }
}