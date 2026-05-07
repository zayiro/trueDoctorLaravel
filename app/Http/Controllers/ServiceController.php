<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $doctor = auth()->user()->doctor()->with(['plan', 'addresses.services'])->first();

        if (!$doctor) {
            return redirect()->back()->with('error', 'Perfil de doctor no encontrado.');
        }

        // Obtenemos los servicios únicos para el conteo real frente al plan
        $uniqueServicesCount = $doctor->addresses->flatMap->services->unique('id')->count();

        // Aplanamos para la tabla (aquí sí se pueden repetir si quieres verlos por sede)
        $services = $doctor->addresses->flatMap(function ($address) {
            return $address->services->map(function ($service) use ($address) {
                $service->address_name = $address->address_line; 
                return $service;
            });
        });

        //dd($services);

        return view('partner.services.index', compact('services', 'doctor', 'uniqueServicesCount'));
    }

    public function create()
    {
        $doctor = auth()->user()->doctor;

        // Cargamos las sedes con su ciudad para tener el nombre disponible
        $addresses = $doctor->addresses()
            ->with('city') 
            ->where('status', true)
            ->where('type', 'physical')
            ->get();

        // Usamos la colección ya cargada para evitar más consultas a la BD
        $hasAddresses = $addresses->where('type', 'physical')->isNotEmpty();

        
            
        return view('partner.services.create', compact('addresses', 'hasAddresses'));
    }

    public function store(Request $request)
    {
        $doctor = auth()->user()->doctor;

        if (!$doctor->canAddMoreServices()) {
            return redirect()->back()->with('error', 'Has alcanzado el límite global de servicios.');
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'price'         => 'required|numeric|min:0',
            'type'          => 'required|in:physical,virtual',
            'duration'      => 'required|integer',
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id',
                function ($attribute, $value, $fail) use ($doctor) {
                    if (!$doctor->addresses()->where('id', $value)->exists()) {
                        $fail('Dirección no válida.');
                    }
                },
            ],
        ]);

        $service = Service::create([
            'name'     => $validated['name'],
            'price'    => $validated['price'],
            'duration' => $validated['duration'],
            'type'     => $validated['type'],
        ]);

        if ($validated['type'] === 'virtual') {
            // Buscamos la sede virtual
            $virtualAddress = $doctor->addresses()->where('type', 'virtual')->first();

            // Si por alguna razón no existe, la creamos
            if (!$virtualAddress) {
                $virtualAddress = $doctor->createVirtualAddress();
            }

            // ASOCIAR SOLO A LA SEDE VIRTUAL (Evita duplicados en el index)
            $service->addresses()->sync([$virtualAddress->id]);
        } else {
            $service->addresses()->sync($validated['address_ids']);
        }

        return redirect()->route('partner.services.index')->with('success', '¡Servicio creado!');
    }

    public function edit(Service $service)
    {
        $doctor = auth()->user()->doctor;

        // SEGURIDAD: Verificar que el servicio esté vinculado a al menos una dirección del doctor
        $belongsToDoctor = $service->addresses()
            ->where('doctor_id', $doctor->id)
            ->exists();

        if (!$belongsToDoctor) {
            abort(403, 'No tienes permiso para editar este servicio.');
        }

        // Obtener las sedes activas del doctor
        $addresses = $doctor->addresses()->where('status', true)->get();
        
        // Obtener los IDs de las sedes que ya tienen este servicio asignado
        $selectedAddressIds = $service->addresses()->pluck('addresses.id')->toArray();

        return view('partner.services.edit', compact('service', 'addresses', 'selectedAddressIds'));
    }

    public function update(Request $request, Service $service)
    {
        if ($service->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:physical,virtual',
            'duration' => 'required|integer',
            'address_ids' => 'required_if:type,physical|array',
            'address_ids.*' => 'exists:addresses,id',
        ]);

        // Actualizar datos básicos
        $service->update($validated);

        // Sincronizar sedes
        if ($request->type === 'physical') {
            $service->addresses()->sync($request->address_ids);
        } else {
            // Si cambió de physical a virtual, quitamos todas las sedes
            $service->addresses()->detach();
        }

        return redirect()->route('doctor.services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function toggleStatus(Service $service)
    {
        $this->authorizeOwner($service);

        // Cambia de true a false o viceversa
        $service->update(['active' => !$service->active]);

        $textoStatus = $service->active ? 'activado' : 'desactivado';

        return back()->with('success', "El servicio ha sido {$textoStatus} correctamente.");
    }
    
    public function destroy(Service $service)
    {
        $partner = auth()->user()->doctor->id;
        // 1. Verificamos si existe alguna sede del partner vinculada a este servicio
        $isOwner = $service->addresses()
            ->where('addresses.doctor_id', $partner) // Especificas la tabla para evitar ambigüedad
            ->exists();

        if (!$isOwner) {
            return back()->with('error', 'No tienes permiso para eliminar este servicio.');
        }

        // 2. Verificamos integridad (Regla de negocio)
        // Asumiendo que tienes la relación 'appointments' definida en el modelo Service
        if ($service->appointments()->exists()) {
            return back()->with('warning', 'El servicio no puede eliminarse porque tiene citas registradas.');
        }

        // 3. Ejecutamos eliminación
        $service->delete();

        return redirect()->route('partner.services.index')->with('success', 'Servicio eliminado correctamente.');
    }

    /**
     * Método privado para seguridad de registros.
    */
    private function authorizeOwner(Service $service)
    {
        $doctorId = auth()->user()->doctor->id;

        // Verificamos si el servicio está conectado a alguna sede de este doctor
        $isOwner = $service->addresses()
            ->where('doctor_id', $doctorId)
            ->exists();

        if (!$isOwner) {
            abort(403, 'No tienes permiso para gestionar este servicio.');
        }
    }
}
