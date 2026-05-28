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
     * Muestra la lista de servicios vinculados al propietario autenticado.
     */
    public function index()
    {
        $owner = $this->getOwner();
        $user = Auth::user();

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no encontrado.');
        }

        // 🔒 CORREGIDO: Usamos la columna exacta de la relación física en la tabla pivote 
        // Esto evita posibles ambigüedades de SQL (Ambiguous column name) si hay Joins internos.
        $ownerField = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

        $services = Service::whereHas('addresses', function ($query) use ($ownerField, $owner) {
            $query->where($ownerField, $owner->id);
        })->with(['addresses' => function ($query) use ($ownerField, $owner) {
            // Eager loading seguro que trae la ciudad limpia de la sede
            $query->where($ownerField, $owner->id)->with('city');
        }])->get();

        $uniqueServicesCount = $services->count();
        
        // Enviamos las variables compactadas en perfecta simetría con tu vista Blade
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

        /**
     * Actualiza el servicio y sincroniza de forma masiva las tarifas y duraciones por sede.
     */
    public function update(Request $request, Service $service)
    {
        $owner = $this->getOwner();
        $user = Auth::user();
        
        // 🔒 CONTROL MULTI-TENANT: Usamos la columna exacta de la relación en la tabla intermedia
        $ownerField = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

        $belongsToOwner = $service->addresses()->where($ownerField, $owner->id)->exists();
        if (!$belongsToOwner) {
            abort(403, 'No tienes permiso para modificar este servicio.');
        }

        $rules = [
            'name'          => ['required', 'string', 'max:255'], 
            'duration'      => ['required', 'integer', 'min:1'],
            'price_virtual' => ['required_if:type,virtual', 'nullable', 'numeric', 'min:0'],
            'address_ids'   => ['required_if:type,physical', 'array'],
            'address_ids.*' => [
                'exists:addresses,id,deleted_at,NULL',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!$owner->addresses()->where('id', $value)->exists()) {
                        $fail('La sede seleccionada no es válida.');
                    }
                },
            ],
            'prices'        => ['required_if:type,physical', 'array'],
        ];

        if ($request->has('address_ids') && $service->type === 'physical') {
            foreach ($request->input('address_ids') as $id) {
                $rules["prices.$id"] = ['required', 'numeric', 'min:0'];
            }
        }

        $validated = $request->validate($rules, [
            'prices.*.required' => 'Debes asignar un precio a todas las sedes que selecciones.',
            'prices.*.numeric'  => 'El precio debe ser un número válido.',
            'prices.*.min'      => 'El precio no puede ser menor a 0.',
        ]);
        // 🔒 REPARADO DE RAÍZ: Solo actualizamos el nombre en la tabla maestra 'services'
        // para prevenir errores 1054 de columnas inexistentes en la base de datos
        $service->update([
            'name' => $validated['name'],
        ]);

        $syncData = [];

        if ($service->type === 'virtual') {
            $virtualAddress = $owner->addresses()->where('type', 'virtual')->first();
            if ($virtualAddress) {
                $syncData[$virtualAddress->id] = [
                    'price'    => round($validated['price_virtual'], 2),
                    'duration' => $validated['duration'], // Guardado seguro en el pivot
                ];
            }
        } else {
            foreach ($validated['address_ids'] as $addressId) {
                $syncData[$addressId] = [
                    'price'    => round($validated['prices'][$addressId], 2),
                    'duration' => $validated['duration'], // Guardado seguro en el pivot
                ];
            }
        }

        // Sincronización masiva en la tabla pivote address_service sin borrar registros de otros dueños
        $service->addresses()->syncWithoutDetaching($syncData);

        // Limpieza atómica tras actualizar sedes físicas (Remueve las desmarcadas de este dueño)
        if ($service->type === 'physical') {
            $ownerPhysicalAddressIds = $owner->addresses()->where('type', 'physical')->pluck('id')->toArray();
            $addressesToDetach = array_diff($ownerPhysicalAddressIds, $validated['address_ids'] ?? []);
            
            if (!empty($addressesToDetach)) {
                $service->addresses()->detach($addressesToDetach);
            }
        }

        return redirect()->route('partner.services.index')->with('success', 'Servicio médico actualizado con éxito.');
    }

    /**
     * Muestra el formulario para editar un servicio médico existente con sus sedes.
     */
    public function edit(Service $service)
    {
        $user = auth()->user();
        $owner = $this->getOwner(); 
        
        $ownerField = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

        // 1. Verificamos propiedad real en la tabla pivote de sedes antes de abrir la vista
        $belongsToOwner = $service->addresses()->where($ownerField, $owner->id)->exists();
        
        if (!$belongsToOwner) {
            abort(403, 'No tienes permisos para modificar este servicio médico.');
        }

        // 🔒 SOLUCIÓN AL BUG: Cargamos las sedes físicas del dueño para el formulario
        // Si tu vista requiere también la sede virtual, quita el ->where('type', 'physical')
        $addresses = $owner->addresses()
            ->where('status', true)
            ->where('type', 'physical') 
            ->get();

        // 2. Retorna la vista de edición enviando ambas variables compactadas
        return view('partner.services.edit', compact('service', 'addresses'));
    }
}
