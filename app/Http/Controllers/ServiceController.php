<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\AppointmentDuration;
use App\Services\OwnerServiceManager;
use App\Traits\ValidatesMultiTenantOwnership;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicios del profesional o clínica.
 *
 * Cada servicio es INDEPENDIENTE: pertenece a una sola sede y a un solo propietario. Crear un servicio en varias
 * sedes genera una copia por sede; editar o eliminar afecta únicamente a esa sede (ver OwnerServiceManager).
 */
class ServiceController extends Controller
{
    use ValidatesMultiTenantOwnership;

    public function __construct(private readonly OwnerServiceManager $services)
    {
    }

    /**
     * Resuelve el propietario comercial (Doctor o Clínica) según el rol y el contexto activo.
     */
    private function getOwner(): Doctor|Clinic
    {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $context = session('doctor_context');

        return match ($user->role) {
            'clinic' => $user->clinic ?? abort(404, 'Perfil de clínica no encontrado.'),
            'doctor' => $this->resolveDoctorOwner($user, $context),
            default  => abort(403, 'Perfil comercial no configurado.'),
        };
    }

    private function resolveDoctorOwner($user, $context): Doctor|Clinic
    {
        if (($context['type'] ?? 'particular') === 'clinic') {
            return Clinic::find($context['id']) ?? abort(403, 'Clínica aliada no encontrada.');
        }

        return $user->doctor ?? abort(404, 'Perfil médico no encontrado.');
    }

    /**
     * Lista los servicios del propietario autenticado (cada fila es de una sede).
     */
    public function index()
    {
        $owner   = $this->getOwner();
        $user    = Auth::user();
        $context = session('doctor_context');

        $services = $this->buildServicesQuery($owner, $user, $context)->get();

        // Un servicio replicado en varias sedes cuenta una sola vez para el límite del plan
        $uniqueServicesCount = $services
            ->unique(fn (Service $service) => mb_strtolower($service->name) . '|' . $service->type)
            ->count();

        $currentContext = $context ?? [
            'type'  => 'particular',
            'id'    => null,
            'name'  => 'Consultorio Particular',
            'photo' => null,
        ];

        return view('partner.services.index', compact('services', 'owner', 'uniqueServicesCount', 'currentContext'));
    }

    private function buildServicesQuery($owner, $user, $context)
    {
        $isClinicContext = $user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic';

        return $isClinicContext
            ? $this->buildClinicContextQuery($owner, $user, (int) $context['id'])
            : $this->buildStandardQuery($owner);
    }

    /**
     * Médico en contexto de clínica (solo lectura): servicios de la clínica en especialidades compartidas.
     */
    private function buildClinicContextQuery(Clinic $clinic, $user, int $clinicId)
    {
        $sharedSpecialtyIds = DB::table('doctor_specialty')
            ->where('doctor_id', $user->doctor->id)
            ->whereIn('specialty_id', function ($q) use ($clinicId) {
                $q->select('specialty_id')->from('clinic_specialty')->where('clinic_id', $clinicId);
            })
            ->pluck('specialty_id')
            ->toArray();

        return Service::ownedBy($clinic)
            ->whereHas('specialties', function ($query) use ($clinic, $sharedSpecialtyIds) {
                $query->where('service_specialty.user_id', $clinic->user_id)
                      ->whereIn('service_specialty.specialty_id', $sharedSpecialtyIds);
            })
            ->with(['specialties', 'addresses' => fn ($query) => $query->with('city')])
            ->orderBy('name');
    }

    /**
     * Consultorio particular o clínica pura: SOLO los servicios de sus propias sedes.
     */
    private function buildStandardQuery(Doctor|Clinic $owner)
    {
        return Service::ownedBy($owner)
            ->with(['specialties', 'addresses' => fn ($query) => $query->with('city')])
            ->orderBy('name');
    }

    public function create()
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();

        $addresses      = $this->getPhysicalAddresses($owner);
        $specialties    = $owner->specialties()->where('status', true)->get();
        $hasAddresses   = $addresses->isNotEmpty();
        $hasSpecialties = $specialties->isNotEmpty();
        $durations      = AppointmentDuration::where('active', true)->orderBy('minutes', 'asc')->get();

        return view('partner.services.create', compact('addresses', 'specialties', 'hasAddresses', 'hasSpecialties', 'durations'));
    }

    /**
     * Crea el servicio en las sedes elegidas: una fila independiente por sede.
     */
    public function store(ServiceRequest $request)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();
        $user  = Auth::user();

        if (!$owner->canAddMoreServices()) {
            return redirect()->back()->with('error', 'Has alcanzado el límite global de servicios permitidos en tu plan.');
        }

        $data = $request->validated();

        $this->validateSpecialtiesOwnership($data['specialties'], $user);

        if ($data['type'] === 'physical') {
            $this->validateAddressesOwnership($data['address_ids'], $user);
        }

        $created = $this->services->create($owner, $user->id, $data);

        $message = $created->count() > 1
            ? "¡Servicio creado en {$created->count()} sedes! Cada una es independiente: puedes editarla o eliminarla sin afectar a las demás."
            : '¡Servicio configurado correctamente!';

        return redirect()->route('partner.services.index')->with('success', $message);
    }

    public function edit(Service $service)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();
        $user  = Auth::user();

        $address     = $this->services->addressOf($service, $owner);
        $specialties = $owner->specialties()->where('status', true)->get();

        $attachedSpecialtyIds = $service->specialties()->pluck('specialties.id')->toArray();
        $pivot                = $service->addresses()->withTrashed()->first()->pivot;

        return view('partner.services.edit', compact('service', 'address', 'pivot', 'specialties', 'attachedSpecialtyIds'));
    }

    /**
     * Actualiza el servicio SOLO en su sede.
     */
    public function update(ServiceRequest $request, Service $service)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();
        $user  = Auth::user();

        $data = $request->validated();

        $this->validateSpecialtiesOwnership($data['specialties'], $user);

        $this->services->update($service, $owner, $user->id, $data);

        return redirect()->route('partner.services.index')->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Elimina el servicio SOLO de su sede.
     */
    public function destroy(Service $service)
    {
        $this->denyIfInstitutionalContext();
        $owner = $this->getOwner();

        try {
            $this->services->delete($service, $owner);
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('partner.services.index')->with('success', 'Servicio eliminado de la sede.');
    }

    private function getPhysicalAddresses($owner)
    {
        return $owner->addresses()->with('city')->where('status', true)->where('type', 'physical')->get();
    }
}