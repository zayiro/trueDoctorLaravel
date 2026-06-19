<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use App\Models\Department;
use App\Traits\ValidatesMultiTenantOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicAddressController extends Controller
{
    use ValidatesMultiTenantOwnership;
    /**
     * Helper metodológico para aislar el contexto comercial de la clínica.
     */
    private function getClinicContext()
    {
        $user = Auth::user();
        if ($user->role !== 'clinic') {
            abort(403, 'Módulo reservado para instituciones médicas.');
        }
        return $user->clinic ?? abort(404, 'Perfil de clínica no encontrado.');
    }

    /**
     * Listar las sedes institucionales y cargar el mapa geográfico colombiano.
     */
    public function index()
    {
        $clinic = $this->getClinicContext();

        // Obtener sedes de la clínica respetando el SoftDeletes de tu migración
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id') // Sedes puras de la institución
            ->with(['city.department'])
            ->get();

        // Cargar departamentos DIVIPOLA para el formulario de alta
        $departments = Department::orderBy('name')->get();

        return view('partner.clinic.addresses.index', compact('addresses', 'clinic', 'departments'));
    }
    
    /**
     * Crear una nueva sede para la clínica.
     * REGLA DE NEGOCIO: Toda sede creada manualmente siempre será de tipo física.
     */
    public function store(Request $request)
    {
        $clinic = $this->getClinicContext();
        $plan = $clinic->settings->plan;

        // 1. Validación limpia: Removemos la columna 'type' del request
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'city_id' => 'required|string|exists:cities,id',
        ]);

        // 2. Control Multi-tenant de límites de infraestructura
        $currentAddressesCount = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->count();

        if ($currentAddressesCount >= $plan->max_addresses) {
            return back()->with('error', "Has alcanzado el límite máximo de sedes permitidas en tu plan actual.");
        }

        // 3. Crear el registro forzando 'type' => 'physical' de manera interna
        Address::create([
            'clinic_id' => $clinic->id,
            'doctor_id' => null, 
            'name'      => trim($validated['name']),
            'address'   => trim($validated['address']),
            'type'      => 'physical', // <-- Forzado internamente por regla de negocio
            'phone'     => trim($validated['phone']),
            'city_id'   => $validated['city_id'],
            'status'    => true,
        ]);

        return redirect()->route('partner.clinic.addresses.index')
            ->with('success', 'La nueva sede física ha sido registrada correctamente en el sistema.');
    }

    /**
     * Alternar el estado operativo de la sede (Activa / Inactiva).
     */
    public function toggleStatus(Address $address)
    {
        $clinic = $this->getClinicContext();

        // Seguridad Multi-tenant: Confirmar propiedad
        $this->validateAddressOwnership($address);

        $address->update([
            'status' => !$address->status
        ]);

        return back()->with('success', 'El estado de la sede ha sido modificado.');
    }

    /**
     * Eliminar de forma suave (SoftDeletes) la sede de la clínica.
     * REGLA DE NEGOCIO SAAS: Una sede virtual nunca se puede eliminar, solo desactivar.
     */
    public function destroy(Address $address)
    {
        $clinic = $this->getClinicContext();

        // 1. Seguridad Multi-tenant: Confirmar propiedad de la sede
        if ($address->clinic_id !== $clinic->id) {
            abort(403, 'Acceso denegado.');
        }

        // 2. 🛑 REGLA DE NEGOCIO CRÍTICA: Bloquear el borrado si es una sede virtual
        if ($address->type === 'virtual') {
            return back()->with('error', 'Por políticas de consistencia operativa y continuidad en telemedicina de OpenDoctor, las sedes virtuales nunca se pueden eliminar. Si deseas suspender su disponibilidad en las agendas públicas de forma temporal o definitiva, por favor utiliza la opción de "Desactivar".');
        }

        // 3. CONTROL DE INTEGRIDAD TRANSACCIONAL: Validar si hay citas pendientes en esta sede física
        $hasAppointments = \App\Models\Appointment::where('address_id', $address->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasAppointments) {
            return back()->with('error', 'No puedes eliminar esta sede física porque cuenta con citas médicas vigentes agendadas con pacientes en el sistema. Debes cancelar o reagendar las consultas primero.');
        }

        // 4. Ejecutar desvinculación y borrado suave (SoftDeletes) en cascada para limpiar horarios base
        DB::transaction(function () use ($clinic, $address) {
            
            // Eliminar registros de la tabla schedules asociados a esta sede para liberar al buscador público
            DB::table('schedules')->where('address_id', $address->id)->delete();
            
            // Laravel rellena automáticamente la columna 'deleted_at' de la tabla addresses
            $address->delete();
        });

        return redirect()->route('partner.clinic.addresses.index')
            ->with('success', 'La sede física ha sido dada de baja de la nómina institucional de forma correcta y su grilla horaria fue purgada.');
    }    
}
