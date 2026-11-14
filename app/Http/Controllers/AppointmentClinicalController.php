<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PatientHistory;
use App\Models\PatientHistoryNote;
use App\Models\Cie10Code;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentClinicalController extends Controller
{
    /**
     * Valida que el usuario autenticado sea el médico que atendió exactamente esta cita.
     */
    private function authorizeDoctorOwnership(Appointment $appointment): void
    {
        $user = Auth::user();

        if ($user->role !== 'doctor' || !$user->doctor || $appointment->doctor_id !== $user->doctor->id) {
            abort(403, 'Solo el especialista médico asignado a esta cita puede gestionar su historia clínica.');
        }

        if (!$appointment->canAccessClinicalRecord()) {
            abort(403, 'La historia clínica solo se habilita a partir de la hora de inicio de la cita.');
        }
    }

    /**
     * Endpoint JSON (Fetch) para el popup centrado en "Mis Citas" (Paciente y Doctor).
     */
    public function fetchNotes(Appointment $appointment): JsonResponse
    {
        $user = Auth::user();
        $isDoctorOwner = $user->role === 'doctor' && $user->doctor?->id === $appointment->doctor_id;
        $isPatientOwner = $user->patient?->id === $appointment->patient_id;

        if (!$isDoctorOwner && !$isPatientOwner) {
            return response()->json(['message' => 'No autorizado para consultar estas notas clínicas.'], 403);
        }

        $history = PatientHistory::with(['notes.doctor.user', 'doctor.user'])
            ->where('appointment_id', $appointment->id)
            ->first();

        // Si no existe historia o el paciente intenta ver un borrador aún sin firmar
        if (!$history || ($isPatientOwner && !in_array($history->status, ['signed', 'amended'], true))) {
            return response()->json([
                'has_history' => false,
                'message' => 'Aún no hay notas médicas firmadas disponibles para esta consulta.',
            ]);
        }

        return response()->json([
            'has_history' => true,
            'reference'   => $appointment->reference,
            'date'        => $appointment->starts_at->format('d/m/Y g:i A'),
            'doctor_name' => $history->doctor?->user?->name ?? 'Profesional Médico',
            'status'      => $history->status,
            'cie10_code'  => $history->cie10_code,
            'soap'        => [
                'subjective' => $history->soap_subjective,
                'objective'  => $history->soap_objective,
                'assessment' => $history->soap_assessment,
                'plan'       => $history->soap_plan,
            ],
            'notes' => $history->notes->map(fn ($note) => [
                'id'         => $note->id,
                'content'    => $note->content,
                'note_type'  => $note->note_type,
                'created_at' => $note->created_at->format('d/m/Y g:i A'),
                'doctor'     => $note->doctor?->user?->name ?? 'Profesional Médico',
            ]),
        ]);
    }

    /**
     * Muestra la vista dedicada de historia clínica de la cita (SOAP o Notas Extemporáneas).
     */
    public function show(Appointment $appointment): View
    {
        $this->authorizeDoctorOwnership($appointment);

        $appointment->load([
            'patient.user',
            'patient.allergies',
            'patient.medications',
            'patient.surgeries',
            'patient.familyHistories',
            'patient.attachments',
            'service',
        ]);

        // Buscamos si ya existe una historia para esta cita (ej. iniciada en room.blade.php)
        $history = PatientHistory::with('notes.doctor.user')
            ->where('appointment_id', $appointment->id)
            ->first();

        // Si estamos dentro de las 3 horas y aún no existe el registro, lo inicializamos en borrador
        if (!$history && $appointment->isWithinSoapWindow()) {
            $history = PatientHistory::create([
                'patient_id'     => $appointment->patient_id,
                'doctor_id'      => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'entry_type'     => 'consultation',
                'status'         => 'draft',
            ]);
        }

        $isWithinSoapWindow = $appointment->isWithinSoapWindow();
        $notesCount = $history ? $history->notes->count() : 0;
        $maxNotes = PatientHistoryNote::MAX_NOTES_PER_HISTORY;

        return view('partner.appointments.clinical-record', compact(
            'appointment',
            'history',
            'isWithinSoapWindow',
            'notesCount',
            'maxNotes'
        ));
    }

        /**
     * Guarda borrador o firma definitivamente el formulario SOAP dentro de las primeras 3 horas.
     * Soporta tanto formularios tradicionales como peticiones AJAX (fetch) desde room.blade.php.
     */
    public function storeSoap(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorizeDoctorOwnership($appointment);

        if (!$appointment->isWithinSoapWindow()) {
            $errorMsg = 'La ventana de 3 horas para editar el formulario SOAP ha finalizado o la historia ya fue firmada.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $errorMsg], 422)
                : back()->with('error', $errorMsg);
        }

        $validated = $request->validate([
            'entry_type'      => ['required', 'in:consultation,follow_up,emergency,note'],
            'cie10_code'      => ['nullable', 'string', 'max:255'],
            'soap_subjective' => ['nullable', 'string'],
            'soap_objective'  => ['nullable', 'string'],
            'soap_assessment' => ['required', 'string'],
            'soap_plan'       => ['nullable', 'string'],
            'ai_assisted'     => ['nullable', 'boolean'],
            'action'          => ['required', 'in:draft,sign'],
        ]);

        $newStatus = $validated['action'] === 'sign' ? 'signed' : 'draft';

        $history = PatientHistory::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id'      => $appointment->patient_id,
                'doctor_id'       => $appointment->doctor_id,
                'entry_type'      => $validated['entry_type'],
                'cie10_code'      => $validated['cie10_code'] ?? null,
                'soap_subjective' => $validated['soap_subjective'] ?? null,
                'soap_objective'  => $validated['soap_objective'] ?? null,
                'soap_assessment' => $validated['soap_assessment'],
                'soap_plan'       => $validated['soap_plan'] ?? null,
                'ai_assisted'     => (bool) ($validated['ai_assisted'] ?? false),
                'status'          => $newStatus,
            ]
        );

        $message = $newStatus === 'signed'
            ? 'Historia clínica firmada y cerrada oficialmente.'
            : 'Borrador guardado correctamente. Puedes completarlo hasta 3 horas después de iniciar la cita.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $history->status,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Agrega una nota extemporánea o aclaratoria individual (máximo 3 por cita).
     */
    public function storeExtemporaneousNote(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeDoctorOwnership($appointment);

        if (!$appointment->isExtemporaneousMode()) {
            return back()->with('error', 'Aún estás dentro del tiempo habilitado para diligenciar el formulario SOAP principal.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:4000'],
        ]);

        DB::transaction(function () use ($appointment, $validated) {
            // Si el doctor nunca alcanzó a abrir la historia en las primeras 3 horas, la creamos cerrada
            $history = PatientHistory::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'patient_id'      => $appointment->patient_id,
                    'doctor_id'       => $appointment->doctor_id,
                    'entry_type'      => 'consultation',
                    'status'          => 'amended',
                    'soap_subjective' => 'Atención documentada mediante nota extemporánea posterior al cierre de la ventana principal.',
                ]
            );

            // Verificamos el límite estricto de 3 notas individuales
            if ($history->notes()->count() >= PatientHistoryNote::MAX_NOTES_PER_HISTORY) {
                abort(422, 'Has alcanzado el límite máximo de 3 notas extemporáneas para esta cita.');
            }

            $noteType = $history->status === 'signed' ? 'amendment' : 'extemporaneous';

            PatientHistoryNote::create([
                'patient_history_id' => $history->id,
                'doctor_id'          => $appointment->doctor_id,
                'content'            => trim($validated['content']),
                'note_type'          => $noteType,
            ]);

            // Si estaba firmada, marcamos que ahora cuenta con notas aclaratorias
            if ($history->status === 'signed') {
                $history->update(['status' => 'amended']);
            }
        });

        return back()->with('success', 'Nota extra registrada correctamente en la historia clínica de la cita.');
    }

        /**
     * Busca códigos y descripciones CIE-10 para el componente autocompletable.
     */
    public function searchCie10(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        // Exigimos al menos 2 caracteres para no sobrecargar la base de datos
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $results = Cie10Code::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('code', 'LIKE', "{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderByRaw('CASE WHEN code LIKE ? THEN 0 ELSE 1 END', ["{$query}%"])
            ->orderBy('code', 'asc')
            ->limit(15)
            ->get(['id', 'code', 'description'])
            ->map(fn ($item) => [
                'id'          => $item->id,
                'code'        => $item->code,
                'description' => $item->description,
                'full_label'  => "{$item->code} - {$item->description}",
            ]);

        return response()->json($results);
    }
}
