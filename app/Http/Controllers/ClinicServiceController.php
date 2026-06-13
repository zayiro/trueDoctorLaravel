<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicServiceController extends Controller
{
    /**
     * Helper metodológico para capturar el contexto comercial de la clínica.
     */
    private function getClinicContext()
    {
        $user = Auth::user();

        if ($user->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para administradores de centros médicos.');
        }

        $clinic = $user->clinic;

        if (!$clinic) {
            abort(404, 'Perfil institucional de la clínica no encontrado.');
        }

        return $clinic;
    }
    
        /**
     * Listar la grilla de servicios que la clínica ofrece en sus respectivas sedes.
     */
    public function index()
    {
        // 1. Capturar el contexto comercial de la clínica e incluir su plan SaaS
        $clinic = $this->getClinicContext();
        $clinic->load('plan');

        // 🎯 SOPORTE MULTI-TENANT: Definimos el alias $owner exigido por la vista
        $owner = $clinic;

        // 2. Obtener únicamente las sedes físicas o virtuales de esta clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id') // Excluimos sedes independientes de médicos
            ->whereNull('deleted_at') // Respetando SoftDeletes
            ->get();

        $addressIds = $addresses->pluck('id');

        // 3. REFACTORIZACIÓN CRÍTICA: Mapear la variable $services con Eager Loading
        // Cargamos los servicios globales asociados a estas sedes con sus especialidades e infraestructura pivot
        $services = Service::where('active', true)
            ->whereHas('addresses', function($query) use ($addressIds) {
                $query->whereIn('address_id', $addressIds);
            })
            ->with([
                'specialties', // Necesario para la segunda columna de tu tabla ($service->specialties)
                'addresses' => function($query) use ($addressIds) {
                    $query->whereIn('address_id', $addressIds)->withPivot('price', 'duration');
                }
            ])
            ->get();

        // 4. Catálogo global para flujos alternos o selectores de vinculación
        $globalServices = Service::where('active', true)->get();

        // 5. Conteo Multi-Tenant real de servicios únicos asignados en este inquilino
        $uniqueServicesCount = DB::table('service_specialty')
            ->where('user_id', auth()->id())
            ->count();

        // 🚀 RETORNO COMPLETO: Empaquetamos todas las variables para dar inmunidad total a la vista
        return view('partner.clinic.services.index', compact(
            'clinic',
            'owner',
            'services',
            'addresses',
            'globalServices',
            'uniqueServicesCount'
        ));
    }
    
        /**
     * Registrar un nuevo servicio o enlazar uno existente distribuyendo tarifas por sede.
     */
    public function store(Request $request)
    {
        $clinic = $this->getClinicContext();
        $plan = $clinic->plan ?? auth()->user()->plan;

        // 🎯 FIX DE CONVERGENCIA MULTI-TENANT: Adaptamos los datos si Alpine los procesó como arreglos
        if ($request->has('address_ids') && !$request->has('address_id')) {
            $addressArray = $request->input('address_ids');
            if (!empty($addressArray)) {
                $request->merge(['address_id' => reset($addressArray)]);
            }
        }

        // Si es un canal virtual, inyectamos la sede digital corporativa para saltar el validador rígido
        if ($request->input('type') === 'virtual') {
            $virtualAddress = Address::where('clinic_id', $clinic->id)->where('type', 'virtual')->first();
            if ($virtualAddress) {
                $request->merge(['address_id' => $virtualAddress->id]);
            }
        }

        // 1. Validación Adaptativa Multi-Sede y Multi-Especialidad
        $request->validate([
            'service_id'   => 'required|string|max:255', // Captura el nombre de texto o ID numérico
            'duration'     => 'required|integer|in:15,20,30,45,60',
            'type'         => 'required|in:physical,virtual',
            'specialties'  => 'required|array|min:1',
            'specialties.*'=> 'exists:specialties,id',
            // Validaciones combinadas flexibles
            'address_id'   => 'required_if:type,physical|exists:addresses,id',
            'price_virtual'=> 'required_if:type,virtual|nullable|numeric|min:0',
            'address_ids'  => 'required_if:type,physical|nullable|array|min:1',
            'prices'       => 'required_if:type,physical|nullable|array',
        ]);

        // 2. Control de Límites del Plan SaaS Corporativo
        $maxServicesAllowed = $plan->max_services ?? 0;
        $currentCount = DB::table('service_specialty')->where('user_id', auth()->id())->count();

        if ($currentCount >= $maxServicesAllowed) {
            return redirect()->back()->withInput()->withErrors(['limit' => "Has agotado la cuota de servicios ({$maxServicesAllowed}) permitidos en tu suscripción."]);
        }

        try {
            // 3. Ejecutar bloque transaccional atómico
            DB::transaction(function() use ($request, $clinic) {
                
                $inputService = $request->service_id;
                
                // Determinar si es un servicio existente (numérico) o uno nuevo (texto de creación)
                if (!is_numeric($inputService)) {
                    $existingService = Service::where('name', 'LIKE', trim($inputService))->first();
                    
                    if ($existingService) {
                        $finalServiceId = $existingService->id;
                    } else {
                        // Crear el servicio global heredando la modalidad del formulario
                        $newGlobalService = Service::create([
                            'name'   => trim($inputService),
                            'type'   => $request->type,
                            'active' => true,
                        ]);
                        $finalServiceId = $newGlobalService->id;
                    }
                } else {
                    $finalServiceId = (int) $inputService;
                }

                // Paso A: Vincular el servicio a las especialidades bajo el user_id de la organización
                foreach ($request->specialties as $specialtyId) {
                    DB::table('service_specialty')->updateOrInsert(
                        [
                            'service_id'   => $finalServiceId,
                            'specialty_id' => $specialtyId,
                            'user_id'      => auth()->id(), // Aislamiento Multi-Tenant
                        ],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                // Paso B: Distribuir tarifas comerciales e infraestructura
                if ($request->type === 'virtual') {
                    // Buscar o autogenerar la sede digital corporativa para video-consultas
                    $virtualAddress = Address::where('clinic_id', $clinic->id)->where('type', 'virtual')->first();
                    
                    if (!$virtualAddress) {
                        $virtualAddress = Address::create([
                            'clinic_id' => $clinic->id,
                            'doctor_id' => null,
                            'name'      => 'Sede Digital - Telemedicina',
                            'type'      => 'virtual',
                        ]);
                    }

                    DB::table('address_service')->updateOrInsert(
                        ['address_id' => $virtualAddress->id, 'service_id' => $finalServiceId],
                        ['price' => $request->price_virtual, 'duration' => $request->duration, 'updated_at' => now(), 'created_at' => now()]
                    );
                } else {
                    // Mapear cada una de las sedes físicas seleccionadas de forma diferencial
                    foreach ($request->address_ids as $addressId) {
                        $ownsAddress = Address::where('id', $addressId)->where('clinic_id', $clinic->id)->exists();
                        
                        if (!$ownsAddress) {
                            throw new \Exception('Se detectó un intento de alteración de infraestructura no autorizada.');
                        }

                        $price = $request->prices[$addressId] ?? 0;

                        DB::table('address_service')->updateOrInsert(
                            ['address_id' => $addressId, 'service_id' => $finalServiceId],
                            ['price' => $price, 'duration' => $request->duration, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            });

            return redirect()->route('partner.clinic.services.index')
                ->with('success', 'El servicio institucional y sus respectivas tarifas fueron almacenados con éxito en tu catálogo.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Fallo de consistencia relacional: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra el formulario para registrar y vincular un nuevo servicio clínico.
     */
    public function create()
    {
        // 1. Capturar el contexto comercial de la clínica autenticada
        $clinic = $this->getClinicContext();
        $owner = $clinic;

        // 2. Obtener únicamente las sedes físicas o virtuales de esta clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        // 3. Bandera operativa para evaluar si existen sedes físicas activas
        $hasAddresses = $addresses->where('type', 'physical')->count() > 0;

        // 4. 🔒 SOLUCIÓN AL ERROR: Consulta directa a la tabla intermedia clinic_specialty
        // Extraemos las especialidades asociadas a esta clínica sin depender de la relación en el modelo Specialty
        $specialties = \App\Models\Specialty::whereIn('id', function($query) use ($clinic) {
            $query->select('specialty_id')
                  ->from('clinic_specialty')
                  ->where('clinic_id', $clinic->id);
        })->where('status', true)->get();

        // 5. Cargar catálogo de procedimientos globales activos
        $globalServices = Service::where('active', true)->get();

        // 🚀 RETORNO INTEGRAL SIN COLISIONES DE MODELOS
        return view('partner.clinic.services.create', compact(
            'clinic',
            'owner',
            'addresses',
            'globalServices',
            'hasAddresses',
            'specialties'
        ));
    }

        /**
     * Muestra el formulario para editar la tarifa de un servicio ya vinculado.
     */
    public function edit($addressId, $serviceId)
    {
        $clinic = $this->getClinicContext();

        // Seguridad Multi-tenant: Validamos que la sede pertenezca a la clínica
        $address = Address::where('id', $addressId)->where('clinic_id', $clinic->id)->firstOrFail();
        
        // Traemos el servicio cargando los datos de la tabla pivote real address_service
        $service = Service::where('id', $serviceId)
            ->whereHas('addresses', function($q) use ($addressId) { $q->where('address_id', $addressId); })
            ->firstOrFail();

        // Inyectamos el objeto address con su relación cargada en memoria para leer $address->pivot
        $address = $service->addresses()->where('address_id', $addressId)->first();

        // --- SOLUCIÓN DE EMERGENCIA PARA PRODUCCIÓN ---
        // Inicializamos las variables ausentes para evitar errores en las líneas 58, 59 y 86 de la vista
        $specialties = collect(); 
        $attachedSpecialtyIds = []; 
        $addresses = collect([$address]); 

        return view('partner.clinic.services.edit', compact(
            'clinic',
            'service', 
            'address', 
            'addresses', 
            'specialties', 
            'attachedSpecialtyIds'
        ));
    }

        /**
     * Actualiza el catálogo institucional (Servicio, Especialidades y Tarifas por Sede).
     */
    public function update(Request $request, $addressId, $serviceId)
    {
        // 1. Validamos estrictamente los inputs del formulario
        $request->validate([
            'name'            => 'required|string|max:255',
            'duration'        => 'required|integer|in:15,20,30,45,60',
            'specialties'     => 'nullable|array',
            'specialties.*'   => 'exists:specialties,id',
            'address_ids'     => 'nullable|array',
            'prices'          => 'nullable|array',
            'price_virtual'   => 'nullable|numeric|min:0',            
        ]);

        $clinic = $this->getClinicContext();

        // 2. Seguridad Multi-tenant
        $address = Address::where('id', $addressId)->where('clinic_id', $clinic->id)->firstOrFail();
        $service = Service::where('id', $serviceId)->firstOrFail();

        // 3. Actualizamos el nombre global del servicio
        $service->update([
            'name' => $request->name,
        ]);

        // 4. Sincronizamos el Módulo Taxonómico (Especialidades)
        $service->specialties()->sync($request->input('specialties', []));

        // 5. Persistencia corporativa de tarifas indexadas según modalidad
        if ($service->type === 'virtual') {
            $virtualAddress = Address::where('clinic_id', $clinic->id)
                ->where('type', 'virtual')
                ->first();

            if ($virtualAddress) {
                $service->addresses()->sync([
                    $virtualAddress->id => [
                        'price'    => $request->input('price_virtual', 0),
                        'duration' => $request->duration
                    ]
                ]);
            }
        } else {
            $syncData = [];
            $selectedAddresses = $request->input('address_ids', []);
            $prices = $request->input('prices', []);

            foreach ($selectedAddresses as $id) {
                // Validación estricta en caliente antes de guardar
                $belongsToClinic = Address::where('id', $id)->where('clinic_id', $clinic->id)->exists();
                
                if ($belongsToClinic) {
                    $syncData[$id] = [
                        'price'    => $prices[$id] ?? 0,
                        'duration' => $request->duration
                    ];
                }
            }

            // sync() guarda las sedes seleccionadas con sus precios y elimina las desmarcadas
            $service->addresses()->sync($syncData);
        }

        return redirect()
            ->route('partner.clinic.services.edit', [$addressId, $serviceId])
            ->with('success', 'El catálogo institucional se ha actualizado correctamente.');
    }


    /**
     * Desvincular un servicio de una sede específica de la clínica (Rompe la fila pivote).
     */
    public function destroy($addressId, $serviceId)
    {
        $clinic = $this->getClinicContext();

        // Confirmar propiedad de la sede antes de eliminar el mapeo comercial
        $address = Address::where('id', $addressId)
            ->where('clinic_id', $clinic->id)
            ->firstOrFail();

        // Rompemos la fila en la tabla intermedia address_service
        $address->services()->detach($serviceId);

        return redirect()->route('partner.clinic.services.index')
            ->with('success', 'El servicio ha sido removido de la sede institucional correctamente.');
    }
} // Cierre definitivo de la clase ClinicServiceController
