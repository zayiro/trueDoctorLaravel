<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\DoctorSetting;
use App\Models\ClinicSetting;
use App\Models\Insurance;
use App\Models\Department;
use App\Models\PatientAllergy;
use App\Models\PatientMedication;
use App\Models\PatientHistory;
use App\Models\PatientSurgery;
use App\Models\PatientFamilyHistory;
use App\Services\AppointmentStateManager;
use App\Services\Appointments\AppointmentRescheduler;
use Illuminate\Auth\Access\AuthorizationException;
use App\Events\AppointmentCancelled;
use App\Notifications\AppointmentRescheduledNotification;
use App\Notifications\AppointmentCancelledNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PatientController extends Controller
{
    /**
     * Límite máximo de reprogramaciones permitidas por cita.
     */
    protected int $maxReschedules = AppointmentRescheduler::MAX_PATIENT_RESCHEDULES;

    /**
     * 🔒 Helper para validar que el paciente pertenece al usuario autenticado.
     */
    private function ensurePatientOwnership(Patient $patient): void
    {
        if ($patient->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para acceder o modificar este perfil médico.');
        }
    }

    /**
     * Limpia caracteres especiales y acentos para estandarización de datos clínicos.
     */
    public function limpiarAcentos(string $texto): string
    {
        $remplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N'
        ];
        return str_replace(array_keys($remplazos), array_values($remplazos), $texto);
    }

    /**
     * Muestra la consola principal de identificación del paciente.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $patient = $user->patient;
        
        $insurances = Insurance::all();
        $departments = Department::orderBy('name')->get();

        return view('patient.patient-identification.index', compact('patient', 'insurances', 'departments'));
    }

    /**
     * Redirección de control: el paciente ya se crea en blanco desde CreateNewUser.
     */
    public function create()
    {
        $patient = Auth::user()->patient;
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
            'height' => ['nullable', 'numeric', 'between:0,999.99'],
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

        $validated['user_id'] = Auth::id();
        Patient::create($validated);

        return redirect()->route('patient.patient-identification.index')
            ->with('success', 'Perfil médico creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar la información médica.
     */
    public function edit(Patient $patient)
    {
        $this->ensurePatientOwnership($patient);
        $insurances = Insurance::all();
        
        return view('patient.patient-identification.form', compact('patient', 'insurances'));
    }

    /**
     * Actualiza los datos clínicos y socioeconómicos del paciente.
     */
    public function update(Request $request, Patient $patient)
    {
        $this->ensurePatientOwnership($patient);

        $validated = $request->validate([
            'identification' => ['required', 'string', 'unique:patients,identification,' . $patient->id],
            'phone' => ['required', 'string', 'max:20'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'weight' => ['nullable', 'numeric', 'between:0,999.99'],
            'height' => ['nullable', 'numeric', 'between:0,999.99'],
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
     * Muestra el perfil de historial clínico.
     */
    public function history(Request $request)
    {
        $patient = $request->user()->patient()->with(['attachments', 'histories'])->firstOrFail();
        $this->ensurePatientOwnership($patient);

        return view('patient.history.index', compact('patient'));
    }

    /**
     * Muestra el historial clínico oficial del paciente en modo solo lectura.
     * Solo despliega registros firmados (signed) o con nota aclaratoria (amended).
     */
    public function clinicalHistory(): View
    {
        // Obtenemos el perfil de paciente del usuario autenticado
        $patient = Auth::user()->patient;

        // Consultamos únicamente las evoluciones clínicas cerradas/firmadas por los médicos
        $histories = PatientHistory::with([
                'doctor.user',
                'doctor.specialty',
                'appointment',
                'notes.doctor.user', // <-- Agrega esta línea para cargar las notas extemporáneas
            ])
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['signed', 'amended'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('patient.clinical-history', compact('patient', 'histories'));
    }

    /**
     * Carga el historial de alergias del paciente.
     */
    public function indexAllergy()
    {
        $user = Auth::user();
        $patient = Patient::with(['allergies' => function ($query) {
            $query->latest()->orderBy('type', 'asc');
        }])->where('user_id', $user->id)->firstOrFail();

        $this->ensurePatientOwnership($patient);

        return view('patient.allergies.index', compact('patient'));
    }

    /**
     * Registra una nueva alergia con bloqueo temporal de duplicados.
     */
    public function storeAllergy(Request $request, int $patientId)
    {
        $user = Auth::user();
        if ($user->patient?->id !== $patientId) {
            abort(403, 'Acción no autorizada.');
        }

        $name = $this->limpiarAcentos($request->name);

        // Prevención de doble envío transaccional (anti-spam)
        $exists = PatientAllergy::where('patient_id', $patientId)
            ->where('name', $name)
            ->where('created_at', '>', now()->subSeconds(10))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esta alergia ya fue registrada hace un momento.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:drug,food,environment,other'],
            'severity' => ['required', 'in:mild,moderate,severe'],
            'reaction' => ['nullable', 'string', 'max:500'],
        ]);

        PatientAllergy::create([
            'patient_id' => $patientId,
            'name' => trim(ucfirst($name)),
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'reaction' => $validated['reaction'],
        ]);

        return back()->with('success', 'Alergia agregada al historial.');
    }

    /**
     * Remueve una alergia validando estrictamente los límites del Tenant.
     */
    public function destroyAllergy(PatientAllergy $allergy)
    {
        $user = Auth::user();
        if ($allergy->patient_id !== $user->patient?->id) {
            abort(403, 'Acción no autorizada sobre este registro.');
        }

        $allergy->delete();
        return back()->with('success', 'Alergia removida del historial.');
    }

    /**
     * Muestra el listado completo de citas médicas del paciente autenticado.
     */
    public function appointments(): View
    {
        $user = Auth::user();
        $patient = $user->patient;

        if (!$patient) {
            return redirect()->route('profile.show')
                ->with('error', 'No se encontró un perfil de paciente asociado a tu cuenta.');
        }

        $now = Carbon::now(config('app.timezone', 'America/Bogota'));
        $statusFilter = request('status');

        // 1. PRÓXIMAS CONSULTAS: Citas futuras o en curso (solo si el filtro permite estados activos)
        $upcomingAppointments = collect();
        if (!$statusFilter || in_array($statusFilter, ['pending', 'confirmed'], true)) {
            $upcomingAppointments = Appointment::where('patient_id', $patient->id)
                ->where(function ($query) use ($now) {
                    $query->whereDate('date', '>', $now->toDateString())
                        ->orWhere(function ($q) use ($now) {
                            $q->whereDate('date', $now->toDateString())
                              ->where('end_time', '>', $now->toTimeString());
                        });
                })
                ->when(
                    $statusFilter,
                    fn ($query) => $query->where('status', $statusFilter),
                    fn ($query) => $query->whereIn('status', ['confirmed', 'pending'])
                )
                ->with(['doctor.user', 'clinic', 'service', 'address.city'])
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();
        }

        // 2. HISTORIAL DE CONSULTAS: Citas pasadas o cerradas (respetando el filtro seleccionado)
        $pastAppointments = Appointment::where('patient_id', $patient->id)
            ->when($statusFilter, function ($query) use ($statusFilter, $now) {
                // Si el paciente filtra explícitamente por un estado, mostramos únicamente ese estado
                $query->where('status', $statusFilter);

                // Si filtró por 'pending' o 'confirmed', en el historial solo van las que ya pasaron de hora
                if (in_array($statusFilter, ['pending', 'confirmed'], true)) {
                    $query->where(function ($sub) use ($now) {
                        $sub->whereDate('date', '<', $now->toDateString())
                            ->orWhere(function ($q) use ($now) {
                                $q->whereDate('date', $now->toDateString())
                                  ->where('end_time', '<=', $now->toTimeString());
                            });
                    });
                }
            }, function ($query) use ($now) {
                // Sin filtro activo: muestra las que ya pasaron de fecha/hora o están completadas/canceladas
                $query->where(function ($sub) use ($now) {
                    $sub->whereDate('date', '<', $now->toDateString())
                        ->orWhere(function ($q) use ($now) {
                            $q->whereDate('date', $now->toDateString())
                              ->where('end_time', '<=', $now->toTimeString());
                        });
                })->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->with([
                'doctor.user',
                'clinic',
                'service',
                'address.city',
                // Cargamos solo id y status para saber si ya tiene historia firmada sin traer textos pesados
                'patientHistory:id,appointment_id,status',
            ])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        $maxReschedules = $this->maxReschedules;
        
        return view('patient.appointments.index', compact(
            'upcomingAppointments',
            'pastAppointments',
            'maxReschedules'
        ));
    }

    /**
     * Procesa la cancelación de una consulta médica solicitada por el paciente.
     */
    public function cancelWeb(Request $request, int $id)
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            abort(403, 'Perfil de paciente no encontrado en el sistema.');
        }

        $appointment = Appointment::with(['doctor.settings', 'clinic.settings'])
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:255'
        ]);

        // Plazo, política del médico/clínica, transición y avisos viven en un solo sitio.
        $result = app(\App\Services\Appointments\AppointmentCancellationService::class)->cancel(
            $appointment,
            'patient',
            $request->filled('cancellation_reason') ? trim((string) $request->cancellation_reason) : null
        );

        if (!$result->ok) {
            return redirect()->back()->with('error', $result->message);
        }

        return redirect()->route('patient.appointments.index')
            ->with('success', 'La consulta médica ha sido cancelada exitosamente y el espacio horario ha sido devuelto a la disponibilidad pública.');
    }

        /**
     * Genera y descarga el reporte consolidado de la Historia Clínica en PDF.
     */
    public function downloadClinicalHistory(Patient $patient)
    {
        $this->ensurePatientOwnership($patient);

        try {
            $patient->load([
                'user',
                'allergies',
                'surgeries',
                'familyHistories',
                'medications',
                // Cargamos únicamente evoluciones firmadas o con nota aclaratoria (nunca borradores)
                'histories' => function ($query) {
                    $query->whereIn('status', ['signed', 'amended'])
                          ->orderBy('created_at', 'desc');
                },
                'histories.doctor.user',
                'histories.notes.doctor.user',
                'attachments',
            ]);

            $generatedBy = Auth::user()->name . ' (' . ucfirst(Auth::user()->role) . ')';
            $generationDate = now(config('app.timezone', 'America/Bogota'))->format('d/m/Y H:i');

            $pdf = Pdf::loadView('patient.pdf.clinical_history', compact(
                'patient',
                'generatedBy',
                'generationDate'
            ))->setPaper('letter', 'portrait');

            $rawName = $patient->user?->name ?? 'Paciente';
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $rawName));
            $fileName = 'HC_' . $safeName . '_' . now(config('app.timezone', 'America/Bogota'))->format('Ymd') . '.pdf';

            return $pdf->download($fileName);
        } catch (Throwable $e) {
            Log::error("Fallo crítico al generar la Historia Clínica en PDF para el Paciente ID {$patient->id}: " . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Ocurrió un error inesperado al procesar la descarga de la historia clínica.');
        }
    }

    /**
     * Muestra las medicaciones del paciente autenticado actual.
     */
    public function indexMedication()
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
        }
        $this->ensurePatientOwnership($patient);

        $medications = $patient->medications()->orderBy('active', 'desc')->get();
        return view('patient.medications.index', compact('patient', 'medications'));
    }

    /**
     * Almacena un nuevo medicamento asociado de forma segura al paciente.
     */
    public function storeMedication(Request $request)
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            return redirect()->back()->with('error', 'No se encontró tu perfil médico en el sistema.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'active' => 'required|boolean',
        ]);

        try {
            PatientMedication::create([
                'patient_id' => $patient->id,
                'name' => strip_tags($validated['name']),
                'dosage' => strip_tags($validated['dosage']),
                'frequency' => strip_tags($validated['frequency']),
                'notes' => strip_tags($validated['notes']),
                'active' => (bool) $validated['active'],
            ]);

            return redirect()->route('patient.medications.index')
                ->with('success', 'El medicamento ha sido registrado correctamente en tu historial.');
        } catch (Throwable $e) {
            Log::error("Fallo crítico al guardar medicamento para el Paciente ID {$patient->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Ocurrió un problema interno al intentar guardar el medicamento.');
        }
    }

    /**
     * Actualiza un medicamento específico en el historial del paciente.
     */
    public function updateMedication(Request $request, int $id)
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            return redirect()->back()->with('error', 'No se encontró tu perfil médico en el sistema.');
        }

        $medication = PatientMedication::where('id', $id)->where('patient_id', $patient->id)->first();
        if (!$medication) {
            Log::warning("Intento de manipulación de medicamento ID {$id} por el Paciente ID {$patient->id}");
            abort(403, 'No tienes autorización para modificar este registro.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'active' => 'required|boolean',
        ]);

        try {
            $medication->update([
                'name' => strip_tags($validated['name']),
                'dosage' => strip_tags($validated['dosage']),
                'frequency' => strip_tags($validated['frequency']),
                'notes' => strip_tags($validated['notes']),
                'active' => (bool) $validated['active'],
            ]);

            return redirect()->route('patient.medications.index')
                ->with('success', 'El medicamento ha sido actualizado correctamente.');
        } catch (Throwable $e) {
            Log::error("Error crítico al actualizar el medicamento ID {$id} para el Paciente ID {$patient->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Ocurrió un problema interno al intentar guardar los cambios.');
        }
    }

    /**
     * Muestra los antecedentes familiares hereditarios del paciente.
     */
    public function indexFamilyHistory()
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
        }
        $this->ensurePatientOwnership($patient);

        $familyHistories = $patient->familyHistories()->latest()->get();
        return view('patient.family-history.index', compact('patient', 'familyHistories'));
    }

    public function storeFamilyHistory(Request $request)
    {
        $validated = $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'condition'    => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        // Opción A: Usando el modelo directamente
        PatientFamilyHistory::create($validated);

        // Opción B: Si tienes la relación definida en el modelo Patient
        // $patient = Patient::findOrFail($validated['patient_id']);
        // $patient->familyHistories()->create($validated);

        return back()->with('success', 'Antecedente familiar guardado correctamente.');
    }

    public function destroyFamilyHistory($id)
    {
        $history = PatientFamilyHistory::findOrFail($id);

        // Verifica que pertenezca al paciente autenticado
        $patient = auth()->user()->patient;
        $ownerId = $patient ? $patient->id : auth()->id();

        if ($history->patient_id != $ownerId) {
            abort(403, 'No tienes permiso para eliminar este registro.');
        }

        $history->delete();

        return back()->with('success', 'Antecedente familiar eliminado correctamente.');
    }


    /**
     * Muestra el historial de cirugías del paciente.
     */
    public function surgeries()
    {
        $patient = Auth::user()->patient;
        if (!$patient) {
            abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
        }
        $this->ensurePatientOwnership($patient);

        $surgeries = $patient->surgeries()->orderBy('year', 'desc')->get();
        return view('patient.surgeries.index', compact('patient', 'surgeries'));
    }

    /**
     * Reprogramación solicitada por el paciente. Toda la lógica vive en AppointmentRescheduler
     * (la misma que usan el doctor y la clínica).
     */
    public function reschedule(Request $request, Appointment $appointment, AppointmentRescheduler $rescheduler)
    {
        if ($request->has('new_start_time')) {
            $request->merge(['new_start_time' => substr((string) $request->new_start_time, 0, 5)]);
        }

        $validated = $request->validate([
            'new_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i',
        ]);

        try {
            $result = $rescheduler->reschedule($appointment, Auth::user(), $validated['new_date'], $validated['new_start_time']);
        } catch (AuthorizationException $e) {
            abort(403, 'No autorizado.');
        }

        return back()->with($result->ok ? 'success' : 'error', $result->message);
    }

    /**
     * Turnos disponibles para reprogramar esta cita (mismas reglas que se validan al guardar).
     */
    public function rescheduleSlots(Request $request, Appointment $appointment, AppointmentRescheduler $rescheduler)
    {
        $validated = $request->validate(['date' => 'required|date_format:Y-m-d|after_or_equal:today']);

        try {
            $slots = $rescheduler->slotsFor($appointment, Auth::user(), $validated['date']);
        } catch (AuthorizationException $e) {
            abort(403, 'No autorizado.');
        } catch (Throwable $e) {
            Log::error('rescheduleSlots paciente: falló el motor de disponibilidad', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo consultar la disponibilidad.'], 500);
        }

        return response()->json(\App\Services\Availability\SlotPresenter::v2($slots));
    }

    public function storeSurgery(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|digits:4|integer|min:1900|max:' . date('Y'),
            'observations' => 'nullable|string',
            'anesthesia_complications' => 'nullable|boolean',
            'anesthesia_details' => 'nullable|string',
        ]);

        // Obtiene el perfil de paciente del usuario autenticado
        $patient = auth()->user()->patient;

        if (!$patient) {
            return back()->withErrors(['error' => 'No se encontró un perfil de paciente asociado a tu cuenta.']);
        }

        PatientSurgery::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'year' => $validated['year'] ?? null,
            'observations' => $validated['observations'] ?? null,
            'anesthesia_complications' => $request->boolean('anesthesia_complications'),
            'anesthesia_details' => $request->boolean('anesthesia_complications')
                ? ($validated['anesthesia_details'] ?? null)
                : null,
        ]);

        return back()->with('success', 'Cirugía registrada correctamente.');
    }

    public function destroySurgery($id)
    {
        $surgery = PatientSurgery::findOrFail($id);

        // Verifica que la cirugía pertenezca al paciente autenticado por seguridad
        $patient = auth()->user()->patient;
        if ($patient && $surgery->patient_id !== $patient->id) {
            abort(403, 'No tienes permiso para eliminar este registro.');
        }

        $surgery->delete();

        return back()->with('success', 'Cirugía eliminada correctamente.');
    }

    public function editSurgery($id)
    {
        $surgery = PatientSurgery::findOrFail($id);

        $patient = auth()->user()->patient;
        if ($patient && $surgery->patient_id !== $patient->id) {
            abort(403, 'No tienes permiso para editar este registro.');
        }

        // Asegúrate de que el nombre de esta vista coincida con tu archivo Blade de edición
        return view('patient.surgeries.edit', compact('surgery'));
    }

    public function updateSurgery(Request $request, $id)
    {
        $surgery = PatientSurgery::findOrFail($id);

        $patient = auth()->user()->patient;
        if ($patient && $surgery->patient_id !== $patient->id) {
            abort(403, 'No tienes permiso para actualizar este registro.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|digits:4|integer|min:1900|max:' . date('Y'),
            'observations' => 'nullable|string',
            'anesthesia_complications' => 'nullable|boolean',
            'anesthesia_details' => 'nullable|string',
        ]);

        $surgery->update([
            'name' => $validated['name'],
            'year' => $validated['year'] ?? null,
            'observations' => $validated['observations'] ?? null,
            'anesthesia_complications' => $request->boolean('anesthesia_complications'),
            'anesthesia_details' => $request->boolean('anesthesia_complications')
                ? ($validated['anesthesia_details'] ?? null)
                : null,
        ]);

        // Cambia 'patient.surgeries.index' por el nombre exacto de tu ruta principal de cirugías
        return redirect()->route('patient.surgeries.index')
            ->with('success', 'Cirugía actualizada correctamente.');
    }

    public function payments(Request $request)
    {
        $user = auth()->user();
        $patientId = $user->patient ? $user->patient->id : $user->id;

        // Consulta todas las citas del paciente que tienen un valor asociado
        $query = Appointment::with(['doctor', 'service', 'clinic'])
            ->where('patient_id', $patientId)
            ->where('price', '>', 0)
            ->orderByDesc('created_at');

        // Filtro por payment_status ('pending', 'paid', 'cancelled', 'failed')
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $appointments = $query->paginate(10);

        // Total pagado real (precio menos descuento en citas con payment_status = 'paid')
        $totalPaid = Appointment::where('patient_id', $patientId)
            ->where('payment_status', 'paid')
            ->selectRaw('SUM(price - discount_amount) as total')
            ->value('total') ?? 0;

        // Cantidad de pagos pendientes
        $pendingCount = Appointment::where('patient_id', $patientId)
            ->where('payment_status', 'pending')
            ->where('price', '>', 0)
            ->count();   

        return view('patient.payments.index', compact('appointments', 'totalPaid', 'pendingCount'));
    }

    public function downloadReceipt($id)
    {
        $user = auth()->user();
        $patientId = $user->patient ? $user->patient->id : $user->id;

        $appointment = Appointment::with(['doctor', 'service', 'patient', 'clinic'])
            ->where('patient_id', $patientId)
            ->where('payment_status', 'paid')
            ->findOrFail($id);

        // Si tienes barryvdh/laravel-dompdf instalado, descomenta estas 2 líneas:
        // $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('patient.payments.receipt', compact('appointment'));
        // return $pdf->download("Comprobante-{$appointment->reference}.pdf");

        return view('patient.payments.receipt', compact('appointment'));
    }

}