<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $doctor = auth()->user()->doctor;

        // Obtenemos todas las direcciones del doctor con sus servicios cargados
        $addresses = $doctor->addresses()->with('services')->get();

        // Si lo que necesitas en la vista es una colección plana de servicios 
        // pero que cada servicio sepa su sede:
        $services = $addresses->flatMap(function ($address) {
            return $address->services->map(function ($service) use ($address) {
                // Opcional: añadimos la sede al objeto service para usarlo en la tabla
                $service->address_name = $address->address_line; 
                return $service;
            });
        });

        return view('doctor.services.index', compact('services'));

    }

    public function create()
    {
        $doctor = auth()->user()->doctor;
        $addresses = $doctor->addresses()->where('status', true)->get();

        // Contamos sedes que NO sean la virtual automática
        // Usamos el nombre de tu campo 'address' que definimos como 'Plataforma Online'
        $hasAddresses = $doctor->addresses()
            ->where('address', '!=', 'Plataforma Online')
            ->where('status', true) // Solo sedes activas
            ->exists();

        return view('doctor.services.create', compact('addresses', 'hasAddresses'));
    }


    public function store(Request $request)
    {
        $doctor = auth()->user()->doctor;

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'price'         => 'required|numeric|min:0',
            'type'          => 'required|in:physical,virtual',
            'duration'      => 'required|integer',
            // Validamos que los IDs de direcciones pertenezcan al doctor autenticado
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id',
                function ($attribute, $value, $fail) use ($doctor) {
                    if (!$doctor->addresses()->where('id', $value)->exists()) {
                        $fail('Una de las direcciones seleccionadas no es válida.');
                    }
                },
            ],
        ]);

        // 1. Crear el servicio
        $service = Service::create([
            'name'     => $validated['name'],
            'price'    => $validated['price'],
            'duration' => $validated['duration'],
            'type'     => $validated['type'],
        ]);

        // 2. Asociar direcciones
        if ($validated['type'] === 'virtual') {
            // Si es virtual y no hay sedes, creamos la virtual
            if ($doctor->addresses()->count() === 0) {
                $doctor->createVirtualAddress();
            }
            // Sincronizamos con TODAS las sedes del doctor
            $service->addresses()->sync($doctor->addresses()->pluck('id'));
        } else {
            // Sincronizamos solo con las sedes seleccionadas (ya validadas arriba)
            $service->addresses()->sync($validated['address_ids']);
        }

        return redirect()->route('doctor.services.index')
            ->with('success', '¡Servicio creado exitosamente!');
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

        return view('doctor.services.edit', compact('service', 'addresses', 'selectedAddressIds'));
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
        // 1. Verificamos si existe alguna sede del doctor vinculada a este servicio
        $isOwner = $service->addresses()
            ->where('doctor_id', auth()->user()->doctor->id)
            ->exists();

        if (!$isOwner) {
            abort(403, 'Acción no autorizada. Este servicio no le pertenece.');
        }

        // 2. Eliminar las relaciones en la tabla pivote (address_service)
        $service->addresses()->detach();

        // 3. Eliminar el servicio
        $service->delete();

    return redirect()->route('doctor.services.index')
        ->with('success', 'Servicio eliminado correctamente.');
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
