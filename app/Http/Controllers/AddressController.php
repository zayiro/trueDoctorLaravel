<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    /**
     * Obtiene el modelo del perfil propietario (Doctor o Clinic) según el rol.
     */
    private function getOwner()
    {
        $user = Auth::user();
        
        if ($user->role === 'clinic') {
            return $user->clinic()->with(['plan', 'addresses'])->first();
        }
        
        return $user->doctor()->with(['plan', 'addresses'])->first();
    }

    /**
     * Muestra la lista de sedes del propietario autenticado.
     */
    public function index()
    {
        $owner = $this->getOwner();

        if (!$owner) {
            return redirect()->back()->with('error', 'Perfil comercial no encontrado.');
        }
        
        // Obtenemos las direcciones vinculadas al propietario con el conteo de horarios
        $addresses = $owner->addresses()
            ->withCount('schedules')
            ->get();

        // Enviamos la variable genérica $owner para validar canAddMoreAddresses() en la misma vista Blade
        return view('partner.addresses.index', compact('addresses', 'owner'));
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
        $cities = City::all();
        $owner = $this->getOwner();

        // Validar límites del plan asignado (Aplica para ambos perfiles)
        if (!$owner->canAddMoreAddresses()) {
            $limite = $owner->plan->max_addresses ?? 2;
            return redirect()->route('partner.addresses.index')
                ->with('error', "Tu plan {$owner->plan->name} solo permite {$limite} sedes.");
        }
        
        return view('partner.addresses.create', compact('cities'));
    }

    /**
     * Almacena una nueva sede vinculada al partner (Doctor o Clínica).
     */
    public function store(Request $request)
    {
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
        $this->authorizeOwner($address);
        $cities = City::all();

        return view('partner.addresses.edit', compact('address', 'cities'));
    }

    /**
     * Actualiza los datos de una sede específica.
     */
    public function update(Request $request, Address $address)
    {        
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

        // Control de acceso para Clínicas
        if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
            abort(403, 'No tienes permiso sobre esta sede institucional.');
        }

        // Control de acceso para Doctores Independientes
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
     * Lógica para cambiar el plan (SaaS Billing placeholder).
     */
    public function upgradePlan(Request $request)
    {
        $owner = $this->getOwner();
        // Lógica de pasarela aquí...
        return back()->with('success', 'Tu plan ha sido actualizado.');
    }
}
