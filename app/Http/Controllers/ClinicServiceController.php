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
        $clinic = $this->getClinicContext();

        // 1. Obtener únicamente las sedes físicas o sucursales de esta clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id') // Excluimos sedes independientes de médicos
            ->whereNull('deleted_at') // Respetando SoftDeletes
            ->get();

        $addressIds = $addresses->pluck('id');

        // 2. Cargar los servicios globales vinculados actualmente a esas sedes con precio y duración
        $linkedServices = Service::where('active', true)
            ->whereHas('addresses', function($query) use ($addressIds) {
                $query->whereIn('address_id', $addressIds);
            })
            ->with(['addresses' => function($query) use ($addressIds) {
                $query->whereIn('address_id', $addressIds)->withPivot('price', 'duration');
            }])
            ->get();

        // 3. Catálogo global administrado para el formulario de vinculación
        $globalServices = Service::where('active', true)->get();

        // Retorno a la ruta unificada de carpetas dentro de resources/views/partner/clinic/
        return view('partner.clinic.services.index', compact('addresses', 'linkedServices', 'globalServices', 'clinic'));
    }
    
    /**
     * Vincular un servicio existente o crear uno nuevo globalmente e indexarlo a la sede.
     */
    public function store(Request $request)
    {
        $clinic = $this->getClinicContext();
        $plan = $clinic->settings->plan;

        // 1. Validación adaptativa (service_id puede ser un ID numérico o el texto de un servicio nuevo)
        $validated = $request->validate([
            'service_id' => 'required|string|max:255', 
            'address_id' => 'required|exists:addresses,id',
            'price'      => 'required|numeric|min:0',
            'duration'   => 'required|integer|min:5|max:480',
        ]);

        // Seguridad Multi-tenant: Validar propiedad de la sede
        $address = Address::where('id', $validated['address_id'])
            ->where('clinic_id', $clinic->id)
            ->firstOrFail();

        // 2. Determinar si es un servicio existente (numérico) o uno nuevo (texto)
        $serviceId = $validated['service_id'];
        
        if (!is_numeric($serviceId)) {
            // Verificar si otro usuario ya creó un servicio con ese nombre exacto de forma preventiva
            $existingService = Service::where('name', 'LIKE', trim($serviceId))->first();
            
            if ($existingService) {
                $serviceId = $existingService->id;
            } else {
                // Crear el servicio en el catálogo global automáticamente para que esté disponible para todos
                $newGlobalService = Service::create([
                    'name'   => trim($validated['service_id']),
                    'type'   => $address->type === 'virtual' ? 'virtual' : 'physical', // Hereda el tipo de la sede destino
                    'active' => true,
                ]);
                $serviceId = $newGlobalService->id;
            }
        }

        // 3. REGLA DE NEGOCIO SAAS: Contar servicios ÚNICOS ya mapeados en la clínica
        $currentServicesCount = DB::table('address_service')
            ->whereIn('address_id', function($query) use ($clinic) {
                $query->select('id')->from('addresses')->where('clinic_id', $clinic->id)->whereNull('deleted_at');
            })
            ->distinct('service_id')
            ->count();

        $alreadyAttached = $address->services()->where('service_id', $serviceId)->exists();

        if (!$alreadyAttached && $currentServicesCount >= $plan->max_services) {
            return back()->with('error', "Has alcanzado el tope máximo de servicios ({$plan->max_services}) permitidos en tu plan.");
        }

        // 4. Sincronizar en la tabla pivote real 'address_service'
        $address->services()->syncWithoutDetaching([
            $serviceId => [
                'price'      => $validated['price'],
                'duration'   => $validated['duration'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        return redirect()->route('partner.clinic.services.index')
            ->with('success', 'El servicio ha sido configurado y guardado en tu catálogo exitosamente.');
    }

    /**
     * Muestra el formulario para vincular un nuevo servicio.
     */
    public function create()
    {
        $clinic = $this->getClinicContext();
        $addresses = Address::where('clinic_id', $clinic->id)->whereNull('doctor_id')->whereNull('deleted_at')->get();
        $globalServices = Service::where('active', true)->get();

        return view('partner.clinic.services.create', compact('addresses', 'globalServices', 'clinic'));
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

        return view('partner.clinic.services.edit', compact('address', 'service', 'clinic'));
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
