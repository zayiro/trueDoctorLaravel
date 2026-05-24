<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Obtiene el modelo propietario (Doctor o Clinic) según el rol.
     */
    private function getOwner()
    {
        $user = Auth::user();
        
        if ($user->role === 'clinic') {
            return $user->clinic()->first();
        }
        
        return $user->doctor()->first();
    }

    /**
     * Muestra la lista de servicios vinculados al propietario auténticado.
     */
    public function index()
    {
        $owner = $this->getOwner();
        $user = Auth::user();

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil no encontrado.');
        }

        // Obtener servicios filtrando dinámicamente por la columna del propietario
        $ownerColumn = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

        $services = Service::whereHas('addresses', function ($query) use ($ownerColumn, $owner) {
            $query->where($ownerColumn, $owner->id);
        })->with(['addresses' => function ($query) use ($ownerColumn, $owner) {
            $query->where($ownerColumn, $owner->id)->with('city');
        }])->get();

        $uniqueServicesCount = $services->count();

        // Enviamos la variable genérica $owner para mantener simetría con Blade
        return view('partner.services.index', compact('services', 'owner', 'uniqueServicesCount'));
    }

    public function create()
    {
        $owner = $this->getOwner();

        // Cargamos únicamente las sedes físicas activas de este dueño específico
        $addresses = $owner->addresses()
            ->with('city') 
            ->where('status', true)
            ->where('type', 'physical')
            ->get();

        $hasAddresses = $addresses->isNotEmpty();

        return view('partner.services.create', compact('addresses', 'hasAddresses'));
    }

    public function store(Request $request)
    {
        $owner = $this->getOwner();

        if (!$owner->canAddMoreServices()) {
            return redirect()->back()->with('error', 'Has alcanzado el límite global de servicios permitidos en tu plan.');
        }

        $rules = [
            'name'          => 'required|string|max:100',
            'type'          => 'required|in:physical,virtual',
            'duration'      => 'required|integer|min:1',
            'price_virtual' => 'required_if:type,virtual|nullable|numeric|min:0',
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id,deleted_at,NULL',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!$owner->addresses()->where('id', $value)->exists()) {
                        $fail('La sede seleccionada no es válida o no le pertenece.');
                    }
                },
            ],
            'prices'        => 'required_if:type,physical|array',
        ];

        if ($request->has('address_ids') && $request->input('type') === 'physical') {
            foreach ($request->input('address_ids') as $id) {
                $rules["prices.$id"] = 'required|numeric|min:0';
            }
        }

        $validated = $request->validate($rules, [
            'prices.*.required' => 'Debes asignar un precio a todas las sedes que selecciones.',
            'prices.*.numeric'  => 'El precio debe ser un número válido.',
            'prices.*.min'      => 'El precio no puede ser menor a 0.',
        ]);

        // Catálogo global unificado del SaaS
        $service = Service::firstOrCreate([
            'name' => trim($validated['name']),
            'type' => trim($validated['type']),
        ]);

        $syncData = [];

        if ($validated['type'] === 'virtual') {
            $virtualAddress = $owner->addresses()->where('type', 'virtual')->first() 
                            ?? $owner->createVirtualAddress();
            
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

        // Sincronizar sin destruir la configuración de otros doctores o clínicas
        $service->addresses()->syncWithoutDetaching($syncData);

        // Limpieza atómica de sedes físicas desmarcadas para ESTE dueño
        if ($validated['type'] === 'physical') {
            $ownerPhysicalAddressIds = $owner->addresses()->where('type', 'physical')->pluck('id')->toArray();
            $addressesToDetach = array_diff($ownerPhysicalAddressIds, $validated['address_ids']);
            
            if (!empty($addressesToDetach)) {
                $service->addresses()->detach($addressesToDetach);
            }
        }

        return redirect()->route('partner.services.index')->with('success', '¡Servicio configurado correctamente!');
    }

    public function update(Request $request, Service $service)
    {
        $owner = $this->getOwner();
        $user = Auth::user();
        
        $ownerColumn = $user->role === 'clinic' ? 'addresses.clinic_id' : 'addresses.doctor_id';

        // Verificamos propiedad real en la tabla pivote antes de editar nada
        $belongsToOwner = $service->addresses()->where($ownerColumn, $owner->id)->exists();
        if (!$belongsToOwner) {
            abort(403, 'No tienes permiso para modificar este servicio.');
        }

        $rules = [
            'duration'      => 'required|integer|min:1',
            'price_virtual' => 'required_if:type,virtual|nullable|numeric|min:0',
            'address_ids'   => 'required_if:type,physical|array',
            'address_ids.*' => [
                'exists:addresses,id,deleted_at,NULL',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!$owner->addresses()->where('id', $value)->exists()) {
                        $fail('La sede seleccionada no es válida.');
                    }
                },
            ],
            'prices'        => 'required_if:type,physical|array',
        ];

        if ($request->has('address_ids') && $service->type === 'physical') {
            foreach ($request->input('address_ids') as $id) {
                $rules["prices.$id"] = 'required|numeric|min:0';
            }
        }

        $validated = $request->validate($rules, [
            'prices.*.required' => 'Debes asignar un precio a todas las sedes que selecciones.',
            'prices.*.numeric'  => 'El precio debe ser un número válido.',
            'prices.*.min'      => 'El precio no puede ser menor a 0.',
        ]);

        $syncData = [];

        if ($service->type === 'virtual') {
            $virtualAddress = $owner->addresses()->where('type', 'virtual')->first();
            if ($virtualAddress) {
                $syncData[$virtualAddress->id] = [
                    'price'    => round($validated['price_virtual'], 2),
                    'duration' => $validated['duration'],
                ];
            }
        } else {
            foreach ($validated['address_ids'] as $addressId) {
                $syncData[$addressId] = [
                    'price'    => round($validated['prices'][$addressId], 2),
                    'duration' => $validated['duration'],
                ];
            }
        }

        $service->addresses()->syncWithoutDetaching($syncData);

        // 🔥 CORREGIDO Y COMPLETADO: Limpieza atómica tras actualizar sedes físicas
        if ($service->type === 'physical') {
            $ownerPhysicalAddressIds = $owner->addresses()->where('type', 'physical')->pluck('id')->toArray();
            $addressesToDetach = array_diff($ownerPhysicalAddressIds, $validated['address_ids'] ?? []);
            
            if (!empty($addressesToDetach)) {
                $service->addresses()->detach($addressesToDetach);
            }
        }

        return redirect()->route('partner.services.index')->with('success', 'Servicio actualizado correctamente.');
    }
}
