<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    /**
     * Obtiene el modelo del perfil propietario (Doctor o Clinic) según el rol o el contexto activo.
     */
    private function getOwner()
    {
        $user = Auth::user();
        $context = session('doctor_context');
        
        if ($user->role === 'clinic') {
            return $user->clinic()->with(['plan', 'addresses'])->first();
        }
        
        // Si es doctor y está en contexto de clínica aliada, el "owner" de este entorno es la clínica
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            // Buscamos la clínica aliada a la que pertenece
            return \App\Models\Clinic::with(['plan', 'addresses'])->find($context['id']);
        }
        
        // Por defecto: Modo médico particular (Tu lógica original de producción intacta)
        return $user->doctor()->with(['plan', 'addresses'])->first();
    }

    /**
     * Muestra la lista de sedes del propietario o del contexto institucional activo.
     */
    public function index()
    {
        $owner = $this->getOwner();
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no encontrado.');
        }
        
        // Separación por contexto para médicos:
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            // Modo Clínica: Listamos solo las sedes pertenecientes a esta clínica aliada
            // Filtrando los horarios en esa sede que correspondan únicamente a este doctor
            $addresses = $owner->addresses()
                ->withCount(['schedules' => function ($query) use ($user) {
                    $query->where('doctor_id', $user->doctor->id);
                }])
                ->get();
        } else {
            // Modo Particular o Perfil Clínica Pura (Tu lógica original de producción intacta)
            $addresses = $owner->addresses()
                ->withCount('schedules')
                ->get();
        }

        // Cargar departamentos DIVIPOLA para el formulario de alta
        $departments = Department::orderBy('name')->get();            

        // Enviamos la variable genérica $owner para validar canAddMoreAddresses() en la misma vista Blade
        return view('partner.addresses.index', compact('addresses', 'owner', 'departments'));
    }

    public function toggleStatus(Address $address)
    {
        $this->authorizeOwner($address);

        $address->update([
            'status' => !$address->status
        ]);

        $textoStatus = $address->status ? 'activada' : 'desactivada';

        return back()->with('success', "La sede ha sido {$textoStatus} correctamente.");
    }
    public function create()
    {
        $this->denyIfInstitutionalContext();

        $cities = City::all();
        $owner = $this->getOwner();

        // Validar límites del plan asignado (Aplica para ambos perfiles)
        if (!$owner->canAddMoreAddresses()) {
            $limite = $owner->plan->max_addresses ?? 2;
            return redirect()->route('partner.addresses.index')
                ->with('error', "Tu plan {$owner->plan->name} solo permite {$limite} sedes.");
        }

        // Cargar departamentos DIVIPOLA para el formulario de alta
        $departments = Department::orderBy('name')->get();   
        
        return view('partner.addresses.create', compact('departments', 'cities'));
    }

    /**
     * Almacena una nueva sede vinculada al partner (Doctor o Clínica).
     */
    public function store(Request $request)
    {
        $this->denyIfInstitutionalContext();

        $owner = $this->getOwner();
        $user = Auth::user();

        if (!$owner->canAddMoreAddresses()) {
            $limite = $owner->plan->max_addresses ?? 2;
            return redirect()->route('partner.addresses.index')
                ->with('error', "Tu plan {$owner->plan->name} solo permite {$limite} sedes.");
        }
        
        // Validación adaptada de forma dinámica para evitar duplicados del mismo dueño
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('addresses')->where(function ($query) use ($user, $owner) {
                    return $user->role === 'clinic' 
                        ? $query->where('clinic_id', $owner->id)->whereNull('deleted_at')
                        : $query->where('doctor_id', $owner->id)->whereNull('deleted_at');
                }),
            ],
            'address' => [
                'required',
                'string',
                'max:255',
                Rule::unique('addresses')->where(function ($query) use ($user, $owner) {
                    return $user->role === 'clinic' 
                        ? $query->where('clinic_id', $owner->id)->whereNull('deleted_at')
                        : $query->where('doctor_id', $owner->id)->whereNull('deleted_at');
                }),
            ],
            'phone' => 'nullable|string|max:20',
            'city_id' => 'required|exists:cities,id',
        ], [
            'name.unique' => 'Ya tienes una sede registrada con este nombre.',
            'address.unique' => 'Ya tienes una sede registrada en esta misma dirección.',
        ]);

        // Inyección condicional de llaves foráneas según el rol
        if ($user->role === 'clinic') {
            $validated['clinic_id'] = $owner->id;
            $validated['doctor_id'] = null;
        } else {
            $validated['doctor_id'] = $owner->id;
            $validated['clinic_id'] = null;
        }

        $validated['type'] = 'physical';

        Address::create($validated);

        return redirect()->route('partner.addresses.index')
            ->with('success', 'Nueva sede registrada correctamente.');
    }
    public function edit(Address $address)
    {
        $this->denyIfInstitutionalContext();
        $this->authorizeOwner($address);
        
        $cities = City::all();
        $departments = Department::orderBy('name')->get();   

        return view('partner.addresses.edit', compact('address', 'departments', 'cities'));
    }

    /**
     * Actualiza los datos de una sede específica.
     */
    public function update(Request $request, Address $address)
    {        
        $this->denyIfInstitutionalContext();
        $this->authorizeOwner($address);
        
        $user = Auth::user();
        $owner = $this->getOwner();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('addresses')->ignore($address->id)->where(function ($query) use ($user, $owner) {
                    return $user->role === 'clinic' 
                        ? $query->where('clinic_id', $owner->id)->whereNull('deleted_at')
                        : $query->where('doctor_id', $owner->id)->whereNull('deleted_at');
                }),
            ],
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city_id'  => 'required|exists:cities,id',
        ]);

        $address->update($validated);

        return redirect()->route('partner.addresses.index')
            ->with('success', 'Sede actualizada correctamente.');
    }

    /**
     * Elimina una sede y sus horarios asociados.
     */
    public function destroy(Address $address)
    {
        $this->denyIfInstitutionalContext();
        $this->authorizeOwner($address);

        $hasAppointments = $address->appointments()
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();            

        if ($hasAppointments) {
            return back()->with('error', 'No puedes eliminar esta sede porque tiene citas agendadas. Debes cancelarlas o reprogramarlas primero.');
        }
              
        $address->delete();

        return back()->with('success', 'Sede eliminada correctamente.');
    }

    /**
     * Filtro estricto de seguridad multi-inquilino.
     */    
    private function authorizeOwner(Address $address)
    {        
        $user = Auth::user();
        $context = session('doctor_context');

        // Si es un médico operando en el contexto de una clínica aliada
        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            if ($address->clinic_id !== (int)$context['id']) {
                abort(403, 'No tienes permiso sobre esta sede de la clínica corporativa.');
            }
            return; // Autorizado para verla/listar horarios
        }

        // Control de acceso estándar para Clínicas Puras (Producción)
        if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
            abort(403, 'No tienes permiso sobre esta sede institucional.');
        }

        // Control de acceso estándar para Doctores Independientes (Producción)
        if ($user->role === 'doctor' && $address->doctor_id !== $user->doctor->id) {
            abort(403, 'No tienes permiso sobre esta sede privada.');
        }

        if ($address->type === 'virtual') {
            $hasVirtualServices = $address->services()->where('type', 'virtual')->exists();
            if ($hasVirtualServices) {
                abort(403, 'No puedes eliminar la sede virtual mientras tengas servicios online activos.');
            }
        }
    }

    /**
     * Impide que un médico realice acciones de escritura (CRUD) sobre sedes institucionales.
     */
    private function denyIfInstitutionalContext()
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            abort(403, 'Acción denegada. Las sedes institucionales solo pueden ser modificadas por los administradores de la clínica.');
        }
    }

    /**
     * Lógica para cambiar el plan (SaaS Billing placeholder).
     */
    public function upgradePlan(Request $request)
    {
        $owner = $this->getOwner();
        return back()->with('success', 'Tu plan ha sido actualizado.');
    }
}
