<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use App\Models\Department;
use App\Models\Schedule;
use App\Repositories\AvailabilityRepository;
use App\Traits\ValidatesMultiTenantOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicAddressController extends Controller
{
    use ValidatesMultiTenantOwnership;

    /**
     * Helper metodológico para aislar el contexto comercial de la clínica.
     * 
     *  Usa $user->role (campo del modelo) en lugar de hasRole() de Spatie
     * para consistencia con el resto del sistema multi-tenant.
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

        // Obtener sedes de la clínica respetando el SoftDeletes de la migración
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
    public function store(Request $request, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // 🔒 CORRECCIÓN: Usar $clinic->plan (relación HasOneThrough) en lugar de $clinic->settings->plan
        $plan = $clinic->plan;
        
        if (!$plan) {
            return back()->with('error', 'La clínica no tiene un plan de suscripción asignado. Contacta al administrador.');
        }

        // 1. Validación limpia: Removemos la columna 'type' del request
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'city_id' => 'required|exists:cities,id',
        ]);

        // 2. Control Multi-tenant de límites de infraestructura
        $currentAddressesCount = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->count();

        if ($currentAddressesCount >= ($plan->max_addresses ?? 0)) {
            return back()->with('error', 
                "Has alcanzado el límite máximo de sedes permitidas en tu plan {$plan->name} ({$plan->max_addresses}).");
        }

        try {
            // 3. Crear el registro forzando 'type' => 'physical' de manera interna
            $newAddress = Address::create([
                'clinic_id' => $clinic->id,
                'doctor_id' => null,
                'name'      => trim($validated['name']),
                'address'   => trim($validated['address']),
                'type'      => 'physical', // Forzado internamente por regla de negocio
                'phone'     => trim($validated['phone'] ?? ''),
                'city_id'   => $validated['city_id'],
                'status'    => true,
            ]);

            // 🔥 INVALIDAR CACHÉ al crear nueva sede (por consistencia)
            $cacheRepo->invalidateAvailabilityCache($clinic->id, [], $newAddress->id);

            return redirect()->route('partner.clinic.addresses.index')
                ->with('success', 'La nueva sede física ha sido registrada correctamente en el sistema.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Error al crear la sede: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Alternar el estado operativo de la sede (Activa / Inactiva).
     * 
     * 🔒 CORRECCIÓN: Ahora invalida el caché de disponibilidad para que la sede
     * desactivada deje de aparecer en búsquedas inmediatamente.
     */
    public function toggleStatus(Address $address, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // Seguridad Multi-tenant: Confirmar propiedad
        $this->validateAddressOwnership($address);

        // Validar que la sede pertenezca a la clínica
        if ($address->clinic_id !== $clinic->id || $address->doctor_id !== null) {
            abort(403, 'No tienes permiso sobre esta sede institucional.');
        }

        $address->update(['status' => !$address->status]);

        // 🔥 INVALIDAR CACHÉ: Crucial para que la sede desactivada deje de aparecer en búsquedas
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (!empty($doctorIds)) {
            $cacheRepo->invalidateAvailabilityCache($clinic->id, $doctorIds, $address->id);
        }

        $textoStatus = $address->status ? 'activada' : 'desactivada';
        return back()->with('success', "El estado de la sede ha sido {$textoStatus} correctamente.");
    }

    /**
     * Eliminar de forma suave (SoftDeletes) la sede de la clínica.
     * REGLA DE NEGOCIO SAAS: Una sede virtual nunca se puede eliminar, solo desactivar.
     * 
     * 🔒 CORRECCIONES:
     * - Usa is_active = false en schedules (no delete físico) para preservar auditoría
     * - Invalida caché de disponibilidad
     * - Manejo de errores transaccionales
     */
    public function destroy(Address $address, AvailabilityRepository $cacheRepo)
    {
        $clinic = $this->getClinicContext();

        // 1. Seguridad Multi-tenant: Confirmar propiedad de la sede
        $this->validateAddressOwnership($address);

        // Validar que la sede pertenezca a la clínica
        if ($address->clinic_id !== $clinic->id || $address->doctor_id !== null) {
            abort(403, 'No tienes permiso sobre esta sede institucional.');
        }

        // 2. 🛑 REGLA DE NEGOCIO CRÍTICA: Bloquear el borrado si es una sede virtual
        if ($address->type === 'virtual') {
            return back()->with('error', 
                'Por políticas de consistencia operativa y continuidad en telemedicina de OpenDoctor, '
                . 'las sedes virtuales nunca se pueden eliminar. Si deseas suspender su disponibilidad '
                . 'en las agendas públicas de forma temporal o definitiva, por favor utiliza la opción de "Desactivar".');
        }

        // 3. CONTROL DE INTEGRIDAD TRANSACCIONAL: Validar si hay citas pendientes en esta sede física
        $hasAppointments = \App\Models\Appointment::where('address_id', $address->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasAppointments) {
            return back()->with('error', 
                'No puedes eliminar esta sede física porque cuenta con citas médicas vigentes agendadas '
                . 'con pacientes en el sistema. Debes cancelar o reagendar las consultas primero.');
        }

        // 4. Capturar doctores ANTES de eliminar (para invalidar caché)
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        try {
            // 5. Ejecutar desvinculación y borrado suave (SoftDeletes) en cascada
            DB::transaction(function () use ($address) {
                // 🔒 CORRECCIÓN CRÍTICA: Desactivar horarios en lugar de eliminarlos físicamente
                // Esto preserva la auditoría médica y permite recuperar el histórico si es necesario
                Schedule::where('address_id', $address->id)
                    ->update(['is_active' => false, 'updated_at' => now()]);

                // Laravel rellena automáticamente la columna 'deleted_at' de la tabla addresses
                $address->delete();
            });

            // 6. 🔥 INVALIDAR CACHÉ después de eliminar
            if (!empty($doctorIds)) {
                $cacheRepo->invalidateAvailabilityCache($address->clinic_id, $doctorIds, $address->id);
            }

            return redirect()->route('partner.clinic.addresses.index')
                ->with('success', 
                    'La sede física ha sido dada de baja de la nómina institucional de forma correcta '
                    . 'y su grilla horaria fue desactivada (no eliminada) para preservar la auditoría.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Error al eliminar la sede: ' . $e->getMessage());
        }
    }
}