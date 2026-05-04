<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Muestra la lista de sedes del doctor autenticado.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Verificamos si existe la relación 'doctor'
        if (!$user->doctor) {
            return redirect()->back()->with('error', 'Perfil de doctor no encontrado.');
        }
        
        $addresses = $user->doctor->addresses()
            ->withCount('schedules')
            ->get();

        //dd($addresses);

        return view('doctor.addresses.index', compact('addresses'));
    }

    public function toggleStatus(Address $address)
    {
        $this->authorizeOwner($address);

        // Cambiamos el booleano al valor opuesto
        $address->update([
            'status' => !$address->status
        ]);

        $textoStatus = $address->status ? 'activada' : 'desactivada';

        return back()->with('success', "La sede ha sido {$textoStatus} correctamente.");
    }

    public function create()
    {
        $cities = City::all();
        $doctor = Auth::user()->doctor;

        // 1. Validar si ya alcanzó el límite antes de procesar nada
        // Verificar permiso según el plan
        if (!$doctor->canAddMoreAddresses()) {
            $limite = ($doctor->plan === 'avanzado') ? 10 : 2;
            return redirect()->route('doctor.addresses.index')
                ->with('error', "Tu plan {$doctor->plan} solo permite {$limite} consultorios.");
        }
        
        return view('doctor.addresses.create', compact('cities'));
    }

    /**
     * Almacena una nueva sede vinculada al doctor.
     */
    public function store(Request $request)
    {
        $doctor = Auth::user()->doctor;

        // 1. Validar si ya alcanzó el límite antes de procesar nada
        // Verificar permiso según el plan
        if (!$doctor->canAddMoreAddresses()) {
            $limite = ($doctor->plan === 'avanzado') ? 10 : 2;
            return redirect()->route('doctor.addresses.index')
                ->with('error', "Tu plan {$doctor->plan} solo permite {$limite} consultorios.");
        }
        
        // 2. Validar los datos del formulario
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city_id'  => 'required|exists:cities,id',
        ]);

        // Asignar el doctor_id automáticamente
        $validated['doctor_id'] = auth()->user()->doctor->id;

        //asignamos el tipo physical porque ya existe una virtual y debe ser solo una las demas physical
        $validated['type'] = 'physical';

        // 3. Crear el registro
        Address::create($validated);

        return redirect()->route('doctor.addresses.index')
            ->with('success', 'Nuevo consultorio registrado correctamente.');
    }

    /**
     * Actualiza los datos de una sede específica.
     */
    public function update(Request $request, Address $address)
    {        
        // 1. Validar seguridad: ¿El consultorio es de este doctor?
        if ($address->doctor_id !== Auth::user()->doctor->id) {
            abort(403, 'No tienes permiso para editar este consultorio.');
        }

        // 2. Validar datos
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city_id'  => 'required|exists:cities,id',
        ]);

        // 3. Actualizar
        $address->update($validated);

        return redirect()->route('doctor.addresses.index')
            ->with('success', 'Consultorio actualizado correctamente.');
    }

    public function edit(Address $address)
    {
        $cities = City::all();

        // Seguridad: verificar que el consultorio sea del doctor logueado
        if ($address->doctor_id !== Auth::user()->doctor->id) {
            abort(403);
        }

        return view('doctor.addresses.edit', compact('address', 'cities'));
    }

    /**
     * Elimina una sede y sus horarios asociados (vía cascada en BD).
     */
    public function destroy(Address $address)
    {
        $this->authorizeOwner($address);

        // 1. Buscamos si existen citas confirmadas o pendientes para esta sede
        $hasAppointments = $address->appointments()
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        if ($hasAppointments) {
            return back()->withErrors([
                'error' => 'No puedes eliminar esta sede porque tiene citas agendadas. Debes cancelarlas o reprogramarlas primero.'
            ]);
        }

        // 2. Si no hay citas, procedemos a eliminar                
        $address->delete();

        return back()->with('success', 'Sede eliminada correctamente.');
    }

    /**
     * Método privado para seguridad de registros.
    */    
    private function authorizeOwner(Address $address)
    {
        // Verificamos que la sede pertenezca al doctor que está logueado
        if ($address->doctor_id !== auth()->user()->doctor->id) {
            abort(403, 'No tienes permiso para eliminar esta sede.');
        }

        // Evitar borrar la sede virtual si tiene servicios virtuales activos
        if ($address->address === 'Plataforma Online') {
            $hasVirtualServices = $address->services()->where('type', 'virtual')->exists();
            if ($hasVirtualServices) {
                abort(403, 'No puedes eliminar la sede virtual mientras tengas servicios online activos.');
            }
        }
    }

    /**
     * Lógica para cambiar el plan del doctor
     */
    public function upgradePlan(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Aquí podrías agregar lógica de pago (Stripe/PayPal)
        $doctor->update(['plan' => 'avanzado']);

        return back()->with('success', 'Tu plan ha sido actualizado a Avanzado.');
    }
}
