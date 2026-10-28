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
use App\Services\Scheduling\SlotOverlapChecker;
use App\Events\AppointmentCancelled;
use App\Notifications\AppointmentRescheduledNotification;
use App\Notifications\AppointmentCancelledNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PatientController extends Controller
{
    /**
     * Límite máximo de reprogramaciones permitidas por cita.
     */
    protected int $maxReschedules = 2;

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

        $history = $patient->histories()->latest()->paginate(10);

        return view('patient.history.index', compact('patient', 'history'));
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
    public function appointments()
    {
        $user = auth()->user();
        $patient = $user->patient;

        if (!$patient) {
            return redirect()->route('profile.show')->with('error', 'No se encontró un perfil de paciente asociado a tu cuenta.');
        }

        $now = Carbon::now('America/Bogota');
        
        // ✅ CORRECCIÓN: Usar el helper request() en lugar de $user->input()
        $statusFilter = request('status');

        // 1. PRÓXIMAS CONSULTAS: Citas futuras o en curso
        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '>', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('date', $now->toDateString())
                        ->where('end_time', '>', $now->toTimeString());
                    });
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->when($statusFilter, function ($query) use ($statusFilter) {
                return $query->where('status', $statusFilter);
            })
            ->with(['doctor.user', 'clinic', 'service', 'address.city'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. HISTORIAL DE CONSULTAS: Citas pasadas o cerradas
        $pastAppointments = Appointment::where('patient_id', $patient->id)
            ->where(function ($query) use ($now) {
                $query->where(function ($sub) use ($now) {
                    $sub->whereDate('date', '<', $now->toDateString())
                        ->orWhere(function ($q) use ($now) {
                            $q->whereDate('date', $now->toDateString())
                            ->where('end_time', '<=', $now->toTimeString());
                        });
                })->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->with(['doctor.user', 'clinic', 'service', 'address.city'])
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

        $now = Carbon::now('America/Bogota');
        $appointmentDateTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time, 'America/Bogota');

        if ($now->greaterThanOrEqualTo($appointmentDateTime)) {
            return redirect()->back()->with('error', 'No es posible cancelar una consulta médica que ya se encuentra en desarrollo o que pertenece al pasado.');
        }

        $settings = $appointment->clinic_id ? $appointment->clinic?->settings : $appointment->doctor?->settings;
        $cancellationNoticeHours = $settings ? (int) $settings->cancellation_notice_hours : 2;
        $hoursDifference = $now->diffInHours($appointmentDateTime, false);

        if ($hoursDifference < $cancellationNoticeHours) {
            return redirect()->back()->with('error', "Las políticas de este centro médico o especialista exigen un mínimo de {$cancellationNoticeHours} horas de anticipación para cancelar el turno.");
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:255'
        ]);

        $reason = $request->filled('cancellation_reason')
            ? 'Cancelado por el paciente. Motivo: ' . trim($request->cancellation_reason)
            : 'Cancelado de forma autónoma por el paciente desde la plataforma web.';

        $appointment->update([
            'status' => 'cancelled',
            'notes'  => ($appointment->notes ? $appointment->notes . "\n" : '') . "[Auditoría: {$reason}]"
        ]);

        event(new AppointmentCancelled($appointment));

        $doctor = Doctor::findOrFail($appointment->doctor_id);
        $userToNotify = User::find($doctor->user_id);
        if ($userToNotify) {
            $userToNotify->notify(new AppointmentCancelledNotification($appointment, 'patient'));
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
                'allergies',
                'surgeries',
                'familyHistories',
                'medications',
                'histories.doctor.user',
                'attachments'
            ]);

            $generatedBy = Auth::user()->name . " (" . ucfirst(Auth::user()->role) . ")";
            $generationDate = now()->format('d/m/Y H:i');

            $pdf = Pdf::loadView('dashboard.patients.reports.clinical_history', compact(
                'patient',
                'generatedBy',
                'generationDate'
            ))->setPaper('letter', 'portrait');

            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $patient->name));
            $fileName = 'HC_' . $safeName . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->download($fileName);
        } catch (Throwable $e) {
            Log::error("Fallo crítico al generar la Historia Clínica en PDF para el Paciente ID {$patient->id}: " . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
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
        return view('dashboard.patients.family_histories.index', compact('patient', 'familyHistories'));
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
        return view('dashboard.patients.surgeries.index', compact('patient', 'surgeries'));
    }

    /**
     * Procesa el reagendamiento estricto solicitado desde el dashboard del paciente.
     * 🔒 REFACTORIZADO: Usa SlotOverlapChecker para consistencia con el resto del sistema.
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $patient = Auth::user()->patient;
        if (!$patient || $appointment->patient_id !== $patient->id) {
            abort(403, 'No autorizado.');
        }

        $rescheduleCount = $appointment->reschedule_count ?? 0;
        if ($rescheduleCount >= $this->maxReschedules) {
            return back()->with('error', 'Has alcanzado el límite máximo de reprogramaciones permitidas para esta cita. Por favor, contacta con soporte.');
        }

        $settings = $appointment->clinic_id
            ? ClinicSetting::where('clinic_id', $appointment->clinic_id)->first()
            : DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();

        if (!$settings) {
            return back()->with('error', 'La configuración de la agenda médica no está disponible.');
        }

        if (!$settings->allow_patient_rescheduling) {
            return back()->with('error', 'Este profesional o centro médico no permite reprogramar citas en línea.');
        }

        $cleanDate = Carbon::parse($appointment->date)->format('Y-m-d');
        $cleanTime = Carbon::parse($appointment->start_time)->format('H:i:s');
        $appointmentDateTime = Carbon::parse($cleanDate . ' ' . $cleanTime, 'America/Bogota');

        if (now('America/Bogota')->diffInHours($appointmentDateTime, false) < $settings->cancellation_notice_hours) {
            return back()->with('error', "Debes reprogramar con al menos {$settings->cancellation_notice_hours} horas de anticipación.");
        }

        if ($request->has('new_start_time')) {
            $request->merge(['new_start_time' => substr($request->new_start_time, 0, 5)]);
        }

        $validated = $request->validate([
            'new_date' => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i',
        ]);

        $newDate = $validated['new_date'];
        $newStartTime = Carbon::parse($validated['new_start_time'])->format('H:i:s');
        $newEndTime = Carbon::parse($newStartTime)->addMinutes($appointment->duration)->format('H:i:s');
        $endTimeWithBuffer = Carbon::parse($newEndTime)->addMinutes($settings->buffer_time_minutes)->format('H:i:s');

        $maxDateAllowed = Carbon::today('America/Bogota')->addDays($settings->max_advance_days);
        if (Carbon::parse($newDate)->greaterThan($maxDateAllowed)) {
            return back()->with('error', "Solo se permiten reprogramaciones hasta el " . $maxDateAllowed->format('d/m/Y'));
        }

        $dayOfWeekIso = Carbon::parse($newDate)->dayOfWeekIso;

        // 🔒 1. Validar horario de atención semanal usando SlotOverlapChecker
        $hasSchedule = Schedule::where('address_id', $appointment->address_id)
            ->where('day', $dayOfWeekIso)
            ->where('is_active', true)
            ->where(function ($query) use ($newStartTime, $newEndTime) {
                // Fórmula universal: (StartA < EndB) AND (EndA > StartB)
                $query->where('start_time', '<', $newEndTime)
                      ->where('end_time', '>', $newStartTime);
            })
            ->exists();

        if (!$hasSchedule) {
            return back()->with('error', 'El médico no atiende en esta sede en el día u horario seleccionado.');
        }

        // 🔒 2. Validar inasistencias o bloqueos temporales usando SlotOverlapChecker
        $isUnavailable = SlotOverlapChecker::overlapsWithUnavailabilities(
            doctorId: $appointment->doctor_id,
            date: $newDate,
            newStart: $newStartTime,
            newEnd: $endTimeWithBuffer
        );

        if ($isUnavailable) {
            return back()->with('error', 'El médico no estará disponible en la fecha seleccionada por motivos de agenda.');
        }

        // 🔒 3. Prevenir Double-Booking usando SlotOverlapChecker
        $isSlotOccupied = SlotOverlapChecker::overlapsWithAppointments(
            doctorId: $appointment->doctor_id,
            date: $newDate,
            newStart: $newStartTime,
            newEnd: $endTimeWithBuffer,
            excludeAppointmentId: $appointment->id,
            statuses: ['pending', 'confirmed']
        );

        if ($isSlotOccupied) {
            return back()->with('error', 'El horario seleccionado o su tiempo de descanso ya se encuentra reservado.');
        }

        // 🔒 4. Ejecución de la transacción atómica
        DB::transaction(function () use ($appointment, $newDate, $newStartTime, $newEndTime, $settings, $rescheduleCount) {
            $appointment->update([
                'date' => $newDate,
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'status' => $settings->requires_approval ? 'pending' : 'confirmed',
                'email_sent' => false,
                'reschedule_count' => $rescheduleCount + 1,
            ]);
        });

        $message = $settings->requires_approval
            ? 'Tu solicitud de cambio fue enviada y está sujeta a la aprobación de la clínica.'
            : 'Tu cita ha sido reprogramada exitosamente.';

        $doctor = Doctor::findOrFail($appointment->doctor_id);
        $userToNotify = User::find($doctor->user_id);
        if ($userToNotify) {
            $userToNotify->notify(new AppointmentRescheduledNotification($appointment, 'patient'));
        }

        return back()->with('success', $message);
    }
}