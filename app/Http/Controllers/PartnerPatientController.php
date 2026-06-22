<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\PatientHistory;
use App\Models\PatientMedication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessConsultationAudio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Notifications\ConsultationAudioUploadFailedNotification;

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
}
