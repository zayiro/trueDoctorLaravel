<?php

namespace App\Http\Controllers;

use App\Models\MedicalExpertise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalExpertiseController extends Controller
{
    /**
     * Obtiene el Tenant actual (Doctor o Clinic) y su respectiva columna en la BD.
     */
    private function getOwner(): array
    {
        $user = auth()->user();

        if ($user->role === 'clinic') {
            return [
                'model' => $user->clinic,
                'column' => 'clinic_id',
                'id' => $user->clinic->id
            ];
        }

        if ($user->role === 'doctor') {
            return [
                'model' => $user->doctor,
                'column' => 'doctor_id',
                'id' => $user->doctor->id
            ];
        }

        abort(403, 'Unauthorized tenant role.');
    }

    /**
     * Listado de especialidades médicas o enfermedades del Tenant actual.
     */
    public function index()
    {
        $owner = $this->getOwner();
        
        // Filtra dinámicamente según el tipo de inquilino conectado
        $expertises = MedicalExpertise::where($owner['column'], $owner['id'])->get();
        
        return view('partner.expertises.index', compact('expertises', 'owner'));
    }

    /**
     * Procesa y almacena una nueva área de experiencia médica.
     */
    public function store(Request $request)
    {
        $owner = $this->getOwner();

        // 1. Validación de reglas de negocio básicas
        $validated = $request->validate([
            'disease_name' => ['required', 'string', 'max:255'],
            'symptoms_keywords' => ['required', 'string'],
        ]);

        // 2. Validación de límites de suscripción del SaaS
        if (method_exists($owner['model'], 'canAddMoreExpertises') && !$owner['model']->canAddMoreExpertises()) {
            return redirect()->back()->with('error', 'Your current plan limit for medical expertises has been reached.');
        }

        // 3. Asignación masiva inyectando dinámicamente la columna del dueño
        MedicalExpertise::create(array_merge($validated, [
            $owner['column'] => $owner['id']
        ]));

        return redirect()->route('partner.expertises.index')
            ->with('success', 'Medical expertise added successfully.');
    }

    /**
     * Formulario de edición con validación estricta de Tenancy.
     */
    public function edit(MedicalExpertise $expertise)
    {
        $owner = $this->getOwner();

        // SEGURIDAD: Evitar que un inquilino altere o inspeccione registros ajenos
        if ($expertise->{$owner['column']} !== $owner['id']) {
            abort(403, 'Unauthorized action.');
        }

        return view('partner.expertises.edit', compact('expertise'));
    }

    /**
     * Procesa la actualización del registro médico.
     */
    public function update(Request $request, MedicalExpertise $expertise)
    {
        $owner = $this->getOwner();

        // SEGURIDAD: Control de acceso basado en pertenencia del Tenant
        if ($expertise->{$owner['column']} !== $owner['id']) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'disease_name' => ['required', 'string', 'max:255'],
            'symptoms_keywords' => ['required', 'string'],
        ]);

        $expertise->update($validated);

        return redirect()->route('partner.expertises.index')
            ->with('success', 'Medical expertise updated successfully.');
    }

    /**
     * Elimina el registro médico del ecosistema del Tenant.
     */
    public function destroy(MedicalExpertise $expertise)
    {
        $owner = $this->getOwner();

        // SEGURIDAD: Evitar eliminación cruzada entre cuentas
        if ($expertise->{$owner['column']} !== $owner['id']) {
            abort(403, 'Unauthorized action.');
        }

        $expertise->delete();

        return redirect()->route('partner.expertises.index')
            ->with('success', 'Medical expertise removed successfully.');
    }
}
