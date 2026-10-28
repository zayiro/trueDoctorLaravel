<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\Specialty;
use App\Repositories\AvailabilityRepository;
use App\Traits\ValidatesMultiTenantOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    use ValidatesMultiTenantOwnership;

    /**
     * Resuelve el propietario comercial (Doctor o Clínica) según el rol y contexto activo.
     * 
     * Usa $user->role (campo del modelo) en lugar de hasRole() de Spatie para consistencia
     * con el resto del sistema multi-tenant.
     */
    private function getOwner(): Doctor|Clinic
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $context = session('doctor_context');

        return match ($user->role) {
            'clinic' => $user->clinic ?? abort(404, 'Perfil de clínica no encontrado.'),
            'doctor' => $this->resolveDoctorOwner($user, $context),
            default  => abort(403, 'Perfil comercial no configurado.'),
        };
    }

    /**
     * Resuelve el owner para médicos: puede ser la clínica (contexto institucional)
     * o el doctor (consultorio particular).
     */
    private function resolveDoctorOwner($user, $context): Doctor|Clinic
    {
        if (($context['type'] ?? 'particular') === 'clinic') {
            return Clinic::find($context['id'])
                ?? abort(403, 'Clínica aliada no encontrada.');
        }

        return $user->doctor ?? abort(404, 'Perfil médico no encontrado.');
    }

    /**
     * Muestra la lista de servicios vinculados al propietario autenticado.
     */
    public function index()
    {
        $owner = $this->getOwner();
        $user  = Auth::user();
        $context = session('doctor_context');

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no encontrado.');
        }

        $services = $this->buildServicesQuery($owner, $user, $context)->get();
        $uniqueServicesCount = $services->count();

        //  Inyectar $currentContext para la vista (banner institucional)
        $currentContext = $context ?? [
            'type'  => 'particular',
            'id'    => null,
            'name'  => 'Consultorio Particular',
            'photo' => null,
        ];

        return view('partner.services.index', compact(
            'services',
            'owner',
            'uniqueServicesCount',
            'currentContext'
        ));
    }

    /**
     * Construye la consulta de servicios según el contexto (clínica vs particular).
     */
    private function buildServicesQuery($owner, $user, $context)
    {
        $isClinicContext = $user->role === 'doctor'
            && ($context['type'] ?? 'particular') === 'clinic';

        if ($isClinicContext) {
            return $this->buildClinicContextQuery($owner, $user, (int) $context['id']);
        }

        return $this->buildStandardQuery($owner, $user);
    }

    /**
     * Query para médico en contexto de clínica: solo especialidades compartidas.
     */
    private function buildClinicContextQuery($clinic, $user, int $clinicId)
    {
        $sharedSpecialtyIds = DB::table('doctor_specialty')
            ->where('doctor_id', $user->doctor->id)
            ->whereIn('specialty_id', function ($q) use ($clinicId) {
                $q->select('specialty_id')
                  ->from('clinic_specialty')
                  ->where('clinic_id', $clinicId);
            })
            ->pluck('specialty_id')
            ->toArray();

        return Service::whereHas('specialties', function ($query) use ($clinic, $sharedSpecialtyIds) {
            $query->where('service_specialty.user_id', $clinic->user_id)
                  ->whereIn('service_specialty.specialty_id', $sharedSpecialtyIds);
        })->with([
            'specialties',
            'addresses' => function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId)->with('city');
            }
        ]);
    }

    /**
     * Query estándar para consultorio particular o clínica pura.
     */
    private function buildStandardQuery($owner, $user)
    {
        $ownerField = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

        return Service::whereHas('specialties', function ($query) use ($user) {
            $query->where('service_specialty.user_id', $user->id);
        })->with([
            'specialties',
            'addresses' => function ($query) use ($ownerField, $owner) {
                $query->where($ownerField, $owner->id)->with('city');
            }
        ]);
    }

    /**
     * Muestra el formulario de creación cargando sedes y especialidades.
     */
    public function create()
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();

        $addresses      = $this->getPhysicalAddresses($owner);
        $specialties    = $owner->specialties()->where('status', true)->get();
        $hasAddresses   = $addresses->isNotEmpty();
        $hasSpecialties = $specialties->isNotEmpty();

        return view('partner.services.create', compact(
            'addresses',
            'specialties',
            'hasAddresses',
            'hasSpecialties',
        ));
    }

    /**
     * Almacena un nuevo servicio sincronizando especialidades y sedes con precios.
     */
    public function store(Request $request, AvailabilityRepository $cacheRepo)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();
        $user  = Auth::user();

        if (!$owner->canAddMoreServices()) {
            return redirect()->back()->with('error',
                'Has alcanzado el límite global de servicios permitidos en tu plan.');
        }

        $validated = $this->validateServiceRequest($request, $owner, $user);

        try {
            DB::transaction(function () use ($validated, $owner, $user, $cacheRepo) {
                $service = $this->findOrCreateService($validated['name'], $validated['type']);

                $this->syncServiceRelations($service, $validated, $owner, $user);

                $this->invalidateServiceCache($cacheRepo, $owner, $service);
            });

            return redirect()->route('partner.services.index')
                ->with('success', '¡Servicio configurado correctamente!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error',
                'Error al crear el servicio: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Valida el request de creación/actualización de servicio.
     */
    private function validateServiceRequest(Request $request, $owner, $user): array
    {
        $rules = [
            'name'          => ['required', 'string', 'max:100'],
            'type'          => ['required', 'in:physical,virtual'],
            'duration'      => ['required', 'integer', 'min:1', 'max:480'],
            'specialties'   => ['required', 'array', 'min:1'],
            'specialties.*' => ['exists:specialties,id'],
            'price_virtual' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'address_ids'   => ['nullable', 'array'],
            'address_ids.*' => ['exists:addresses,id,deleted_at,NULL'],
            'prices'        => ['nullable', 'array'],
        ];

        // Validación condicional: precio virtual obligatorio si es virtual
        if ($request->input('type') === 'virtual') {
            $rules['price_virtual'] = ['required', 'numeric', 'min:0', 'max:999999.99'];
        }

        // Validación condicional: sedes y precios obligatorios si es físico
        if ($request->input('type') === 'physical') {
            $rules['address_ids'] = ['required', 'array', 'min:1'];
            $rules['prices']      = ['required', 'array'];
        }

        $validated = $request->validate($rules, [
            'specialties.required'   => 'Debes asociar este servicio a al menos una especialidad médica.',
            'duration.max'           => 'La duración no puede exceder 480 minutos (8 horas).',
            'price_virtual.required' => 'Debes asignar un precio para consultas virtuales.',
        ]);

        // Validar precios por sede (solo si es físico)
        if ($validated['type'] === 'physical' && !empty($validated['address_ids'])) {
            foreach ($validated['address_ids'] as $addressId) {
                if (!isset($validated['prices'][$addressId]) || $validated['prices'][$addressId] === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "prices.{$addressId}" => 'Debes asignar un precio a todas las sedes seleccionadas.',
                    ]);
                }
            }
        }

        // Validar propiedad de especialidades y sedes
        $this->validateSpecialtiesOwnership($validated['specialties'], $user);

        if ($validated['type'] === 'physical') {
            $this->validateAddressesOwnership($validated['address_ids'], $user);
        }

        return $validated;
    }

    /**
     * Busca o crea un servicio global (catálogo compartido).
     * Normaliza el nombre para evitar duplicados por mayúsculas/espacios.
     */
    private function findOrCreateService(string $name, string $type): Service
    {
        $normalizedName = $this->normalizeServiceName($name);

        return Service::firstOrCreate(
            [
                'name' => $normalizedName,
                'type' => $type,
            ],
            ['active' => true]
        );
    }

    /**
     * Normaliza el nombre del servicio para búsqueda consistente.
     */
    private function normalizeServiceName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Sincroniza todas las relaciones del servicio (especialidades + sedes con precios).
     */
    private function syncServiceRelations(Service $service, array $validated, $owner, $user): void
    {
        // 1. Sincronizar especialidades con aislamiento por user_id
        $specialtySyncData = collect($validated['specialties'])
            ->mapWithKeys(fn ($id) => [$id => ['user_id' => $user->id]])
            ->all();

        $service->specialties()->sync($specialtySyncData);

        // 2. Sincronizar sedes con precios y duraciones
        $syncData = $this->buildAddressSyncData($validated, $owner, $service->type);
        $service->addresses()->sync($syncData);
    }

    /**
     * Construye los datos de sincronización para la tabla pivote address_service.
     */
    private function buildAddressSyncData(array $validated, $owner, string $type): array
    {
        if ($type === 'virtual') {
            return $this->buildVirtualAddressSync($owner, $validated);
        }

        return $this->buildPhysicalAddressSync($owner, $validated);
    }

    /**
     * Datos de sync para servicio virtual (usa la sede virtual del owner).
     */
    private function buildVirtualAddressSync($owner, array $validated): array
    {
        $virtualAddress = $owner->addresses()
            ->where('type', 'virtual')
            ->first() ?? $owner->createVirtualAddress();

        $this->validateAddressTypeForService($virtualAddress, 'virtual');

        return [
            $virtualAddress->id => [
                'price'    => round((float) $validated['price_virtual'], 2),
                'duration' => (int) $validated['duration'],
            ],
        ];
    }

    /**
     * Datos de sync para servicio físico (múltiples sedes con precios individuales).
     */
    private function buildPhysicalAddressSync($owner, array $validated): array
    {
        $syncData = [];

        foreach ($validated['address_ids'] as $addressId) {
            $address = Address::findOrFail($addressId);
            $this->validateAddressTypeForService($address, 'physical');

            $syncData[$addressId] = [
                'price'    => round((float) $validated['prices'][$addressId], 2),
                'duration' => (int) $validated['duration'],
            ];
        }

        return $syncData;
    }

    /**
     * Invalida el caché de disponibilidad para las sedes afectadas por el nuevo servicio.
     */
    private function invalidateServiceCache(AvailabilityRepository $cacheRepo, $owner, Service $service): void
    {
        $addressIds = $service->addresses()->pluck('id')->all();

        if (empty($addressIds)) {
            return;
        }

        // Obtener los doctor_ids asociados a esas sedes
        $doctorIds = Address::whereIn('id', $addressIds)
            ->whereNotNull('doctor_id')
            ->pluck('doctor_id')
            ->unique()
            ->all();

        // También incluir el doctor del owner si es médico particular
        if ($owner instanceof Doctor && !in_array($owner->id, $doctorIds)) {
            $doctorIds[] = $owner->id;
        }

        if (!empty($doctorIds)) {
            $clinicId = $owner instanceof Clinic ? $owner->id : null;

            foreach ($addressIds as $addressId) {
                $cacheRepo->invalidateAvailabilityCache($clinicId, $doctorIds, $addressId);
            }
        }
    }

    /**
     * Muestra el formulario de edición de un servicio.
     */
    public function edit(Service $service)
    {
        $this->denyIfInstitutionalContext();
        $user  = Auth::user();
        $owner = $this->getOwner();

        $this->validateServiceOwnership($service, $user);

        $addresses   = $this->getPhysicalAddresses($owner);
        $specialties = $owner->specialties()->where('status', true)->get();

        $attachedSpecialtyIds = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $user->id)
            ->pluck('specialty_id')
            ->toArray();

        $attachedAddressIds = $service->addresses()
            ->where('addresses.type', 'physical')
            ->pluck('addresses.id')
            ->toArray();

        return view('partner.services.edit', compact(
            'service',
            'addresses',
            'specialties',
            'attachedSpecialtyIds',
            'attachedAddressIds',
        ));
    }

    /**
     * Actualiza un servicio existente y sus relaciones.
     */
    public function update(Request $request, Service $service, AvailabilityRepository $cacheRepo)
    {
        $this->denyIfInstitutionalContext();
        $user  = Auth::user();
        $owner = $this->getOwner();

        $this->validateServiceOwnership($service, $user);

        $validated = $this->validateServiceRequest($request, $owner, $user);

        try {
            DB::transaction(function () use ($validated, $service, $owner, $user, $cacheRepo) {
                $service->update(['name' => $this->normalizeServiceName($validated['name'])]);

                $this->syncServiceRelations($service, $validated, $owner, $user);

                $this->invalidateServiceCache($cacheRepo, $owner, $service);
            });

            return redirect()->route('partner.services.index')
                ->with('success', 'Servicio médico actualizado con éxito.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error',
                'Error al actualizar el servicio: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Obtiene las sedes físicas activas del owner.
     */
    private function getPhysicalAddresses($owner)
    {
        return $owner->addresses()
            ->with('city')
            ->where('status', true)
            ->where('type', 'physical')
            ->get();
    }
}