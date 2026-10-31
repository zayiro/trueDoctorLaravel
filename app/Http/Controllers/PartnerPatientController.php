<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientFamilyHistory;
use App\Models\PatientHistory;
use App\Models\PatientMedication;
use App\Models\PatientSurgery;
use App\Jobs\ProcessConsultationAudio;
use App\Notifications\ConsultationAudioUploadFailedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PartnerPatientController extends Controller
{
    /**
     * 🔒 Helper centralizado para obtener el perfil del médico autenticado.
     * Evita la duplicación de consultas en cada método.
     */
    private function getAuthenticatedDoctor(): Doctor
    {
        $user = Auth::user();
        return $user->doctor ?? abort(403, 'Perfil de médico no encontrado o no configurado.');
    }

    /**
     * 🔒 Valida que el médico tenga una relación histórica con el paciente.
     * Previene vulnerabilidades IDOR (Insecure Direct Object Reference).
     */
    private function ensurePatientRelation(Patient $patient, int $doctorId): void
    {
        $hasRelation = $patient->appointments()->where('doctor_id', $doctorId)->exists();
        if (!$hasRelation) {
            abort(403, 'No tienes permiso para acceder o modificar la información de este paciente.');
        }
    }

    /**
     * Muestra la lista de pacientes del médico, con búsqueda optimizada en SQL.
     */
    public function index(Request $request)
    {
        $doctor = $this->getAuthenticatedDoctor();
        $doctorId = $doctor->id;
        $plan = $doctor->settings?->plan;
        $querySearch = $request->input('query');

        // Query base: Pacientes que han tenido citas con este doctor
        $baseQuery = Patient::whereHas('appointments', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        })->with(['user', 'appointments']);

        if ($plan?->can_search_patients && $querySearch) {
            // ✅ CORRECCIÓN CRÍTICA: Búsqueda en SQL, no en memoria (previene OOM)
            $patients = $baseQuery->where(function ($q) use ($querySearch) {
                $q->whereHas('user', function ($uq) use ($querySearch) {
                    $uq->where('name', 'LIKE', "%{$querySearch}%")
                       ->orWhere('email', 'LIKE', "%{$querySearch}%");
                })->orWhere('identification', 'LIKE', "%{$querySearch}%");
            })->paginate(15)->withQueryString();
        } else {
            // Modo restringido: Solo pacientes con citas de hoy, limitado por plan
            $patients = $baseQuery->whereHas('appointments', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                  ->whereDate('date', Carbon::today());
            })
            ->limit($plan?->max_patients_list ?? 15)
            ->paginate(15);
        }

        return view('partner.patients.index', compact('patients', 'plan'));
    }

    /**
     * Registra una nota de evolución o historial clínico (Formato SOAP).
     */
    public function storeHistory(Request $request, int $patientId)
    {
        $doctor = $this->getAuthenticatedDoctor();
        $patient = Patient::findOrFail($patientId);
        
        $this->ensurePatientRelation($patient, $doctor->id);

        // 1. Validación alineada con la nueva estructura de la tabla patient_histories
        $validated = $request->validate([
            'entry_type'      => ['required', Rule::in(['consultation', 'follow_up', 'emergency', 'note'])],
            'appointment_id'  => ['nullable', 'exists:appointments,id'],
            'cie10_code'      => ['nullable', 'string', 'max:20'],
            'soap_subjective' => ['nullable', 'string'],
            'soap_objective'  => ['nullable', 'string'],
            'soap_assessment' => ['required', 'string'], // El diagnóstico/evaluación es obligatorio
            'soap_plan'       => ['nullable', 'string'],
            
            // Mantenemos validación de medicamentos por compatibilidad con el partial history-form
            'medication_name'      => ['nullable', 'required_with:medication_dosage,medication_frequency', 'string', 'max:255'],
            'medication_dosage'    => ['nullable', 'string', 'max:100'],
            'medication_frequency' => ['nullable', 'string', 'max:100'],
            'medication_notes'     => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated, $patient, $doctor) {
                // 2. Crear la nota clínica con la estructura SOAP estandarizada
                PatientHistory::create([
                    'patient_id'      => $patient->id,
                    'doctor_id'       => $doctor->id,
                    'appointment_id'  => $validated['appointment_id'] ?? null,
                    'entry_type'      => $validated['entry_type'],
                    'status'          => 'signed', // Por defecto, al guardar desde el formulario se considera firmada
                    'cie10_code'      => $validated['cie10_code'] ?? null,
                    'ai_assisted'     => false,    // Por defecto es manual (la IA lo cambiaría a true en el futuro)
                    'soap_subjective' => $validated['soap_subjective'] ?? null,
                    'soap_objective'  => $validated['soap_objective'] ?? null,
                    'soap_assessment' => $validated['soap_assessment'],
                    'soap_plan'       => $validated['soap_plan'] ?? null,
                ]);

                // 3. Registrar medicamentos si se proporcionaron
                if (!empty($validated['medication_name'])) {
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

            // 4. Respuesta dual: AJAX (para Alpine.js) o Redirección tradicional
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Nota de evolución guardada correctamente.',
                ]);
            }

            return redirect()->route('partner.patients.show', $patient->id)
                ->with('success', 'Nota de evolución guardada correctamente.');
                
        } catch (Throwable $e) {
            Log::error("Error al guardar historial para paciente {$patient->id}", [
                'error' => $e->getMessage(),
                'doctor_id' => $doctor->id,
            ]);
            
            // Respuesta de error dual
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error al guardar la nota. Por favor, intenta de nuevo.',
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Ocurrió un error al guardar la nota. Por favor, intenta de nuevo.')
                                ->withInput(); // Preservar input en caso de error tradicional
        }
    }

    /**
     * Muestra la ficha completa del paciente.
     */
    public function show(int $patientId, ?string $reference = null)
    {
        $doctor = $this->getAuthenticatedDoctor();
        $plan = $doctor->settings?->plan;

        $patient = Patient::with([
            'user',
            'familyHistories',
            'city',
            'department',
            'appointments' => function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id)
                      ->whereDate('date', '>=', Carbon::today())
                      ->orderBy('date', 'asc')
                      ->orderBy('start_time', 'asc');
            },
            'appointments.service'
        ])->findOrFail($patientId);

        $this->ensurePatientRelation($patient, $doctor->id);

        $appointmentId = null;
        $allowed = false;

        if ($reference) {
            $appointment = Appointment::where('reference', $reference)->first();
            if ($appointment) {
                $appointmentId = $appointment->id;
                $allowed = $this->canEditNotes($appointment);
            }
        }

        return view('partner.patients.show', compact('patient', 'doctor', 'plan', 'appointmentId', 'reference', 'allowed'));
    }

    /**
     * Determina si una cita permite edición de notas (hasta 48h después).
     */
    private function canEditNotes(Appointment $appointment): bool
    {
        if ($appointment->date->isFuture()) {
            return false;
        }
        return now()->diffInHours($appointment->date) <= 48;
    }

    /**
     * Procesa la subida de audio para el asistente de consulta con IA.
     */
    public function uploadConsultationAudio(Request $request, int $patientId): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $patient = Patient::findOrFail($patientId);
        
        $this->ensurePatientRelation($patient, $doctor->id);

        $plan = $doctor->settings?->plan;
        if (!$plan?->ai_scribe_enabled) {
            return response()->json(['message' => 'Tu plan actual no incluye el asistente de consulta con IA.'], 403);
        }

        $validated = $request->validate([
            'audio' => ['required', 'file', 'mimes:webm,ogg,mp3,wav,m4a', 'max:51200'], // 50MB
        ]);

        $jobToken = (string) Str::uuid();
        $tmpDisk = 'local';
        $extension = $request->file('audio')->extension();
        $tmpPath = "ai-scribe-tmp/{$jobToken}.{$extension}";

        $request->file('audio')->storeAs(dirname($tmpPath), basename($tmpPath), $tmpDisk);

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

    /**
     * Consulta el estado del procesamiento del audio.
     */
    public function consultationAudioStatus(string $jobToken): JsonResponse
    {
        $result = Cache::get("ai_scribe:{$jobToken}");
        
        if (!$result) {
            return response()->json(['status' => 'not_found'], 404);
        }
        
        return response()->json($result);
    }

    /**
     * Notifica al médico sobre un fallo en el procesamiento del audio.
     */
    public function notifyPendingAudio(Request $request, int $patientId): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $patient = Patient::findOrFail($patientId);
        
        $this->ensurePatientRelation($patient, $doctor->id);

        $validated = $request->validate([
            'appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        Auth::user()->notify(
            new ConsultationAudioUploadFailedNotification($patient, $validated['appointment_id'] ?? null)
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Actualiza las condiciones permanentes del paciente (AJAX).
     */
    public function updateCondition(Request $request, Patient $patient): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $this->ensurePatientRelation($patient, $doctor->id);

        $validated = $request->validate([
            'permanent_conditions' => 'nullable|string|max:1000',
        ]);

        try {
            $patient->update(['permanent_conditions' => $validated['permanent_conditions']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Condición permanente actualizada con éxito.',
                'permanent_conditions' => $patient->permanent_conditions
            ]);
        } catch (Throwable $e) {
            Log::error("Error al actualizar condiciones del paciente {$patient->id}", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al guardar los datos.'], 500);
        }
    }

    /**
     * Registra una nueva alergia (AJAX).
     */
    public function storeAllergy(Request $request, Patient $patient): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $this->ensurePatientRelation($patient, $doctor->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['drug', 'food', 'environment', 'other'])],
            'severity' => ['required', Rule::in(['mild', 'moderate', 'severe'])],
            'reaction' => 'nullable|string|max:1000',
        ]);

        try {
            $allergy = PatientAllergy::create([
                'patient_id' => $patient->id,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'severity' => $validated['severity'],
                'reaction' => $validated['reaction'],
            ]);

            return response()->json(['success' => true, 'message' => 'Alergia agregada correctamente.', 'allergy' => $allergy]);
        } catch (Throwable $e) {
            Log::error("Error al registrar alergia para paciente {$patient->id}", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se pudo registrar la alergia.'], 500);
        }
    }

    /**
     * Registra una cirugía previa (AJAX).
     */
    public function storeSurgery(Request $request, Patient $patient): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $this->ensurePatientRelation($patient, $doctor->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => ['nullable', 'integer', 'digits:4', 'between:1900,' . date('Y')],
            'observations' => 'nullable|string|max:1000',
            'anesthesia_complications' => 'required|boolean',
            'anesthesia_details' => 'nullable|required_if:anesthesia_complications,1|string|max:1000',
        ]);

        try {
            $surgery = PatientSurgery::create([
                'patient_id' => $patient->id,
                'name' => $validated['name'],
                'year' => $validated['year'],
                'observations' => $validated['observations'],
                'anesthesia_complications' => $validated['anesthesia_complications'],
                'anesthesia_details' => $validated['anesthesia_complications'] ? $validated['anesthesia_details'] : null,
            ]);

            return response()->json(['success' => true, 'message' => 'Cirugía registrada con éxito.', 'surgery' => $surgery]);
        } catch (Throwable $e) {
            Log::error("Error al registrar cirugía para paciente {$patient->id}", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se pudo registrar la cirugía.'], 500);
        }
    }

    /**
     * Registra un antecedente familiar (AJAX).
     */
    public function storeFamilyHistory(Request $request, Patient $patient): JsonResponse
    {
        $doctor = $this->getAuthenticatedDoctor();
        $this->ensurePatientRelation($patient, $doctor->id);

        $validated = $request->validate([
            'condition' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $history = PatientFamilyHistory::create([
                'patient_id' => $patient->id,
                'condition' => $validated['condition'],
                'relationship' => $validated['relationship'],
                'notes' => $validated['notes'],
            ]);

            return response()->json(['success' => true, 'message' => 'Antecedente familiar registrado con éxito.', 'history' => $history]);
        } catch (Throwable $e) {
            Log::error("Error al registrar antecedente familiar para paciente {$patient->id}", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'No se pudo registrar el antecedente.'], 500);
        }
    }
}