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

        if (!$doctor) {
            return redirect()->back()->with('error', 'Perfil de doctor no encontrado.');
        }

        // 1. Obtener todos los servicios únicos vinculados a las sedes de este doctor
        // Cargamos la relación de direcciones y ciudades para mostrarlas en la tabla
        $services = Service::whereHas('addresses', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })->with(['addresses' => function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id)->with('city');
        }])->get();

        // 2. El conteo real para el límite del plan ahora es exacto y directo
        $uniqueServicesCount = $services->count();

        return view('partner.services.index', compact('services', 'doctor', 'uniqueServicesCount'));
    }

    public function create()
    {
        $doctor = auth()->user()->doctor;

        // Cargamos únicamente las sedes físicas activas con su ciudad
        $addresses = $doctor->addresses()
            ->with('city') 
            ->where('status', true)
            ->where('type', 'physical')
            ->get();

        // Evaluamos directamente si la colección contiene registros
        $hasAddresses = $addresses->isNotEmpty();

        return view('partner.services.create', compact('addresses', 'hasAddresses'));
    }

    public function store(Request $request)
    {
        $doctor = auth()->user()->doctor;

        if (!$doctor->canAddMoreServices()) {
            return redirect()->back()->with('error', 'Has alcanzado el límite global de servicios.');
        }

        // Define las reglas base comunes para crear y actualizar
        $rules = [
            'duration'      => 'required|integer|min:1',
            'price_virtual' => 'required_if:type,virtual|nullable|numeric|min:0',
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id',
                function ($attribute, $value, $fail) use ($doctor) {
                    if (!$doctor->addresses()->where('id', $value)->exists()) {
                        $fail('Dirección no válida.');
                    }
                },
            ],
            'prices'        => 'required_if:type,physical|array',
        ];

        // Si es el método store, incluye la validación del nombre y tipo (catálogo global)
        if ($request->isMethod('post')) {
            $rules['name'] = 'required|string|max:100';
            $rules['type'] = 'required|in:physical,virtual';
        }

        // Inyección dinámica: Exige el precio SOLO para las sedes seleccionadas
        if ($request->has('address_ids')) {
            foreach ($request->input('address_ids') as $id) {
                $rules["prices.$id"] = 'required|numeric|min:0';
            }
        }

        // Ejecuta la validación con los mensajes personalizados
        $validated = $request->validate($rules, [
            'prices.*.required' => 'Debes asignar un precio a todas las sedes que selecciones.',
            'prices.*.numeric'  => 'El precio debe ser un número válido.',
            'prices.*.min'      => 'El precio no puede ser menor a 0.',
        ]);

        // 1. Obtener o crear el concepto del servicio en el catálogo global
        $service = Service::firstOrCreate([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        $syncData = [];

        // 2. Estructurar datos según la modalidad
        if ($validated['type'] === 'virtual') {
            $virtualAddress = $doctor->addresses()->where('type', 'virtual')->first() 
                            ?? $doctor->createVirtualAddress();
            
            $syncData[$virtualAddress->id] = [
                'price'    => round($validated['price_virtual'], 2),
                'duration' => $validated['duration'],
            ];
        } else {
            foreach ($validated['address_ids'] as $addressId) {
                $syncData[$addressId] = [
                    'price'    => round($validated['prices'][$addressId], 2),
                    'duration' => $validated['duration'],
                ];
            }
        }

        // 3. Sincronizar sin borrar a otros doctores
        $service->addresses()->syncWithoutDetaching($syncData);

        // 4. Limpieza de sedes desmarcadas (Solo si es físico)
        if ($validated['type'] === 'physical') {
            $doctorAddressIds = $doctor->addresses()->where('type', 'physical')->pluck('id')->toArray();
            $addressesToDetach = array_diff($doctorAddressIds, $validated['address_ids']);
            if (!empty($addressesToDetach)) {
                $service->addresses()->detach($addressesToDetach);
            }
        }

        return redirect()->route('partner.services.index')->with('success', '¡Servicio configurado correctamente!');
    }

    public function update(Request $request, Service $service)
    {
        $doctor = auth()->user()->doctor;

        $belongsToDoctor = $service->addresses()->where('addresses.doctor_id', $doctor->id)->exists();
        if (!$belongsToDoctor) {
            abort(403, 'No tienes permiso para modificar este servicio.');
        }

        // Define las reglas base comunes para crear y actualizar
        $rules = [
            'duration'      => 'required|integer|min:1',
            'price_virtual' => 'required_if:type,virtual|nullable|numeric|min:0',
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id',
                function ($attribute, $value, $fail) use ($doctor) {
                    if (!$doctor->addresses()->where('id', $value)->exists()) {
                        $fail('Dirección no válida.');
                    }
                },
            ],
            'prices' => 'required_if:type,physical|array',
        ];

        // Si es el método store, incluye la validación del nombre y tipo (catálogo global)
        if ($request->isMethod('post')) {
            $rules['name'] = 'required|string|max:100';
            $rules['type'] = 'required|in:physical,virtual';
        }

        // Inyección dinámica: Exige el precio SOLO para las sedes seleccionadas
        if ($request->has('address_ids')) {
            foreach ($request->input('address_ids') as $id) {
                $rules["prices.$id"] = 'required|numeric|min:0';
            }
        }

        // Ejecuta la validación con los mensajes personalizados
        $validated = $request->validate($rules, [
            'prices.*.required' => 'Debes asignar un precio a todas las sedes que selecciones.',
            'prices.*.numeric'  => 'El precio debe ser un número válido.',
            'prices.*.min'      => 'El precio no puede ser menor a 0.',
        ]);

        $syncData = [];

        if ($service->type === 'virtual') {
            $virtualAddress = $doctor->addresses()->where('type', 'virtual')->first();
            if ($virtualAddress) {
                $syncData[$virtualAddress->id] = [
                    'price'    => $validated['price_virtual'],
                    'duration' => $validated['duration'],
                ];
            }
        } else {
            foreach ($validated['address_ids'] as $addressId) {
                $syncData[$addressId] = [
                    'price'    => $validated['prices'][$addressId], // Precio específico de esta sede
                    'duration' => $validated['duration'],
                ];
            }
        }

        $service->addresses()->syncWithoutDetaching($syncData);

        if ($service->type === 'physical') {
            $doctorAddressIds = $doctor->addresses()->where('type', 'physical')->pluck('id')->toArray();
            $addressesToDetach = array_diff($doctorAddressIds, $validated['address_ids']);
            if (!empty($addressesToDetach)) {
                $service->addresses()->detach($addressesToDetach);
            }
        }

        return redirect()->route('partner.services.index')->with('success', '¡Servicio actualizado con éxito!');
    }

    public function edit(Service $service)
    {
        $doctor = auth()->user()->doctor;

        // Verificar si el servicio está asignado a alguna dirección de este doctor
        $belongsToDoctor = $service->addresses()
            ->where('addresses.doctor_id', $doctor->id)
            ->exists();

        if (!$belongsToDoctor) {
            abort(403, 'No tienes permiso para editar este servicio en tus sedes.');
        }

        $addresses = $doctor->addresses()->where('status', true)->where('type', 'physical')->get();
        $selectedAddressIds = $service->addresses()->where('addresses.doctor_id', $doctor->id)->pluck('addresses.id')->toArray();

        return view('partner.services.edit', compact('service', 'addresses', 'selectedAddressIds'));
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
        $doctor = auth()->user()->doctor;

        // 1. Obtener todas los IDs de las sedes (físicas y virtuales) de este doctor
        $doctorAddressIds = $doctor->addresses()->pluck('id')->toArray();

        // 2. Desvincular el servicio únicamente de las sedes de este doctor
        // Esto borra los registros correspondientes en la tabla pivot 'address_service'
        $service->addresses()->detach($doctorAddressIds);

        // 3. SEGURIDAD/LIMPIEZA: Si el servicio ya no está asociado a ninguna sede en todo el sistema,
        // lo eliminamos del catálogo global de forma segura.
        if (!$service->addresses()->exists()) {
            $service->delete();
        }

        return redirect()->route('partner.services.index')
            ->with('success', '¡Servicio eliminado de tus sedes correctamente!');
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
