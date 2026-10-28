<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialty;
use App\Repositories\AvailabilityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicServiceController extends Controller
{
    /**
     * Resuelve el contexto comercial de la clínica autenticada.
     * 
     *  Usa $user->role (campo del modelo) en lugar de hasRole() de Spatie
     * para consistencia con el resto del sistema multi-tenant.
     */
    private function getClinicContext()
    {
        $user = Auth::user();
        
        if ($user->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para administradores de centros médicos.');
        }
        
        return $user->clinic ?? abort(404, 'Perfil institucional de la clínica no encontrado.');
    }

    /**
     * Listar la grilla de servicios que la clínica ofrece en sus respectivas sedes.
     */
    public function index()
    {
        $clinic = $this->getClinicContext();
        $clinic->load('plan');

        // Sedes físicas o virtuales de la clínica (excluyendo sedes de médicos independientes)
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        $addressIds = $addresses->pluck('id');

        // Servicios asociados a las sedes de la clínica
        $services = Service::where('active', true)
            ->whereHas('addresses', function ($query) use ($addressIds) {
                $query->whereIn('address_id', $addressIds);
            })
            ->with([
                'specialties',
                'addresses' => function ($query) use ($addressIds) {
                    $query->whereIn('address_id', $addressIds)->withPivot('price', 'duration');
                }
            ])
            ->get();

        // Catálogo global para selectores de vinculación
        $globalServices = Service::where('active', true)->get();

        // Conteo de servicios únicos asignados a la clínica
        $uniqueServicesCount = DB::table('service_specialty')
            ->where('user_id', $clinic->user_id)
            ->distinct('service_id')
            ->count();

        return view('partner.clinic.services.index', compact(
            'clinic',
            'services',
            'addresses',
            'globalServices',
            'uniqueServicesCount'
        ));
    }

    /**
     * Muestra el formulario para registrar y vincular un nuevo servicio clínico.
     */
    public function create()
    {
        $clinic = $this->getClinicContext();

        // Sedes de la clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        $hasAddresses = $addresses->where('type', 'physical')->count() > 0;

        //  CORRECCIÓN: Cargar especialidades reales de la clínica
        $specialties = Specialty::whereIn('id', function ($query) use ($clinic) {
            $query->select('specialty_id')
                ->from('clinic_specialty')
                ->where('clinic_id', $clinic->id);
        })->where('status', true)->get();

        // Catálogo de servicios globales activos
        $globalServices = Service::where('active', true)->get();

        return view('partner.clinic.services.create', compact(
            'clinic',
            'addresses',
            'globalServices',
            'hasAddresses',
            'specialties'
        ));
    }

    /**
     * Registrar un nuevo servicio o enlazar uno existente distribuyendo tarifas por sede.
     */
    public function store(Request $request, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // Validación
        $validated = $request->validate([
            'service_name'  => 'required|string|max:100',
            'type'          => 'required|in:physical,virtual',
            'duration'      => 'required|integer|min:1|max:480',
            'specialties'   => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
            'address_ids'   => 'required_if:type,physical|array|min:1',
            'address_ids.*' => 'exists:addresses,id',
            'prices'        => 'required_if:type,physical|array',
            'price_virtual' => 'required_if:type,virtual|nullable|numeric|min:0|max:999999.99',
        ], [
            'specialties.required' => 'Debes asociar este servicio a al menos una especialidad médica.',
            'duration.max'         => 'La duración no puede exceder 480 minutos (8 horas).',
            'price_virtual.required_if' => 'Debes asignar un precio para consultas virtuales.',
        ]);

        // Validar que las especialidades pertenezcan a la clínica
        $this->validateClinicSpecialties($validated['specialties'], $clinic);

        // Validar que las sedes pertenezcan a la clínica
        if ($validated['type'] === 'physical') {
            $this->validateClinicAddresses($validated['address_ids'], $clinic);
        }

        // Control de límites del plan SaaS
        $plan = $clinic->plan;
        if ($plan) {
            $currentCount = DB::table('service_specialty')
                ->where('user_id', $clinic->user_id)
                ->distinct('service_id')
                ->count();
            
            if ($currentCount >= ($plan->max_services ?? 0)) {
                return redirect()->back()->withInput()->withErrors([
                    'limit' => "Has agotado la cuota de servicios ({$plan->max_services}) permitidos en tu suscripción."
                ]);
            }
        }

        try {
            DB::transaction(function () use ($validated, $clinic, $cacheRepo) {
                // Normalizar nombre del servicio
                $normalizedName = $this->normalizeServiceName($validated['service_name']);

                // Buscar o crear servicio global
                $service = Service::firstOrCreate(
                    [
                        'name' => $normalizedName,
                        'type' => $validated['type'],
                    ],
                    ['active' => true]
                );

                // Vincular servicio a especialidades con aislamiento por user_id de la clínica
                $specialtySyncData = collect($validated['specialties'])
                    ->mapWithKeys(fn ($id) => [$id => ['user_id' => $clinic->user_id]])
                    ->all();

                $service->specialties()->sync($specialtySyncData);

                // Vincular servicio a sedes con precios
                if ($validated['type'] === 'virtual') {
                    $this->syncVirtualService($service, $clinic, $validated);
                } else {
                    $this->syncPhysicalService($service, $clinic, $validated);
                }

                // Invalidar caché de disponibilidad
                $this->invalidateServiceCache($cacheRepo, $clinic, $service);
            });

            return redirect()->route('partner.clinic.services.index')
                ->with('success', 'El servicio institucional y sus respectivas tarifas fueron almacenados con éxito en tu catálogo.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->withErrors([
                'error' => 'Fallo de consistencia relacional: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Muestra el formulario para editar un servicio ya vinculado a la clínica.
     */
    public function edit($serviceId)
    {
        $clinic = $this->getClinicContext();

        //  CORRECCIÓN: Cargar el servicio con sus relaciones
        $service = Service::where('id', $serviceId)
            ->whereHas('specialties', function ($query) use ($clinic) {
                $query->where('service_specialty.user_id', $clinic->user_id);
            })
            ->with(['addresses', 'specialties'])
            ->firstOrFail();

        //  CORRECCIÓN: Cargar especialidades reales de la clínica
        $specialties = Specialty::whereIn('id', function ($query) use ($clinic) {
            $query->select('specialty_id')
                ->from('clinic_specialty')
                ->where('clinic_id', $clinic->id);
        })->where('status', true)->get();

        // IDs de especialidades ya vinculadas a este servicio por la clínica
        $attachedSpecialtyIds = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $clinic->user_id)
            ->pluck('specialty_id')
            ->toArray();

        // Sedes de la clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        return view('partner.clinic.services.edit', compact(
            'clinic',
            'service',
            'addresses',
            'specialties',
            'attachedSpecialtyIds'
        ));
    }

    /**
     * Actualiza el catálogo institucional (Servicio, Especialidades y Tarifas por Sede).
     */
    public function update(Request $request, $serviceId, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // Validación
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'duration'        => 'required|integer|min:1|max:480',
            'specialties'     => 'nullable|array',
            'specialties.*'   => 'exists:specialties,id',
            'address_ids'     => 'nullable|array',
            'address_ids.*'   => 'exists:addresses,id',
            'prices'          => 'nullable|array',
            'price_virtual'   => 'nullable|numeric|min:0|max:999999.99',
        ]);

        // Cargar servicio con validación de propiedad
        $service = Service::where('id', $serviceId)
            ->whereHas('specialties', function ($query) use ($clinic) {
                $query->where('service_specialty.user_id', $clinic->user_id);
            })
            ->firstOrFail();

        // Validar que las especialidades pertenezcan a la clínica
        if (!empty($validated['specialties'])) {
            $this->validateClinicSpecialties($validated['specialties'], $clinic);
        }

        // Validar que las sedes pertenezcan a la clínica
        if (!empty($validated['address_ids'])) {
            $this->validateClinicAddresses($validated['address_ids'], $clinic);
        }

        try {
            DB::transaction(function () use ($validated, $service, $clinic, $cacheRepo) {
                // Actualizar nombre del servicio (normalizado)
                $service->update([
                    'name' => $this->normalizeServiceName($validated['name']),
                ]);

                // Sincronizar especialidades con aislamiento por user_id
                if (isset($validated['specialties'])) {
                    $specialtySyncData = collect($validated['specialties'])
                        ->mapWithKeys(fn ($id) => [$id => ['user_id' => $clinic->user_id]])
                        ->all();
                    $service->specialties()->sync($specialtySyncData);
                }

                // Sincronizar sedes con precios
                if ($service->type === 'virtual') {
                    $this->syncVirtualService($service, $clinic, $validated);
                } else {
                    $this->syncPhysicalService($service, $clinic, $validated);
                }

                // Invalidar caché de disponibilidad
                $this->invalidateServiceCache($cacheRepo, $clinic, $service);
            });

            return redirect()->route('partner.clinic.services.edit', $service->id)
                ->with('success', 'El catálogo institucional se ha actualizado correctamente.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->withErrors([
                'error' => 'Fallo de consistencia relacional: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Desvincular un servicio de una sede específica de la clínica.
     */
    public function destroy($serviceId, $addressId, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // Validar propiedad del servicio
        $service = Service::where('id', $serviceId)
            ->whereHas('specialties', function ($query) use ($clinic) {
                $query->where('service_specialty.user_id', $clinic->user_id);
            })
            ->firstOrFail();

        // Validar que la sede pertenezca a la clínica
        $address = Address::where('id', $addressId)
            ->where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->firstOrFail();

        //  CORRECCIÓN: Validar citas futuras antes de desvincular
        $hasFutureAppointments = Appointment::where('address_id', $address->id)
            ->where('service_id', $service->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasFutureAppointments) {
            return back()->with('error', 'No puedes desvincular este servicio porque tiene citas agendadas. Debes cancelarlas o reprogramarlas primero.');
        }

        // Desvincular servicio de la sede
        $address->services()->detach($service->id);

        // Invalidar caché
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (!empty($doctorIds)) {
            $cacheRepo->invalidateAvailabilityCache($clinic->id, $doctorIds, $address->id);
        }

        return redirect()->route('partner.clinic.services.index')
            ->with('success', 'El servicio ha sido removido de la sede institucional correctamente.');
    }

    /**
     * Valida que todas las especialidades pertenezcan a la clínica.
     */
    private function validateClinicSpecialties(array $specialtyIds, $clinic): void
    {
        $validCount = DB::table('clinic_specialty')
            ->where('clinic_id', $clinic->id)
            ->whereIn('specialty_id', $specialtyIds)
            ->count();

        if ($validCount !== count($specialtyIds)) {
            abort(403, 'Una o más especialidades no pertenecen a tu clínica.');
        }
    }

    /**
     * Valida que todas las sedes pertenezcan a la clínica.
     */
    private function validateClinicAddresses(array $addressIds, $clinic): void
    {
        $validCount = Address::whereIn('id', $addressIds)
            ->where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->count();

        if ($validCount !== count($addressIds)) {
            abort(403, 'Una o más sedes seleccionadas no pertenecen a tu clínica.');
        }
    }

    /**
     * Normaliza el nombre del servicio para búsqueda consistente.
     */
    private function normalizeServiceName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Sincroniza un servicio virtual con la sede digital de la clínica.
     */
    private function syncVirtualService(Service $service, $clinic, array $validated): void
    {
        // Buscar o crear sede virtual de la clínica
        $virtualAddress = Address::where('clinic_id', $clinic->id)
            ->where('type', 'virtual')
            ->whereNull('doctor_id')
            ->first();

        if (!$virtualAddress) {
            $virtualAddress = Address::create([
                'clinic_id' => $clinic->id,
                'doctor_id' => null,
                'name'      => 'Sede Digital - Telemedicina',
                'address'   => 'Plataforma Online',
                'type'      => 'virtual',
                'status'    => true,
            ]);
        }

        $service->addresses()->sync([
            $virtualAddress->id => [
                'price'    => round((float) ($validated['price_virtual'] ?? 0), 2),
                'duration' => (int) $validated['duration'],
            ]
        ]);
    }

    /**
     * Sincroniza un servicio físico con las sedes seleccionadas.
     */
    private function syncPhysicalService(Service $service, $clinic, array $validated): void
    {
        $syncData = [];

        foreach ($validated['address_ids'] as $addressId) {
            $syncData[$addressId] = [
                'price'    => round((float) ($validated['prices'][$addressId] ?? 0), 2),
                'duration' => (int) $validated['duration'],
            ];
        }

        $service->addresses()->sync($syncData);
    }

    /**
     * Invalida el caché de disponibilidad para las sedes afectadas.
     */
    private function invalidateServiceCache(AvailabilityRepository $cacheRepo, $clinic, Service $service): void
    {
        $addressIds = $service->addresses()->pluck('id')->all();

        if (empty($addressIds)) {
            return;
        }

        $doctorIds = Schedule::whereIn('address_id', $addressIds)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (!empty($doctorIds)) {
            foreach ($addressIds as $addressId) {
                $cacheRepo->invalidateAvailabilityCache($clinic->id, $doctorIds, $addressId);
            }
        }
    }
}