<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Resuelve dinámicamente si el usuario logueado es Doctor o Clínica (Tenant) según su rol o contexto activo.
     */
    private function getOwner(): Doctor|Clinic
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $context = session('doctor_context');

        if ($user->hasRole('clinic')) {
            return $user->clinic;
        }

        if ($user->hasRole('doctor')) {
            // Si está operando dentro del contexto de una clínica aliada, el Tenant comercial es la Clínica
            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinic = Clinic::find($context['id']);
                if (!$clinic) abort(403, 'Clínica aliada no encontrada.');
                return $clinic;
            }

            // Por defecto: Modo consultorio particular (Lógica original de producción)
            return $user->doctor;
        }

        abort(403, 'Perfil comercial no configurado.');
    }

    /**
     * Muestra la lista de servicios vinculados al propietario autenticado o al contexto institucional activo.
     */
    public function index()
    {
        $owner = $this->getOwner();
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no encontrado.');
        }

        // ESCENARIO A: Médico operando dentro del contexto institucional de una clínica aliada
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            $clinicId = (int)$context['id'];
            $clinicUserId = $owner->user_id;
            
            // Obtenemos estrictamente las especialidades compartidas entre la clínica activa y el médico staff
            $sharedSpecialtyIds = DB::table('doctor_specialty')
                ->where('doctor_id', $user->doctor->id)
                ->whereIn('specialty_id', function($q) use ($clinicId) {
                    $q->select('specialty_id')->from('clinic_specialty')->where('clinic_id', $clinicId);
                })
                ->pluck('specialty_id')
                ->toArray();

            // Sincronizamos la consulta cruzando por tenant y especialidad compartida
            $services = Service::whereHas('specialties', function ($query) use ($clinicUserId, $sharedSpecialtyIds) {
                $query->where('service_specialty.user_id', $clinicUserId)
                      ->whereIn('service_specialty.specialty_id', $sharedSpecialtyIds);
            })->with([
                'specialties',
                'addresses' => function ($query) use ($clinicId) {
                    $query->where('clinic_id', $clinicId)->with('city');
                }
            ])->get();
        } 
        // ESCENARIO B: Consultorio Particular o Perfil Clínica Pura (Lógica de producción intacta)
        else {
            $ownerField = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

            $services = Service::whereHas('specialties', function ($query) use ($user) {
                $query->where('service_specialty.user_id', $user->id);
            })->with([
                'specialties',
                'addresses' => function ($query) use ($ownerField, $owner) {
                    $query->where($ownerField, $owner->id)->with('city');
                }
            ])->get();
        }

        $uniqueServicesCount = $services->count();
        
        return view('partner.services.index', compact('services', 'owner', 'uniqueServicesCount'));
    }
    /**
     * Muestra el formulario de creación cargando sedes y especialidades correspondientes.
     */
    public function create()
    {
        // Escudo de protección: No se permite crear servicios en entornos institucionales ajenos
        $this->denyIfInstitutionalContext();

        $owner = $this->getOwner();

        $addresses = $owner->addresses()
            ->with('city') 
            ->where('status', true)
            ->where('type', 'physical')
            ->get();

        $specialties = $owner->specialties()->where('status', true)->get();

        $hasAddresses = $addresses->isNotEmpty();
        $hasSpecialties = $specialties->isNotEmpty();

        return view('partner.services.create', compact('addresses', 'hasAddresses', 'specialties', 'hasSpecialties'));
    }

    /**
     * Almacena y sincroniza un nuevo servicio dentro del catálogo maestro y las tablas pivot del tenant.
     */
    public function store(Request $request)
    {
        $this->denyIfInstitutionalContext();

        $owner = $this->getOwner();
        $user = Auth::user(); 

        if (!$owner->canAddMoreServices()) {
            return redirect()->back()->with('error', 'Has alcanzado el límite global de servicios permitidos en tu plan.');
        }

        $rules = [
            'name'          => 'required|string|max:100',
            'type'          => 'required|in:physical,virtual',
            'duration'      => 'required|integer|min:1',
            'specialties'   => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
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
            'specialties.required' => 'Debes asociar este servicio a una especialidad médica.',
        ]);
        DB::transaction(function () use ($validated, $owner, $user) {
            $service = Service::firstOrCreate([
                'name' => trim($validated['name']),
                'type' => trim($validated['type']),
            ]);

            $specialtySyncData = [];
            foreach ($validated['specialties'] as $specialtyId) {
                $specialtySyncData[$specialtyId] = ['user_id' => $user->id];
            }
            $service->specialties()->syncWithoutDetaching($specialtySyncData);

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
            $service->addresses()->syncWithoutDetaching($syncData);

            if ($validated['type'] === 'physical') {
                $ownerPhysicalAddressIds = $owner->addresses()->where('type', 'physical')->pluck('id')->toArray();
                $addressesToDetach = array_diff($ownerPhysicalAddressIds, $validated['address_ids']);
                
                if (!empty($addressesToDetach)) {
                    $service->addresses()->detach($addressesToDetach);
                }
            }

            $allUserSpecialtiesInService = DB::table('service_specialty')
                ->where('service_id', $service->id)
                ->where('user_id', $user->id)
                ->pluck('specialty_id')
                ->toArray();

            $specialtiesToDetach = array_diff($allUserSpecialtiesInService, $validated['specialties']);
            
            if (!empty($specialtiesToDetach)) {
                DB::table('service_specialty')
                    ->where('service_id', $service->id)
                    ->where('user_id', $user->id)
                    ->whereIn('specialty_id', $specialtiesToDetach)
                    ->delete();
            }
        });

        return redirect()->route('partner.services.index')->with('success', '¡Servicio configurado correctamente!');
    }

    /**
     * Muestra el formulario para editar un servicio médico existente con sus sedes y especialidades.
     */
    public function edit(Service $service)
    {
        $this->denyIfInstitutionalContext();

        $user = auth()->user();
        $owner = $this->getOwner(); 
        
        $belongsToOwner = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$belongsToOwner) {
            abort(403, 'No tienes permisos para modificar este servicio médico.');
        }

        $addresses = $owner->addresses()
            ->with('city')
            ->where('status', true)
            ->where('type', 'physical') 
            ->get();

        $specialties = $owner->specialties()->where('status', true)->get();

        $attachedSpecialtyIds = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $user->id)
            ->pluck('specialty_id')
            ->toArray();

        return view('partner.services.edit', compact('service', 'addresses', 'specialties', 'attachedSpecialtyIds'));
    }
    /**
     * Actualiza el servicio y sincroniza de forma masiva las tarifas, duraciones y especialidades.
     */
    public function update(Request $request, Service $service)
    {
        $this->denyIfInstitutionalContext();

        $owner = $this->getOwner();
        $user = Auth::user();
        
        $belongsToOwner = DB::table('service_specialty')
            ->where('service_id', $service->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$belongsToOwner) {
            abort(403, 'No tienes permiso para modificar este servicio.');
        }

        $rules = [
            'name'          => ['required', 'string', 'max:255'], 
            'duration'      => ['required', 'integer', 'min:1'],
            'specialties'   => ['required', 'array', 'min:1'],
            'specialties.*' => ['exists:specialties,id'],
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
            'specialties.required' => 'Debes asociar este servicio a una especialidad médica.',
        ]);

        DB::transaction(function () use ($validated, $service, $owner, $user) {
            $service->update([
                'name' => trim($validated['name']),
            ]);

            $specialtySyncData = [];
            foreach ($validated['specialties'] as $specialtyId) {
                $specialtySyncData[$specialtyId] = ['user_id' => $user->id];
            }
            $service->specialties()->syncWithoutDetaching($specialtySyncData);

            $allUserSpecialtiesInService = DB::table('service_specialty')
                ->where('service_id', $service->id)
                ->where('user_id', $user->id)
                ->pluck('specialty_id')
                ->toArray();

            $specialtiesToDetach = array_diff($allUserSpecialtiesInService, $validated['specialties']);
            
            if (!empty($specialtiesToDetach)) {
                DB::table('service_specialty')
                    ->where('service_id', $service->id)
                    ->where('user_id', $user->id)
                    ->whereIn('specialty_id', $specialtiesToDetach)
                    ->delete(); 
            }

            $syncData = [];
            if ($service->type === 'virtual') {
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
            $service->addresses()->syncWithoutDetaching($syncData);

            if ($service->type === 'physical') {
                $ownerPhysicalAddressIds = $owner->addresses()->where('type', 'physical')->pluck('id')->toArray();
                $addressesToDetach = array_diff($ownerPhysicalAddressIds, $validated['address_ids'] ?? []);
                
                if (!empty($addressesToDetach)) {
                    $service->addresses()->detach($addressesToDetach);
                }
            }
        });

        return redirect()->route('partner.services.index')->with('success', 'Servicio médico actualizado con éxito.');
    }

    /**
     * Impide modificaciones comerciales sobre servicios institucionales en el contexto de clínicas.
     */
    private function denyIfInstitutionalContext()
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            abort(403, 'Accion denegada. Los catalogos y tarifas institucionales pertenecen exclusivamente a la administracion de la clínica.');
        }
    }
}
