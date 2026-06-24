<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\PatientHistory;
use App\Models\PatientMedication;
use App\Models\PatientAllergy;
use App\Models\PatientSurgery;
use App\Models\PatientFamilyHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessConsultationAudio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Notifications\ConsultationAudioUploadFailedNotification;
use Illuminate\Validation\Rule;

class PartnerPatientController extends Controller
{
    public function index(Request $request)
    {        
        $doctor = auth()->user();
        $plan = auth()->user()->doctor->settings->plan;
        
        $querySearch = $request->input('query');

        // 1. Base de la consulta: Pacientes que han tenido citas con este doctor
        $query = Patient::whereHas('appointments', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        });

        // 2. Aplicar Restricción según Plan
        if ($plan?->can_search_patients && $querySearch) {
            $query->where(function ($q) use ($querySearch) {
                // Buscamos el nombre en la tabla USERS vinculada
                $q->whereHas('user', function ($qu) use ($querySearch) {
                    $qu->where('name', 'LIKE', "%{$querySearch}%");
                })
                // Buscamos el documento en la tabla PATIENTS (asegúrate de que el campo sea 'identification' o 'documento')
                ->orWhere('identification', 'LIKE', "%{$querySearch}%");
            });
        } else {
            // Plan FREE: Forzamos que solo vea pacientes que tengan cita HOY
            $query->whereHas('appointments', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                  ->whereDate('date', now());
            });
        }

        // 3. Ejecutar con paginación y límite del plan
        $patients = $query->with(['user', 'appointments']) // Eager loading para evitar el problema N+1
            ->limit($plan?->max_patients_list)
            ->paginate(15);
        
        return view('partner.patients.index', compact('patients', 'plan'));
    }

    public function storeHistory(Request $request, $id)
    {        
        $doctor = auth()->user();
        $doctor = Doctor::where('user_id', $doctor->id)->first();

        $patient = Patient::findOrFail($id);

        // SEGURIDAD: el doctor solo puede escribir notas de pacientes con los que tiene una cita
        $hasRelation = $patient->appointments()->where('doctor_id', $doctor->id)->exists();
        if (! $hasRelation) {
            abort(403, 'No tienes permiso para registrar una nota para este paciente.');
        }
        
        $validated = $request->validate([
            'entry_type'              => ['required', 'in:consultation,follow_up,emergency,note'],
            'reason_for_consultation' => ['required', 'string', 'max:255'],
            'symptoms'                => ['nullable', 'string'],
            'diagnosis'               => ['required', 'string'],
            'treatment_plan'          => ['nullable', 'string'],
            'appointment_id'          => ['nullable', 'exists:appointments,id'],

            // Medicamento opcional: si viene el nombre, se vuelve obligatorio el resto
            'medication_name'         => ['nullable', 'required_with:medication_dosage,medication_frequency', 'string', 'max:255'],
            'medication_dosage'       => ['nullable', 'string', 'max:100'],
            'medication_frequency'    => ['nullable', 'string', 'max:100'],
            'medication_notes'        => ['nullable', 'string'],
        ]);
        
        DB::transaction(function () use ($validated, $patient, $doctor) {
            PatientHistory::create([
                'patient_id'               => $patient->id,
                'doctor_id'                => $doctor->id,
                'appointment_id'           => $validated['appointment_id'] ?? null,
                'entry_type'               => $validated['entry_type'],
                'reason_for_consultation'  => $validated['reason_for_consultation'],
                'symptoms'                 => $validated['symptoms'] ?? null,
                'diagnosis'                => $validated['diagnosis'],
                'treatment_plan'           => $validated['treatment_plan'] ?? null,
            ]);

            if (! empty($validated['medication_name'])) {
                PatientMedication::create([
                    'patient_id' => $patient->id,
                    'name'       => $validated['medication_name'],
                    'dosage'     => $validated['medication_dosage'] ?? null,
                    'frequency'  => $validated['medication_frequency'] ?? null,
                    'notes'      => $validated['medication_notes'] ?? null,
                    'active'     => true,
                ]);
            }
        });

        return redirect()
            ->route('partner.patients.show', $patient->id)
            ->with('success', 'Nota de evolución guardada correctamente.');
    }

    public function show($id, $reference = null)
    {
        $doctor = auth()->user();
        $doctor = Doctor::where('user_id', $doctor->id)->first();        
        $plan = auth()->user()->doctor->settings->plan;

        // 1. Cargamos al paciente con sus relaciones
        // Incluimos citas ordenadas para ver el historial clínico correctamente
        $patient = Patient::with(['user', 'familyHistories', 'city', 'department', 'appointments' => function($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id)
                ->orderBy('date', 'desc');
        }, 'appointments.service'])
        ->where('id', $id)
        ->firstOrFail();

        // 2. SEGURIDAD: Verificar que el paciente tiene relación con este doctor
        // Evita que un doctor vea datos de pacientes ajenos cambiando el ID en la URL        
        if ($patient->appointments->isEmpty()) {
            abort(403, 'No tienes permiso para acceder a la ficha de este paciente.');
        }    
        
        // Capturamos el ID de la cita si viene en la URL
        $appointmentId = '';
        $allowed = false;
        if ($reference) {
            $appointment = Appointment::where('reference', $reference)->first(); 
            $appointmentId = $appointment->id;
                        
            // Verificar si puede editar notas
            $canEditNotes = $this->verificarSiPuedeEditarNotas($appointment);
            if ($canEditNotes) {
               $allowed = true; 
            }
        }
        
        return view('partner.patients.show', compact('patient', 'doctor', 'plan', 'appointmentId', 'reference', 'allowed'));
    }    

    private function verificarSiPuedeEditarNotas(Appointment $appointment)
    {
        // Si la cita es futura, NO puede editar
        if ($appointment->date->isFuture()) {
            return false;
        }

        // Si la cita fue hace 48 horas o menos, SÍ puede editar
        $horasDesdeConsulta = $appointment->date->diffInHours(now());
        return $horasDesdeConsulta <= 48;
    }

    // use App\Jobs\ProcessConsultationAudio;
    // use Illuminate\Support\Facades\Cache;
    // use Illuminate\Support\Str;
    public function uploadConsultationAudio(Request $request, $id)
    {
        $doctor = auth()->user();
        $doctor = Doctor::where('user_id', $doctor->id)->first();

        $patient = Patient::findOrFail($id);

        $hasRelation = $patient->appointments()->where('doctor_id', $doctor->id)->exists();
        if (! $hasRelation) {
            abort(403, 'No tienes permiso para registrar una nota para este paciente.');
        }

        // Gancho de plan: feature solo disponible si el plan del doctor lo habilita
        $plan = $doctor->settings->plan;
        if (! $plan->ai_scribe_enabled) {
            return response()->json([
                'message' => 'Tu plan actual no incluye el asistente de consulta con IA.',
            ], 403);
        }

        $request->validate([
            'audio' => ['required', 'file', 'mimes:webm,ogg,mp3,wav,m4a', 'max:51200'], // 50MB
        ]);

        $jobToken = (string) Str::uuid();
        $tmpDisk = 'local'; // disco privado, no público
        $tmpPath = "ai-scribe-tmp/{$jobToken}." . $request->file('audio')->extension();

        $request->file('audio')->storeAs(
            dirname($tmpPath),
            basename($tmpPath),
            $tmpDisk
        );

        Cache::put("ai_scribe:{$jobToken}", ['status' => 'queued'], now()->addMinutes(15));

        ProcessConsultationAudio::dispatch(
            jobToken: $jobToken,
            tmpDisk: $tmpDisk,
            tmpPath: $tmpPath,
            mimeType: $request->file('audio')->getMimeType(),
            doctorId: $doctor->id,
            patientId: $patient->id,
        );

        return response()->json(['job_token' => $jobToken]);
    }

    public function consultationAudioStatus(string $jobToken)
    {
        $result = Cache::get("ai_scribe:{$jobToken}");

        if (! $result) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($result);
    }

    public function notifyPendingAudio(Request $request, $id)
    {
        $doctor = auth()->user();
        $doctor = Doctor::where('user_id', $doctor->id)->first();

        $patient = Patient::findOrFail($id);

        $hasRelation = $patient->appointments()->where('doctor_id', $doctor->id)->exists();
        if (! $hasRelation) {
            abort(403);
        }

        $validated = $request->validate([
            'appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        auth()->user()->notify(
            new ConsultationAudioUploadFailedNotification($patient, $validated['appointment_id'] ?? null)
        );

        return response()->json(['ok' => true]);
    }

    public function updateCondition(Request $request, Patient $patient)
    {
        // 1. Validar la petición (permitimos que sea null o string largo)
        $request->validate([
            'permanent_conditions' => 'nullable|string|max:1000',
        ]);

        try {
            // 2. Actualizar el campo en el modelo
            $patient->update([
                'permanent_conditions' => $request->permanent_conditions,
            ]);

            // 3. Responder con éxito para el Fetch de Alpine
            return response()->json([
                'success' => true,
                'message' => 'Condición permanente actualizada con éxito.',
                'permanent_conditions' => $patient->permanent_conditions
            ]);

        } catch (\Exception $e) {
            // 4. Manejo de errores internos
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar guardar los datos.'
            ], 500);
        }
    }

    public function storeAllergy(Request $request, Patient $patient)
    {
        // Validamos estrictamente contra los ENUM de tu base de datos
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['drug', 'food', 'environment', 'other'])],
            'severity' => ['required', Rule::in(['mild', 'moderate', 'severe'])],
            'reaction' => 'nullable|string|max:1000',
        ]);

        try {
            // Creamos la alergia vinculándola al paciente actual
            $allergy = PatientAllergy::create([
                'patient_id' => $patient->id,
                'name' => $request->name,
                'type' => $request->type,
                'severity' => $request->severity,
                'reaction' => $request->reaction,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alergia agregada correctamente.',
                'allergy' => $allergy // Devolvemos el objeto para pintarlo en la vista con Alpine
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la alergia.'
            ], 500);
        }
    }

    public function storeSurgery(Request $request, Patient $patient)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|integer|digits:4|between:1900,' . date('Y'),
            'observations' => 'nullable|string|max:1000',
            'anesthesia_complications' => 'required|boolean',
            'anesthesia_details' => 'nullable|required_if:anesthesia_complications,1|string|max:1000',
        ]);

        try {
            $surgery = PatientSurgery::create([
                'patient_id' => $patient->id,
                'name' => $request->name,
                'year' => $request->year,
                'observations' => $request->observations,
                'anesthesia_complications' => $request->anesthesia_complications,
                'anesthesia_details' => $request->anesthesia_complications ? $request->anesthesia_details : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cirugía registrada con éxito.',
                'surgery' => $surgery
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la cirugía.'
            ], 500);
        }
    }

    public function storeFamilyHistory(Request $request, Patient $patient)
    {
        $request->validate([
            'condition' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $history = PatientFamilyHistory::create([
                'patient_id' => $patient->id,
                'condition' => $request->condition,
                'relationship' => $request->relationship,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Antecedente familiar registrado con éxito.',
                'history' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar el antecedente familiar.'
            ], 500);
        }
    }
}
