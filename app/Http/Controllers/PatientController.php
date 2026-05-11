<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientSurgery;
use App\Models\PatientFamilyHistory;
use App\Models\PatientMedication;
use App\Models\Insurance;
use App\Models\Department;

class PatientController extends Controller
{
    public function limpiarAcentos($texto) 
    {
        $remplazos = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N'
        ];

        $cadena = str_replace(array_keys($remplazos), array_values($remplazos), $texto);

        return $cadena;
    }

    public function index()
    {
        $patient = auth()->user()->patient;
        
        // Obtenemos todos los seguros disponibles para el select de la vista
        // Asegúrate de tener el modelo Insurance creado
        $insurances = Insurance::all(); 

        $departments = Department::orderBy('name')->get();

        return view('patient.patient-identification.index', compact('patient', 'insurances', 'departments'));
    }

    public function create()
    {
        // Si ya tiene un perfil, lo mandamos a editar en lugar de crear otro
        if (Patient::where('user_id', auth()->id())->exists()) {
            return redirect()->route('patients.edit', auth()->user()->patient);
        }

        $insurances = Insurance::all();
        return view('patient.form', compact('insurances'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'identification' => 'required|string|unique:patients,identification',
            'phone' => 'required|string|max:20',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'weight' => 'nullable|numeric|between:0,999.99',
            'height' => 'nullable|numeric|between:0,9.99', // Si envían metros (1.75)
            'insurance_id' => 'nullable|exists:insurances,id',
            'permanent_conditions' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'city_id' => 'nullable|exists:cities,id',
            'residence_zone' => 'required|in:Urbana,Rural',
            'occupation' => 'nullable|string|max:255',
            'civil_status' => 'nullable|in:Soltero/a,Casado/a,Unión Libre,Divorciado/a,Viudo/a',
            'ethnicity' => 'required|string',
            'affiliation_type' => 'nullable|in:Contributivo,Subsidiado,Vinculado,Particular,Otro',
            'regime_type' => 'required|in:General,Especial,Excepción',
            'sisben_level' => 'nullable|string|max:5',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string',
        ]);

        // Lógica para corregir la estatura si la envían en cm (ej: 170 -> 1.70)
        if ($request->filled('height') && $request->height > 10) {
            $validated['height'] = $request->height / 100;
        }

        // Forzamos el user_id del usuario autenticado
        $validated['user_id'] = auth()->id();

        Patient::create($validated);

        return redirect()->route('patient.patient-identification.index')
            ->with('success', 'Perfil médico creado exitosamente.');
    }

    public function edit(Patient $patient)
    {
        // SEGURIDAD: Validar que el paciente que intenta editar sea el suyo
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar este perfil.');
        }

        $insurances = Insurance::all();
        return view('patient.patient-identification.form', compact('patient', 'insurances'));
    }

    public function update(Request $request, Patient $patient)
    {
        // SEGURIDAD: Validar que el paciente pertenezca al usuario logueado
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'Acción no autorizada.');
        }

        $validated = $request->validate([
            // Ignoramos el ID del paciente actual para que no falle al validar su propia cédula
            'identification' => 'required|string|unique:patients,identification,' . $patient->id,
            'phone' => 'required|string|max:20',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'weight' => 'nullable|numeric|between:0,999.99',
            'height' => 'nullable|numeric|between:0,9.99',
            'insurance_id' => 'nullable|exists:insurances,id',
            'permanent_conditions' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'city_id' => 'nullable|exists:cities,id',
            'residence_zone' => 'required|in:Urbana,Rural',
            'occupation' => 'nullable|string|max:255',
            'civil_status' => 'nullable|in:Soltero/a,Casado/a,Unión Libre,Divorciado/a,Viudo/a',
            'ethnicity' => 'required|string',
            'affiliation_type' => 'nullable|in:Contributivo,Subsidiado,Vinculado,Particular,Otro',
            'regime_type' => 'required|in:General,Especial,Excepción',
            'sisben_level' => 'nullable|string|max:5',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string',
        ]);

        // Lógica de corrección de estatura (cm a m)
        if ($request->filled('height') && $request->height > 10) {
            $validated['height'] = $request->height / 100;
        }

        // Actualizamos el registro con los datos validados
        $patient->update($validated);

        return redirect()->route('patient.patient-identification.index')
            ->with('success', 'Tu perfil médico ha sido actualizado correctamente.');
    }

    public function indexAllergy()
    {
        $user = auth()->user();
        
        // Buscamos el paciente donde user_id sea igual al ID del usuario logueado
        $patient = Patient::with(['allergies' => function ($query) {
            $query->orderBy('created_at', 'desc')
            ->orderBy('type', 'asc'); // o 'desc'
        }])
        ->where('user_id', $user->id)
        ->firstOrFail();

        return view('patient.allergies.index', compact('patient'));
    }

    public function storeAllergy(Request $request, $patientId)
    {
        $name = $this->limpiarAcentos($request->name);
        // Verificar si ya existe una alergia idéntica creada recientemente (evita duplicados por refresco)
        $exists = PatientAllergy::where('patient_id', $patientId)
            ->where('name', $name)
            ->where('created_at', '>', now()->subSeconds(10))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esta alergia ya fue registrada hace un momento.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:drug,food,environment,other',
            'severity' => 'required|in:mild,moderate,severe',
            'reaction' => 'nullable|string|max:500',
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

    public function destroyAllergy(PatientAllergy $allergy)
    {
        // Seguridad: Verificar que el usuario sea el dueño o tenga permiso
        if ($allergy->patient_id !== auth()->user()->patient->id) { 
            abort(403); 
        }

        $allergy->delete();

        return back()->with('success', 'Alergia eliminada correctamente.');
    }

    public function appointments(Request $request)
    {
        $appointments = auth()->user()->patient->appointments()
        ->with(['doctor', 'service', 'address'])
        ->when($request->status, function ($query, $status) {
            return $query->where('status', $status);
        })
        // Orden complejo: 
        // 1. Citas futuras primero (ascendente: la más próxima arriba)
        // 2. Citas pasadas después (descendente: la última que pasó)
        ->orderByRaw("CASE WHEN date >= CURRENT_DATE THEN 0 ELSE 1 END")
        ->orderBy('date', 'asc') 
        ->orderBy('start_time', 'asc')
        ->paginate(10)
        ->withQueryString();

        //dd($appointments);

        return view('patient.appointments.index', compact('appointments'));
    }

    public function history()
    {
        $history = auth()->user()->patient->patientHistories()
            ->with(['doctor', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('patient.history.index', compact('history'));
    }

    public function surgeries()
    {
        $surgeries = auth()->user()->patient->surgeries()
            ->orderBy('year', 'desc')
            ->get();

        return view('patient.surgeries.index', compact('surgeries'));
    }

    public function storeSurgery(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'observations' => 'nullable|string',
            'anesthesia_complications' => 'boolean',
            'anesthesia_details' => 'required_if:anesthesia_complications,1|nullable|string',
        ]);

        auth()->user()->patient->surgeries()->create($validated);

        return redirect()->back()->with('success', 'Cirugía registrada correctamente.');
    }

    public function editSurgery(PatientSurgery $surgery)
    {
        if ($surgery->patient_id !== auth()->user()->patient->id) {
            abort(403);
        }

        return view('patient.surgeries.edit', compact('surgery'));
    }

    public function updateSurgery(Request $request, PatientSurgery $surgery)
    {
        // Verificar que la cirugía pertenezca al paciente autenticado
        if ($surgery->patient_id !== auth()->user()->patient->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'observations' => 'nullable|string',
            'anesthesia_complications' => 'boolean',
            'anesthesia_details' => 'required_if:anesthesia_complications,1|nullable|string',
        ]);

        // El checkbox no enviado se trata como false
        $validated['anesthesia_complications'] = $request->has('anesthesia_complications');

        $surgery->update($validated);

        return redirect()->route('patient.surgeries.index')->with('success', '¡Registro quirúrgico actualizado con éxito!');
    }

    public function destroySurgery(PatientSurgery $surgery)
    {
        // Verificar que la cirugía pertenezca al paciente actual
        if ($surgery->patient_id !== auth()->user()->patient->id) {
            abort(403);
        }

        $surgery->delete();

        return redirect()->route('patient.surgeries.index')
            ->with('success', 'El registro quirúrgico ha sido eliminada del historial.');
    }

    public function indexFamilyHistory()
    {
        $patient = auth()->user()->patient; // Obtenemos el paciente
        $familyHistory = $patient->familyHistories()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Enviamos AMBAS variables a la vista
        return view('patient.family-history.index', compact('familyHistory', 'patient'));
    }

    public function storeFamilyHistory(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'condition' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        PatientFamilyHistory::create($validated);

        return back()->with('success', 'Antecedente familiar registrado correctamente.');
    }

    public function destroyFamilyHistory($id)
    {
        // Buscamos el registro asegurando que pertenezca al paciente actual
        $history = auth()->user()->patient->familyHistories()->findOrFail($id);

        $history->delete();

        return redirect()->back()->with('success', 'Antecedente familiar eliminado correctamente.');
    }


    public function indexMedication()
    {
        $medications = auth()->user()->patient->medications()
            ->orderBy('active', 'desc') // Los activos primero
            ->orderBy('name', 'asc')
            ->get();

        // Obtenemos el paciente del usuario actual
        $patient = auth()->user()->patient;    

        return view('patient.medications.index', compact('medications', 'patient'));
    }

    public function storeMedication(Request $request)
    {
        // 1. Validamos solo los datos del medicamento
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'dosage'     => 'nullable|string|max:255',
            'frequency'  => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
            'active'     => 'boolean',
        ]);

        // 2. Obtenemos el paciente autenticado
        $patient = auth()->user()->patient;

        // 3. Creamos el medicamento asociado a ese paciente
        $patient->medications()->create([
            'name'      => $validated['name'],
            'dosage'    => $validated['dosage'],
            'frequency' => $validated['frequency'],
            'notes'     => $validated['notes'],
            'active'    => $request->has('active') ? $request->active : true,
        ]);

        return back()->with('success', 'Medicamento añadido correctamente.');
    }

    public function updateMedication(Request $request, PatientMedication $medication)
    {
        // 1. Validar que el medicamento pertenezca al paciente autenticado
        if ($medication->patient_id !== auth()->user()->patient->id) {
            abort(403, 'No tienes permiso para editar este medicamento.');
        }

        // 2. Validar los datos recibidos
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'dosage'     => 'nullable|string|max:255',
            'frequency'  => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
            'active'     => 'required|boolean',
        ]);

        // 3. Actualizar en la base de datos
        $medication->update($validated);

        return back()->with('success', 'Medicamento actualizado con éxito.');
    }
}
