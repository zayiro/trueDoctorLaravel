<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Appointment;
use App\Models\PatientSurgery;
use App\Models\PatientFamilyHistory;
use App\Models\PatientMedication;
use App\Models\Insurance;
use App\Models\Department;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PatientController extends Controller
{
    use AuthorizesRequests;

    /**
     * Limpia caracteres especiales y acentos.
     */
    public function limpiarAcentos($texto) 
    {
        $remplazos = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N'
        ];

        return str_replace(array_keys($remplazos), array_values($remplazos), $texto);
    }

    /**
     * Muestra la consola principal de identificación del paciente.
     */
    public function index()
    {
        $patient = auth()->user()->patient;
        
        // Carga maestra de catálogos para los selectores de la interfaz
        $insurances = Insurance::all(); 
        $departments = Department::orderBy('name')->get();        

        return view('patient.patient-identification.index', compact('patient', 'insurances', 'departments'));
    }

    /**
     * Redirección de control: el paciente ya se crea en blanco desde CreateNewUser.
     */
    public function create()
    {
        $patient = auth()->user()->patient;

        if ($patient) {
            return redirect()->route('patients.edit', $patient);
        }

        $insurances = Insurance::all();
        return view('patient.form', compact('insurances'));
    }

    /**
     * Registro de respaldo (el registro core ahora es automático mediante Fortify).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'identification' => ['required', 'string', 'unique:patients,identification'],
            'phone' => ['required', 'string', 'max:20'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'weight' => ['nullable', 'numeric', 'between:0,999.99'],
            'height' => ['nullable', 'numeric', 'between:0,999.99'], // Manejado automáticamente por el mutador del modelo
            'insurance_id' => ['nullable', 'exists:insurances,id'],
            'permanent_conditions' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'residence_zone' => ['required', 'in:Urbana,Rural'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'in:Soltero/a,Casado/a,Unión Libre,Divorciado/a,Viudo/a'],
            'ethnicity' => ['required', 'string'],
            'affiliation_type' => ['nullable', 'in:Contributivo,Subsidiado,Vinculado,Particular,Otro'],
            'regime_type' => ['required', 'in:General,Especial,Excepción'],
            'sisben_level' => ['nullable', 'string', 'max:5'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
        ]);

        $validated['user_id'] = auth()->id();

        Patient::create($validated);

        return redirect()->route('patient.patient-identification.index')
            ->with('success', 'Perfil médico creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar la información médica.
     */
    public function edit(Patient $patient)
    {
        // SEGURIDAD: Validar tenencia del recurso (Multi-tenancy unificado)
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar este perfil.');
        }

        $insurances = Insurance::all();
        return view('patient.patient-identification.form', compact('patient', 'insurances'));
    }

    /**
     * Actualiza los datos clínicos y socioeconómicos del paciente.
     */
    public function update(Request $request, Patient $patient)
    {
        // SEGURIDAD: Validar tenencia del recurso antes de mutar la BD
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'Acción no autorizada.');
        }

        $validated = $request->validate([
            // Ignoramos el ID actual para evitar colisiones de unicidad sobre su propio documento
            'identification' => ['required', 'string', 'unique:patients,identification,' . $patient->id],
            'phone' => ['required', 'string', 'max:20'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'weight' => ['nullable', 'numeric', 'between:0,999.99'],
            'height' => ['nullable', 'numeric', 'between:0,999.99'], // Removida la división matemática manual, el modelo lo hace solo
            'insurance_id' => ['nullable', 'exists:insurances,id'],
            'permanent_conditions' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'residence_zone' => ['required', 'in:Urbana,Rural'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'in:Soltero/a,Casado/a,Unión Libre,Divorciado/a,Viudo/a'],
            'ethnicity' => ['required', 'string'],
            'affiliation_type' => ['nullable', 'in:Contributivo,Subsidiado,Vinculado,Particular,Otro'],
            'regime_type' => ['required', 'in:General,Especial,Excepción'],
            'sisben_level' => ['nullable', 'string', 'max:5'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
        ]);

        $patient->update($validated);

        return redirect()->route('patient.patient-identification.index')
            ->with('success', 'Tu perfil médico ha sido actualizado correctamente.');
    }

    /**
     * Carga el historial de alergias del paciente.
     */
    public function indexAllergy()
    {
        $user = auth()->user();
        
        $patient = Patient::with(['allergies' => function ($query) {
            $query->latest()
                  ->orderBy('type', 'asc');
        }])
        ->where('user_id', $user->id)
        ->firstOrFail();

        return view('patient.allergies.index', compact('patient'));
    }

    /**
     * Registra una nueva alergia con bloqueo temporal de duplicados.
     */
    public function storeAllergy(Request $request, $patientId)
    {
        $name = $this->limpiarAcentos($request->name);
        
        // Prevención de doble envío transaccional (anti-spam de peticiones paralelas)
        $exists = PatientAllergy::where('patient_id', $patientId)
            ->where('name', $name)
            ->where('created_at', '>', now()->subSeconds(10))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esta alergia ya fue registrada hace un momento.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:drug,food,environment,other'],
            'severity' => ['required', 'in:mild,moderate,severe'],
            'reaction' => ['nullable', 'string', 'max:500'],
        ]);

        PatientAllergy::create([
            'patient_id' => $patientId,
            'name' => trim(ucfirst($name)),
            'type' => $request->type,
            'severity' => $request->severity,
            'reaction' => $request->reaction,
        ]);

        return back()->with('success', 'Alergia agregada al historial.');
    }

    /**
     * Remueve una alergia validando estrictamente los límites del Tenant.
     */
    public function destroyAllergy(PatientAllergy $allergy)
    {
        // SEGURIDAD: Impedir manipulación cruzada de IDs de otros pacientes
        if ($allergy->patient_id !== auth()->user()->patient->id) {
            abort(403, 'Acción no autorizada sobre este registro.');
        }

        $allergy->delete();

        return back()->with('success', 'Alergia removida del historial.');
    }

    /**
     * Muestra el listado completo de citas médicas del paciente autenticado.
     */
    public function appointments()
    {        
        // 🛠️ REPARACIÓN DE EMERGENCIA PARA SPATIE
        $user = auth()->user();
        if (!$user->hasRole('patient')) {
            // Crea el rol en Spatie si no existe en la base de datos
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'patient']);
            // Vincula al usuario actual con el rol en la tabla 'model_has_roles'
            $user->assignRole('patient');
        }
        
        $user = auth()->user();
        $patient = $user->patient;

        // 1. Obtiene las citas próximas (Pendientes o Confirmadas) ordenadas de la más cercana a la más lejana
        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->with('doctor.user', 'address') // Carga el médico y la sede de atención
            ->orderBy('date', 'asc')
            ->get();

        // 2. Obtiene el historial histórico de consultas (Completadas, Canceladas, Asistidas)
        $pastAppointments = Appointment::where('patient_id', $patient->id)
            ->where(function($query) {
                $query->whereDate('date', '<', now()->toDateString())
                    ->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->with('doctor.user', 'address')
            ->orderBy('date', 'desc')
            ->paginate(10); // Paginación de 10 en 10 para no sobrecargar la vista

        return view('patient.appointments.index', compact('upcomingAppointments', 'pastAppointments'));
    }
    
    /**
     * Procesa la cancelación de una consulta médica solicitada por el paciente.
     */
    public function cancelWeb(Request $request, $id)
    {
        $patient = auth()->user()->patient;

        // Busca la cita asegurando que pertenezca estrictamente a este paciente (Seguridad Tenant)
        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        // Actualiza el estado a cancelado
        $appointment->update([
            'status' => 'cancelled'
        ]);

        return redirect()->route('patient.appointments.index')
            ->with('success', 'La cita médica ha sido cancelada exitosamente.');
    }
}
