<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Department;
use App\Models\Plan;
use App\Models\DoctorSetting;
use App\Models\Schedule;
use App\Repositories\AvailabilityRepository;
use App\Traits\ValidatesMultiTenantOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    use ValidatesMultiTenantOwnership;

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

        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            return Clinic::with(['plan', 'addresses'])->find($context['id']);
        }

        return $user->doctor()->with(['plan', 'addresses'])->first();
    }

    /**
     * Reglas de la georreferencia: ambas coordenadas o ninguna, dentro de los rangos válidos.
     */
    private function coordinateRules(): array
    {
        return [
            'latitude'  => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    private function coordinateMessages(): array
    {
        return [
            'latitude.required_with'  => 'Si ingresas la longitud, también debes ingresar la latitud.',
            'longitude.required_with' => 'Si ingresas la latitud, también debes ingresar la longitud.',
            'latitude.numeric'        => 'La latitud debe ser un número (Ej: 4.7109886).',
            'longitude.numeric'       => 'La longitud debe ser un número (Ej: -74.0721012).',
            'latitude.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitude.between'       => 'La longitud debe estar entre -180 y 180.',
        ];
    }

    /**
     * Redondea las coordenadas a 7 decimales (la precisión de la columna DECIMAL(10,7)).
     * Google Maps y los GPS suelen entregar más decimales de los que se almacenan.
     */
    private function normalizeCoordinates(array $validated): array
    {
        foreach (['latitude', 'longitude'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = round((float) $validated[$field], 7);
            }
        }

        return $validated;
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

        if ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') {
            $addresses = $owner->addresses()
                ->withCount(['schedules' => function ($query) use ($user) {
                    $query->where('doctor_id', $user->doctor->id);
                }])
                ->get();
        } else {
            $addresses = $owner->addresses()
                ->withCount('schedules')
                ->get();
        }

        $departments = Department::orderBy('name')->get();
        $canAddAddress = $owner->canAddMoreAddresses();

        // 🔒 CORRECCIÓN CRÍTICA: Inyectar $currentContext para la vista
        // La vista lo usa en múltiples lugares (banner, textos, botones)
        $currentContext = $context ?? [
            'type'  => 'particular',
            'id'    => null,
            'name'  => 'Consultorio Particular',
            'photo' => null,
        ];

        return view('partner.addresses.index', compact(
            'addresses',
            'owner',
            'departments',
            'canAddAddress',
            'currentContext'  // ← AGREGADO
        ));
    }

    /**
     * Cambia el estado (activo/inactivo) de una sede.
     */
    public function toggleStatus(Address $address, AvailabilityRepository $cacheRepo)
    {
        $this->validateAddressOwnership($address);

        $address->update(['status' => !$address->status]);

        // 🔥 INVALIDAR CACHÉ: Crucial para que la sede desactivada deje de aparecer en búsquedas
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (!empty($doctorIds)) {
            $cacheRepo->invalidateAvailabilityCache(
                $address->clinic_id,
                $doctorIds,
                $address->id
            );
        }

        $textoStatus = $address->status ? 'activada' : 'desactivada';
        return back()->with('success', "La sede ha sido {$textoStatus} correctamente.");
    }

    /**
     * Muestra el formulario para crear una nueva sede.
     */
    public function create()
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();

        if (!$owner->canAddMoreAddresses()) {
            $limite = $owner->plan->max_addresses ?? 2;
            return redirect()->route('partner.addresses.index')
                ->with('error', "Tu plan {$owner->plan->name} solo permite {$limite} sedes.");
        }

        $cities = City::all();
        $departments = Department::orderBy('name')->get();
        $canAddAddress = $owner->canAddMoreAddresses();

        return view('partner.addresses.create', compact('departments', 'cities', 'canAddAddress'));
    }

    /**
     * Almacena una nueva sede vinculada al partner (Doctor o Clínica).
     */
    public function store(Request $request, AvailabilityRepository $cacheRepo)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();
        $user = Auth::user();

        if (!$owner->canAddMoreAddresses()) {
            $limite = $owner->plan->max_addresses ?? 2;
            return redirect()->route('partner.addresses.index')
                ->with('error', "Tu plan {$owner->plan->name} solo permite {$limite} sedes.");
        }

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
        ] + $this->coordinateRules(), [
            'name.unique' => 'Ya tienes una sede registrada con este nombre.',
            'address.unique' => 'Ya tienes una sede registrada en esta misma dirección.',
        ] + $this->coordinateMessages());

        $validated = $this->normalizeCoordinates($validated);

        if ($user->role === 'clinic') {
            $validated['clinic_id'] = $owner->id;
            $validated['doctor_id'] = null;
        } else {
            $validated['doctor_id'] = $owner->id;
            $validated['clinic_id'] = null;
        }

        $validated['type'] = 'physical';

        $newAddress = Address::create($validated);

        // 🔥 INVALIDAR CACHÉ al crear nueva sede (por consistencia)
        if ($user->role === 'doctor') {
            $cacheRepo->invalidateAvailabilityCache(
                null,
                [$owner->id],
                $newAddress->id
            );
        }

        return redirect()->route('partner.addresses.index')
            ->with('success', 'Nueva sede registrada correctamente.');
    }

    /**
     * Muestra el formulario de edición de una sede.
     */
    public function edit(Address $address)
    {
        $this->denyIfInstitutionalContext();
        $this->validateAddressOwnership($address);

        $cities = City::all();
        $departments = Department::orderBy('name')->get();

        return view('partner.addresses.edit', compact('address', 'departments', 'cities'));
    }

    /**
     * Actualiza los datos de una sede específica.
     */
    public function update(Request $request, Address $address, AvailabilityRepository $cacheRepo)
    {
        $this->denyIfInstitutionalContext();
        $this->validateAddressOwnership($address);

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
            'address' => [
                'required',
                'string',
                'max:255',
                Rule::unique('addresses')->ignore($address->id)->where(function ($query) use ($user, $owner) {
                    return $user->role === 'clinic'
                        ? $query->where('clinic_id', $owner->id)->whereNull('deleted_at')
                        : $query->where('doctor_id', $owner->id)->whereNull('deleted_at');
                }),
            ],
            'phone' => 'nullable|string|max:20',
            'city_id' => 'required|exists:cities,id',
        ] + $this->coordinateRules(), $this->coordinateMessages());

        $address->update($this->normalizeCoordinates($validated));

        // 🔥 INVALIDAR CACHÉ al actualizar sede (por consistencia)
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (!empty($doctorIds)) {
            $cacheRepo->invalidateAvailabilityCache(
                $address->clinic_id,
                $doctorIds,
                $address->id
            );
        }

        return redirect()->route('partner.addresses.index')
            ->with('success', 'Sede actualizada correctamente.');
    }

    /**
     * Elimina una sede y sus horarios asociados (Soft Delete).
     */
    public function destroy(Address $address, AvailabilityRepository $cacheRepo)
    {
        $this->denyIfInstitutionalContext();
        $this->validateAddressOwnership($address);

        // 🔒 Capturar doctores ANTES de eliminar (para invalidar caché)
        $doctorIds = Schedule::where('address_id', $address->id)
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        // 🔒 CORRECCIÓN: Solo validar citas FUTURAS (no pasadas)
        $hasAppointments = $address->appointments()
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('date', '>=', now()->toDateString())  // ← Solo citas futuras
            ->exists();

        if ($hasAppointments) {
            return back()->with('error', 'No puedes eliminar esta sede porque tiene citas agendadas. Debes cancelarlas o reprogramarlas primero.');
        }

        $address->delete(); // Soft delete

        // 🔥 INVALIDAR CACHÉ después de eliminar
        if (!empty($doctorIds)) {
            $cacheRepo->invalidateAvailabilityCache(
                $address->clinic_id,
                $doctorIds,
                $address->id
            );
        }

        return back()->with('success', 'Sede eliminada correctamente.');
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