<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\User;
use App\Models\Doctor;
use App\Models\PatientAllergy;
use App\Models\Appointment;
use App\Models\PatientSurgery;
use App\Models\PatientHistory;
use App\Models\PatientFamilyHistory;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\DoctorSetting;
use App\Models\PatientMedication;
use App\Models\Insurance;
use App\Models\Department;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Carbon\Carbon; 
use App\Events\AppointmentCancelled;
use App\Notifications\AppointmentRescheduledNotification;
use App\Notifications\AppointmentCancelledNotification;

class PatientController extends Controller
{
    use AuthorizesRequests;

    // Límite máximo de reprogramaciones permitidas
    protected $maxReschedules = 2;

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
     * Muestra el perfil de historial clínico para que el paciente lo actualice.
     * Carga las relaciones declaradas en el modelo Patient de opendoctor.online.
     */
    public function history(Request $request)
    {
        // 1. Obtenemos el registro del paciente autenticado
        $patient = $request->user()->patient()
        ->with(['attachments', 'histories']) // Cargamos la relación indexada
        ->firstOrFail();

        // 2. Traemos su historial médico usando la relación del modelo (ordenada por las más recientes)
        $history = $patient->histories()->paginate(10);
        

        // 3. Pasamos ambas variables a la vista para que no falte ninguna
        return view('patient.history.index', compact('patient', 'history'));
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
     * Muestra el listado completo de citas médicas del paciente autenticado (Soporte Híbrido).
     */
    public function appointments()
    {        
        $user = auth()->user();
        
        // Asegurar que el usuario tenga asignado el rol correspondiente en Spatie
        if (!$user->hasRole('patient')) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'patient']);
            $user->assignRole('patient');
        }
        
        $patient = $user->patient;

        if (!$patient) {
            return redirect()->route('profile.show')->with('error', 'No se encontró un perfil de paciente asociado a tu cuenta.');
        }

        $now = Carbon::now('America/Bogota'); 
        $statusFilter = request('status');

        // 1. 🔮 PRÓXIMAS CONSULTAS: Citas futuras estrictamente activas (confirmed o pending)
        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->where(function($query) use ($now) {
                $query->whereDate('date', '>', $now->toDateString())
                    ->orWhere(function($q) use ($now) {
                        $q->whereDate('date', $now->toDateString())
                          ->where('start_time', '>=', $now->toTimeString());
                    });
            })
            // 🔒 BLINDAJE ANTI-DUPLICADOS: Excluimos citas canceladas o completadas aunque tengan fecha futura
            ->whereIn('status', ['confirmed', 'pending'])
            ->when($statusFilter, function($query) use ($statusFilter) {
                return $query->where('status', $statusFilter);
            })
            // Cargamos la relación 'clinic' para evitar consultas lentas N+1 en la tarjeta
            ->with(['doctor.user', 'clinic', 'service', 'address.city'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. 📜 HISTORIAL DE CONSULTAS: Todo lo que ya expiró en tiempo O que explícitamente se cerró
        $pastAppointments = Appointment::where('patient_id', $patient->id)
            ->where(function($query) use ($now) {
                // Caso A: La fecha ya pasó cronológicamente
                $query->where(function($sub) use ($now) {
                    $sub->whereDate('date', '<', $now->toDateString())
                        ->orWhere(function($q) use ($now) {
                            $q->whereDate('date', $now->toDateString())
                              ->where('start_time', '<', $now->toTimeString());
                        });
                })
                // Caso B: Citas explícitamente terminadas (independientemente de su fecha)
                ->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->with(['doctor.user', 'clinic', 'service', 'address.city'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        $maxReschedules = $this->maxReschedules;
    
        return view('patient.appointments.index', compact('upcomingAppointments', 'pastAppointments', 'maxReschedules'));
    }
    
    /**
     * Procesa la cancelación de una consulta médica solicitada por el paciente (Seguridad Tenant & Políticas de Tiempo).
     */
    public function cancelWeb(Request $request, $id)
    {
        // 1. Extraer de forma segura el perfil del paciente logueado
        $patient = auth()->user()->patient;
        if (!$patient) {
            abort(403, 'Perfil de paciente no encontrado en el sistema.');
        }

        // 2. Buscar la cita asegurando que pertenezca estrictamente a este paciente (Seguridad Inversa IDOR)
        $appointment = Appointment::with(['doctor.settings', 'clinic.settings'])
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        // 3. 🛡️ CONTROL POLÍTICO DE TIEMPO: Validar si la cita ya expiró o si viola las horas de aviso previo
        $now = Carbon::now('America/Bogota');

        $appointmentDate = Carbon::parse($appointment->date)->format('Y-m-d');
        $appointmentDateTime = Carbon::parse($appointmentDate . ' ' . $appointment->start_time, 'America/Bogota');

        if ($now->greaterThanOrEqualTo($appointmentDateTime)) {
            return redirect()->back()->with('error', 'No es posible cancelar una consulta médica que ya se encuentra en desarrollo o que pertenece al pasado.');
        }

        // Resolver las horas mínimas de aviso previo configuradas por el proveedor (Clínica o Particular)
        $settings = $appointment->clinic_id ? $appointment->clinic?->settings : $appointment->doctor?->settings;
        $cancellationNoticeHours = $settings ? (int) $settings->cancellation_notice_hours : 2; // 2 horas por defecto en el SaaS

        // Calcular la diferencia en horas entre este instante y el bloque de atención médica
        $hoursDifference = $now->diffInHours($appointmentDateTime, false);

        if ($hoursDifference < $cancellationNoticeHours) {
            return redirect()->back()->with('error', "Las políticas de este centro médico o especialista exigen un mínimo de {$cancellationNoticeHours} horas de anticipación para cancelar el turno. Por favor, comunícate con soporte.");
        }

        // 4. Capturar de forma limpia el rastro de auditoría e inyectar el motivo
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:255'
        ]);

        $reason = $request->filled('cancellation_reason') 
            ? 'Cancelado por el paciente. Motivo: ' . trim($request->cancellation_reason)
            : 'Cancelado de forma autónoma por el paciente desde la plataforma web.';

        // 5. Actualizar la cita y liberar de forma paralela la pasarela y la franja de horarios
        $appointment->update([
            'status' => 'cancelled',
            'notes'  => $appointment->notes . "\n\n[Auditoría: " . $reason . "]"
        ]);

        // 8. 🔥 DISPARAR EL EVENTO ASÍNCRONO 🔥
        // El core de Laravel retendrá el evento un instante y lo despachará a Redis/Database 
        // tan pronto como la transacción de la base de datos haga el COMMIT exitoso.
        event(new AppointmentCancelled($appointment));

        // Si el paciente cambió la cita, notificamos al MÉDICO
        // Buscamos el médico por su ID, y luego extraemos su user_id de autenticación
        $doctor = Doctor::findOrFail($appointment->doctor_id);
        $userToNotify = User::find($doctor->user_id);

        // Enviamos la notificación al usuario de autenticación correcto
        if ($userToNotify) {
            $userToNotify->notify(new AppointmentCancelledNotification($appointment, 'patient'));
        }

        return redirect()->route('patient.appointments.index')
            ->with('success', 'La consulta médica ha sido cancelada exitosamente y el espacio horario ha sido devuelto a la disponibilidad pública.');
    }

    /**
     * Genera y descarga el reporte consolidado e indexado de la Historia Clínica en PDF.
     */
    public function downloadClinicalHistory(Patient $patient)
    {
        // 1. Control de accesos Multi-tenant (Spatie / Esquema de co-propiedad)
        $user = Auth::user();
        
        // Bloqueo preventivo de seguridad: Validar que el actor pertenezca al ecosistema
        if (!$user->hasRole(['patient'])) {
            abort(403, 'No tienes autorización para acceder a expedientes médicos.');
        }

        try {
            // 2. CARGA COMPLETA Y OPTIMIZADA DE LAS 6 TABLAS (Evita el problema N+1)
            $patient->load([
                'allergies',
                'surgeries',
                'familyHistories',
                'medications',
                'histories.doctor.user',
                'attachments'
            ]);

            // 3. Variables de auditoría para validez legal e institucional del documento
            $generatedBy = $user->name . " (" . ucfirst($user->role) . ")";
            $generationDate = now()->format('d/m/Y H:i');

            // 4. Compilar y renderizar la plantilla HTML a memoria interna
            $pdf = Pdf::loadView('dashboard.patients.reports.clinical_history', compact(
                'patient', 
                'generatedBy', 
                'generationDate'
            ));

            // 5. Configuración del papel (Carta, Vertical)
            $pdf->setPaper('letter', 'portrait');

            // 6. Nombre de archivo estandarizado, libre de espacios o caracteres especiales
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $patient->name));
            $fileName = 'HC_' . $safeName . '_' . now()->format('Ymd') . '.pdf';

            // 7. Despacho y descarga directa en el navegador
            return $pdf->download($fileName);

        } catch (Throwable $e) {
            Log::error("Fallo crítico al generar la Historia Clínica en PDF para el Paciente ID {$patient->id}: " . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return redirect()->back()->with('error', __('Ocurrió un error inesperado al procesar la descarga de la historia clínica.'));
        }
    }

    /**
     * Muestra las medicaciones del paciente autenticado actual.
     */
    public function indexMedication()
    {
        // 1. Obtener el usuario de la sesión actual
        $user = auth()->user();

        // 2. Control de accesos: Asegurar que el usuario logueado sea un paciente
        if ($user->role !== 'patient') {
            abort(403, 'Este portal es exclusivo para pacientes.');
        }        

        try {
            // 3. Obtener el perfil del paciente asociado a ese usuario
            $patient = $user->patient;
            
            if (!$patient) {
                abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
            }
            
            // 4. Carga optimizada de sus medicamentos (activos primero)
            $medications = $patient->medications()->orderBy('active', 'desc')->get();

            // 5. Retornamos la vista pasando al paciente y sus medicinas
            return view('patient.medications.index', compact('patient', 'medications'));

        } catch (Throwable $e) {
            Log::error("Error en indexMedication del portal del paciente: " . $e->getMessage());
            return redirect()->back()->with('error', __('No se pudieron cargar tus medicamentos.'));
        }
    }

    /**
     * Almacena un nuevo medicamento asociado de forma segura al paciente autenticado.
     */
    public function storeMedication(Request $request)
    {
        // 1. Control de accesos: Asegurar que quien guarda sea un paciente legítimo
        $user = Auth::user();
        if ($user->role !== 'patient') {
            abort(403, 'Operación no autorizada para este perfil de usuario.');
        }

        // 2. Extraer el perfil clínico del paciente desde la sesión
        $patient = $user->patient;
        if (!$patient) {
            return redirect()->back()->with('error', __('No se encontró tu perfil médico en el sistema.'));
        }

        // 3. Validar minuciosamente los datos recibidos del formulario
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500', // Evitamos textos excesivos
            'active' => 'required|boolean',
        ]);

        try {
            // 4. Crear el registro farmacológico inyectando el patient_id de forma interna
            PatientMedication::create([
                'patient_id' => $patient->id,
                'name' => strip_tags($validated['name']), // Sanitización básica contra XSS
                'dosage' => strip_tags($validated['dosage']),
                'frequency' => strip_tags($validated['frequency']),
                'notes' => strip_tags($validated['notes']),
                'active' => $validated['active'],
            ]);

            // 5. Redirección con mensaje de éxito estandarizado
            return redirect()->route('patient.medications.index')
                ->with('success', __('El medicamento ha sido registrado correctamente en tu historial.'));

        } catch (Throwable $e) {
            // Registro silencioso del fallo para el equipo de desarrollo
            Log::error("Fallo crítico al guardar medicamento para el Paciente ID {$patient->id}: " . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', __('Ocurrió un problema interno al intentar guardar el medicamento. Por favor, reintenta.'));
        }
    }

    /**
     * Actualiza un medicamento específico en el historial del paciente autenticado.
     */
    public function updateMedication(Request $request, $id)
    {
        // 1. Control de accesos: Validar perfil de paciente
        $user = Auth::user();
        if ($user->role !== 'patient') {
            abort(403, 'Operación no autorizada para este perfil de usuario.');
        }

        // 2. Extraer el perfil del paciente logueado
        $patient = $user->patient;
        if (!$patient) {
            return redirect()->back()->with('error', __('No se encontró tu perfil médico en el sistema.'));
        }

        // 3. Buscar el medicamento y validar propiedad estricta (Multi-tenancy por sesión)
        $medication = PatientMedication::where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$medication) {
            Log::warning("Intento de manipulación de medicamento ID {$id} por el Paciente ID {$patient->id}");
            abort(403, 'No tienes autorización para modificar este registro.');
        }

        // 4. Validar minuciosamente los datos recibidos del formulario
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'active' => 'required|boolean',
        ]);

        try {
            // 5. Actualizar el registro sanitizando las cadenas de texto
            $medication->update([
                'name' => strip_tags($validated['name']),
                'dosage' => strip_tags($validated['dosage']),
                'frequency' => strip_tags($validated['frequency']),
                'notes' => strip_tags($validated['notes']),
                'active' => $validated['active'],
            ]);

            // 6. Redirección con mensaje de éxito estandarizado
            return redirect()->route('patient.medications.index')
                ->with('success', __('El medicamento ha sido actualizado correctamente.'));

        } catch (Throwable $e) {
            Log::error("Error crítico al actualizar el medicamento ID {$id} para el Paciente ID {$patient->id}: " . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', __('Ocurrió un problema interno al intentar guardar los cambios.'));
        }
    }

    /**
     * Muestra los antecedentes familiares hereditarios del paciente autenticado actual.
     */
    public function indexFamilyHistory()
    {
        // 1. Control de accesos: Asegurar que quien consulta sea un paciente legítimo
        $user = Auth::user();
        if ($user->role !== 'patient') {
            abort(403, 'Este portal es exclusivo para el perfil de paciente.');
        }

        try {
            // 2. Extraer el perfil clínico del paciente desde la sesión de co-propiedad
            $patient = $user->patient;

            if (!$patient) {
                abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
            }

            // 3. Carga optimizada de sus antecedentes familiares ordenados cronológicamente
            $familyHistories = $patient->familyHistories()->latest()->get();

            // 4. Retornamos la vista pasando al paciente y sus antecedentes familiares
            return view('dashboard.patients.family_histories.index', compact('patient', 'familyHistories'));

        } catch (Throwable $e) {
            Log::error("Error crítico en indexFamilyHistory del portal del paciente: " . $e->getMessage());
            
            return redirect()->back()->with('error', __('No se pudieron cargar tus antecedentes familiares.'));
        }
    }

    /**
     * Muestra el historial de cirugías del paciente autenticado actual.
     */
    public function surgeries()
    {
        // 1. Control de accesos: Asegurar que quien consulta sea un paciente legítimo
        $user = Auth::user();
        if ($user->role !== 'patient') {
            abort(403, 'Este portal es exclusivo para el perfil de paciente.');
        }

        try {
            // 2. Extraer el perfil clínico del paciente desde la sesión de co-propiedad
            $patient = $user->patient;

            if (!$patient) {
                abort(404, 'No se encontró un perfil médico asociado a tu cuenta.');
            }

            // 3. Carga optimizada de sus antecedentes quirúrgicos (Cirugías más recientes primero)
            $surgeries = $patient->surgeries()->orderBy('year', 'desc')->get();

            // 4. Retornamos la vista pasando al paciente y sus cirugías
            return view('dashboard.patients.surgeries.index', compact('patient', 'surgeries'));

        } catch (Throwable $e) {
            Log::error("Error crítico en el método surgeries del portal del paciente: " . $e->getMessage());
            
            return redirect()->back()->with('error', __('No se pudo cargar tu historial de cirugías.'));
        }
    }

    /**
     * Procesa el reagendamiento estricto solicitado desde el dashboard del paciente.
     */
    public function reschedule(Request $request, Appointment $appointment)
    {        
        // 1. Validar que la cita pertenezca al paciente autenticado
        if ($appointment->patient_id !== auth()->user()->patient->id) {
            abort(403, 'No autorizado.');
        }
        
        $rescheduleCount = $appointment->reschedule_count ?? 0;    
        if ($rescheduleCount >= $this->maxReschedules) {
            return back()->with('error', 'Has alcanzado el límite máximo de reprogramaciones permitidas para esta cita. Por favor, contacta con soporte para asistencia.');
        }

        // 2. Cargar configuración dinámica según el Tenant (Clínica o Doctor Independiente)
        $settings = $appointment->clinic_id 
            ? ClinicSetting::where('clinic_id', $appointment->clinic_id)->first()
            : DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();

        if (!$settings) {
            return back()->with('error', 'La configuración de la agenda médica no está disponible.');
        }

        // 3. Regla de Negocio: Verificar si el tenant permite cambios autónomos
        if (!$settings->allow_patient_rescheduling) {
            return back()->with('error', 'Este profesional o centro médico no permite reprogramar citas en línea.');
        }

        // 4. Regla de Negocio: Validar anticipación mínima exigida (cancellation_notice_hours)
        // Extraemos solo la fecha (YYYY-MM-DD) para evitar que arrastre los ceros de la hora de la base de datos
        $cleanDate = Carbon::parse($appointment->date)->format('Y-m-d');
        $cleanTime = Carbon::parse($appointment->start_time)->format('H:i:s');
        
        $appointmentDateTime = Carbon::parse($cleanDate . ' ' . $cleanTime, 'America/Bogota');
        
        if (now('America/Bogota')->diffInHours($appointmentDateTime, false) < $settings->cancellation_notice_hours) {
            return back()->with('error', "Debes reprogramar con al menos {$settings->cancellation_notice_hours} horas de anticipación.");
        }

        if ($request->has('new_start_time')) {
            // Corta los caracteres para dejar solo "11:00"
            $timeWithoutSeconds = substr($request->new_start_time, 0, 5); 
            $request->merge(['new_start_time' => $timeWithoutSeconds]);
        }

        // 5. Validar inputs requeridos del formulario modal
        $validated = $request->validate([
            'new_date' => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i',
        ]);

        $newDate = $validated['new_date'];
        $newStartTime = Carbon::parse($validated['new_start_time'])->format('H:i:s');

        // 6. Regla de Negocio: Validar límite máximo de días hacia el futuro (max_advance_days)
        $maxDateAllowed = Carbon::today('America/Bogota')->addDays($settings->max_advance_days);
        if (Carbon::parse($newDate)->greaterThan($maxDateAllowed)) {
            return back()->with('error', "Solo se permiten reprogramaciones hasta el " . $maxDateAllowed->format('d/m/Y'));
        }

        // 7. Calcular nueva hora de finalización usando tu columna nativa 'duration'
        $newEndTime = Carbon::parse($newStartTime)->addMinutes($appointment->duration)->format('H:i:s');
        
        // Extender rango con el buffer médico para la validación de colisiones
        $endTimeWithBuffer = Carbon::parse($newEndTime)->addMinutes($settings->buffer_time_minutes)->format('H:i:s');

        // 8. Validación 1: Verificar el horario de atención semanal (`schedules`)
        $dayOfWeekIso = Carbon::parse($newDate)->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)
        $hasSchedule = Schedule::where('address_id', $appointment->address_id)
            ->where('day', $dayOfWeekIso)
            ->where('start_time', '<=', $newStartTime)
            ->where('end_time', '>=', $newEndTime)
            ->exists();

        if (!$hasSchedule) {
            return back()->with('error', 'El médico no atiende en esta sede en el día u horario seleccionado.');
        }

        // 9. Validación 2: Verificar inasistencias o bloqueos temporales (`unavailabilities`)
        $isUnavailable = Unavailability::where('doctor_id', $appointment->doctor_id)
            ->where(function ($query) use ($appointment) {
                $query->whereNull('address_id')->orWhere('address_id', $appointment->address_id);
            })
            ->where('start_date', '<=', $newDate)
            ->where('end_date', '>=', $newDate)
            ->where(function ($query) use ($newStartTime, $newEndTime) {
                $query->whereNull('start_time')
                    ->orWhere(function ($q) use ($newStartTime, $newEndTime) {
                        $q->where('start_time', '<', $newEndTime)->where('end_time', '>', $newStartTime);
                    });
            })
            ->exists();

        if ($isUnavailable) {
            return back()->with('error', 'El médico no estará disponible en la fecha seleccionada por motivos de agenda.');
        }

        // 10. Validación 3: Prevenir Double-Booking usando tu índice compuesto optimizado
        $isSlotOccupied = Appointment::where('doctor_id', $appointment->doctor_id)
            ->where('date', $newDate)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($newStartTime, $endTimeWithBuffer) {
                $query->where(function ($q) use ($newStartTime, $endTimeWithBuffer) {
                    $q->where('start_time', '<=', $newStartTime)->where('end_time', '>', $newStartTime);
                })->orWhere(function ($q) use ($newStartTime, $endTimeWithBuffer) {
                    $q->where('start_time', '<', $endTimeWithBuffer)->where('end_time', '>=', $endTimeWithBuffer);
                });
            })
            ->exists();

        if ($isSlotOccupied) {
            return back()->with('error', 'El horario seleccionado o su tiempo de descanso ya se encuentra reservado.');
        }

        // 11. Ejecución de la transacción atómica
        DB::transaction(function () use ($appointment, $newDate, $newStartTime, $newEndTime, $settings, $rescheduleCount) {
            $appointment->update([
                'date' => $newDate,
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'status' => $settings->requires_approval ? 'pending' : 'confirmed',
                'email_sent' => false, // Resetea flag para obligar la re-notificación
                'reschedule_count' => $rescheduleCount + 1, // Incrementa el contador de reprogramaciones
            ]);
        });

        $message = $settings->requires_approval 
            ? 'Tu solicitud de cambio fue enviada y está sujeta a la aprobación de la clínica.' 
            : 'Tu cita ha sido reprogramada exitosamente.';

        // Si el paciente cambió la cita, notificamos al MÉDICO
        // Buscamos el médico por su ID, y luego extraemos su user_id de autenticación
        $doctor = Doctor::findOrFail($appointment->doctor_id);
        $userToNotify = User::find($doctor->user_id);

        // Enviamos la notificación al usuario de autenticación correcto
        if ($userToNotify) {
            $userToNotify->notify(new AppointmentRescheduledNotification($appointment, 'patient'));
        }

        return back()->with('success', $message);
    }
}
