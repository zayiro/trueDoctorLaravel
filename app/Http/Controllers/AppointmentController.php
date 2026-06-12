<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Patient;
use Spatie\Permission\Models\Role;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Services\AppointmentService;
use App\Models\ZoomCreationFailure;
use Illuminate\Support\Facades\Crypt;
use App\Services\ZoomService;
use App\Models\User;
use App\Events\AppointmentCancelled;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;
use Carbon\Carbon;
use App\Notifications\MailLimitExceededNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Throwable;

class AppointmentController extends Controller
{
    // Definición de propiedades protegidas para los servicios del SaaS
    protected AppointmentService $appointmentService;
    protected ZoomService $zoomService;

    public function __construct(AppointmentService $service, ZoomService $zoomService)
    {
        $this->appointmentService = $service;
        $this->zoomService = $zoomService;
    }

    /**
     * Obtiene el modelo dueño actual autenticado (Exclusivo para flujos del Panel Privado Dashboard).
     * ⚠️ NOTA DE SEGURIDAD: No utilizar en métodos de agendamiento público, ya que el paciente es un invitado.
     */
    protected function getOwner()
    {
        $user = Auth::user();
        if (!$user) return null;
        
        return $user->role === 'clinic' ? $user->clinic : $user->doctor;
    }

    /**
     * Busca una reservación por su referencia filtrando según el rol del usuario (Lectura Pura para AJAX).
     * Responde de forma asíncrona al componente x-appointment-search-form.
     */
    public function searchByReference(Request $request)
    {
        // 1. Validar la entrada de forma estricta
        $request->validate([
            'reference' => 'required|string|max:20',
        ]);

        $user = Auth::user();

        // Limpiamos espacios y forzamos mayúsculas (Ej: 26060121-YHK)
        $reference = strtoupper(trim($request->query('reference')));

        // 2. Consulta base con relaciones optimizadas para el reporte de la ficha médica
        $query = Appointment::where('reference', $reference)
            ->with(['address', 'service', 'patient.user', 'doctor.user', 'clinic']);

        // 3. Aplicar reglas de seguridad por Rol (Multi-tenancy & Multi-profile)
        switch ($user->role) {
            case 'clinic':
                // La cita debe pertenecer estrictamente al Tenant de la clínica autenticada
                $query->where('clinic_id', $user->clinic->id);
                break;

            case 'doctor':
                // La cita debe pertenecer estrictamente al médico independiente autenticado
                $query->where('doctor_id', $user->doctor->id);
                break;

            case 'patient':
                // El paciente solo puede auto-consultar su propio historial
                $query->where('patient_id', $user->patient->id);
                break;

            case 'admin':
                // El administrador global del SaaS tiene pase libre para auditorías
                break;

            default:
                return response()->json(['error' => 'Rol de usuario no autorizado en la plataforma.'], 403);
        }

        // 4. Ejecutar la consulta en la base de datos
        $appointment = $query->first();

        // 5. Validar existencia y pertenencia (Retorno semántico HTTP 404 para interceptar en el catch de Alpine)
        if (!$appointment) {
            return response()->json([
                'error' => "No se encontró ninguna cita médica con la referencia '{$reference}' o no cuentas con los permisos de co-propiedad requeridos."
            ], 404);
        }

        // 6. CONTROL DE LECTURA PURA: Retornamos el payload JSON estructurado para pintar en el modal
        return response()->json([
            'reference'   => $appointment->reference,
            'patient'     => $appointment->patient->user->name ?? 'Paciente no registrado',
            'doctor'      => $appointment->doctor->user->name ?? 'Profesional no asignado',
            'clinic'      => $appointment->clinic->name ?? null,
            'service'     => $appointment->service->name ?? 'Consulta General',
            'type'        => $appointment->service->type ?? 'physical', // virtual o physical
            'date'        => Carbon::parse($appointment->date)->format('d/m/Y'),
            'time'        => Carbon::parse($appointment->start_time)->format('g:i A'),
            'duration'    => $appointment->duration,
            'price'       => number_format($appointment->price, 2) . ' COP',
            'status'      => $appointment->status,
            'address'     => $appointment->address->name ?? 'Sede Virtual / Telemedicina',
            'notes'       => $appointment->notes ?? 'El paciente no registró síntomas o notas adicionales en esta consulta.'
        ], 200);
    }
    
    /**
     * Consólida la transacción final de reserva insertando la cita médica (Multi-tenant).
     */
    public function store(Request $request)
    {
        // 1. Validación estructural estricta de la reserva
        $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'address_id' => 'required|integer|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'start_time' => 'required|string',
            'doctor_id'  => 'required|integer|exists:doctors,id',
            'clinic_id'  => 'nullable|integer|exists:clinics,id',
        ]);

        // 2. RESOLUCIÓN DE LA ENTIDAD PACIENTE (Fin del bug de llaves users.id)
        $patient = DB::table('patients')->where('user_id', Auth::id())->first();
        if (!$patient) {
            return redirect()->back()->withErrors(['error' => 'No se encontró un perfil de paciente válido vinculado a tu cuenta.']);
        }

        $service = Service::findOrFail($request->service_id);
        $address = Address::findOrFail($request->address_id);
        $doctorId = $request->integer('doctor_id');
        $dateInput = $request->input('date');
        $startTime = Carbon::parse($request->start_time)->format('H:i:s');

        // Buscar la duración específica en la tabla pivote de la sede para calcular el end_time real
        $pivotDuration = DB::table('address_service')
            ->where('address_id', $address->id)
            ->where('service_id', $service->id)
            ->value('duration') ?? 20;

        $endTime = Carbon::parse($startTime)->addMinutes((int)$pivotDuration)->format('H:i:s');

        // ====================================================================
        // 🔥 ESCUDO FINAL DE CONCURRENCIA (BLOQUEO PESIMISTA ANTE DOBLE CLIC)
        // ====================================================================
        $isAlreadyBooked = Appointment::where('address_id', $address->id)
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $dateInput)
            ->where('start_time', $startTime)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();

        if ($isAlreadyBooked) {
            return redirect()->route('partner.public.profile', $request->input('doctor_slug', 'search'))
                ->with('error', 'El turno seleccionado fue reservado por otro paciente en el último segundo. Por favor, elige uno nuevo.');
        }

        // Estado por defecto según tus reglas de pre-aprobación del SaaS
        $status = \App\Enums\AppointmentStatus::CONFIRMED->value; 
        $payment_status = \App\Enums\PaymentStatus::PENDING->value;

        // 3. Preparación molecular de la matriz de datos transaccionales
        $appointmentData = [
            'patient_id'     => (int) $patient->id, // Vinculado a la tabla patients legítima
            'doctor_id'      => (int) $doctorId,
            'clinic_id'      => $address->clinic_id ? (int) $address->clinic_id : ($request->filled('clinic_id') ? (int) $request->clinic_id : null),
            'service_id'     => (int) $service->id,
            'address_id'     => (int) $address->id,
            'date'           => $dateInput,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'status'         => $status,
            'payment_status' => $payment_status,
        ];

        // 🔒 CONTROL NATIVO DE TELEMEDICINA ASÍNCRONA
        if ($service->type === 'virtual' || $address->is_virtual) {
            try {
                // Invocamos al servicio de Zoom inyectado para estructurar la sala virtual
                $zoomMeeting = $this->zoomService->createMeeting([
                    'topic'      => "Consulta Médica: " . $service->name,
                    'start_time' => Carbon::parse("$dateInput $startTime")->toIso8601String(),
                    'duration'   => (int) $pivotDuration,
                ]);

                // Acoplamos las credenciales seguras de la API de Zoom
                $appointmentData['zoom_meeting_id']   = $zoomMeeting['id'];
                $appointmentData['zoom_start_url']    = $zoomMeeting['start_url'];
                $appointmentData['zoom_join_url']     = $zoomMeeting['join_url'];
                $appointmentData['meeting_link']      = $zoomMeeting['join_url'];
            } catch (\Exception $e) {
                // ⚠️ TRATAMIENTO DE ERRORES ASÍNCRONO NATIVO (OPENDOCTOR)
                // Si la API de Zoom falla, se crea un link temporal y se delega la corrección al Job
                $appointmentData['meeting_link'] = url('/meet/pending-' . Str::random(6));
                
                // Registramos el fallo para que la cola de Jobs reintente la creación en background
                DB::table('zoom_creation_failures')->insert([
                    'appointment_id' => $appointmentData['id'],
                    'error_log'      => $e->getMessage(),
                    'created_at'     => now()
                ]);
            }
        }
        
        // 4. Inserción atómica final en la base de datos
        $appointment = Appointment::create($appointmentData);

        // 🔥 LIMPIEZA ABSOLUTA DE MEMORIA CACHEADA EN SESIÓN
        session()->forget('booking_data');
        session()->forget('current_doctor_id');
        session()->forget('current_clinic_user_id');

        // 5. Redirección blindada al recibo de éxito que validará la propiedad de la cita
        return redirect()->route('appointments.success', $appointment->id);
    }

    /**
     * Almacena temporalmente en sesión los datos de la cita elegida por el paciente (Multi-tenant).
     */
    public function storeStepTwo(Request $request) 
    {
        // 1. Validación estructural rígida de tipos y estados en el payload JSON
        $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'address_id' => 'required|integer|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'hour'       => 'required|string',
            'doctor_id'  => 'nullable|integer|exists:doctors,id',
            'clinic_id'  => 'nullable|integer|exists:clinics,id',
        ]);

        $addressId = $request->integer('address_id');
        $dateInput = $request->input('date');
        $hourInput = $request->input('hour');
        $address = Address::findOrFail($addressId);

        // 2. RESOLUCIÓN DE IDENTIDAD COMERCIAL (Fin del Bug de Llaves Cruzadas user_id)
        $targetDoctorId = $request->integer('doctor_id');

        if (!$targetDoctorId) {
            // Si Alpine no lo envía, buscamos el modelo de doctor usando el user_id de la sesión
            $sessionDoctorUserId = session('current_doctor_id');
            if ($sessionDoctorUserId) {
                $targetDoctorId = DB::table('doctors')
                    ->where('user_id', $sessionDoctorUserId)
                    ->value('id');
            }
        }

        // Si sigue sin resolverse y estamos en una clínica, lo extraemos de la agenda de la sede
        if (!$targetDoctorId && $address->clinic_id) {
            $targetDoctorId = DB::table('schedules')
                ->where('address_id', $addressId)
                ->where('day', Carbon::parse($dateInput)->dayOfWeekIso)
                ->value('doctor_id');
        }

        // 3. 🔒 BLINDAJE DE CO-PROPIEDAD CONTEXTUAL MULTI-TENANT
        $targetClinicId = $request->integer('clinic_id');

        if (!$targetClinicId) {
            if ($address->clinic_id) {
                $targetClinicId = $address->clinic_id;
            } else {
                $sessionClinicUserId = session('current_clinic_user_id');
                if ($sessionClinicUserId) {
                    $targetClinicId = DB::table('clinics')
                        ->where('user_id', $sessionClinicUserId)
                        ->value('id');
                }
            }
        }

        // 🛡️ CONTROL DE INTEGRIDAD OPERATIVA
        if (!$targetDoctorId) {
            return response()->json([
                "message" => "No se pudo identificar al especialista responsable para gestionar esta reserva.",
                "status"  => false,
            ], 422);
        }

        // ====================================================================
        // 🔥 REVALIDACIÓN TRANSACCIONAL UTIZANDO EL SERVICIO INYECTADO
        // ====================================================================
        $availableSlots = $this->appointmentService->getAvailableSlots(
            $addressId,
            $dateInput,
            $targetDoctorId,
            $address->type === 'virtual',
            $request->integer('service_id')
        );

        // Convertimos la hora recibida (ej: "3:00 PM") a formato estándar para comparar de forma segura
        $formattedRequestedHour = Carbon::parse($hourInput)->format('g:i A');
        $slotStillAvailable = collect($availableSlots)->contains('time', $formattedRequestedHour);

        if (!$slotStillAvailable) {
            return response()->json([
                "message" => "El turno seleccionado acaba de ser reservado por otro paciente. Por favor, elige otra hora.",
                "status"  => false,
            ], 409);
        }

        // 4. Empaquetamos el objeto sanitizado congelándolo en la sesión nativa
        session()->forget('booking_data');
        session([
            'booking_data' => [
                'clinic_id'  => $targetClinicId ? (int) $targetClinicId : null, 
                'doctor_id'  => (int) $targetDoctorId, 
                'service_id' => (int) $request->service_id,
                'address_id' => (int) $addressId,
                'date'       => $dateInput,
                'hour'       => $hourInput,
            ]
        ]);

        return response()->json([
            "message" => "Información de reserva (booking_data) configurada correctamente en el ecosistema de OpenDoctor.",
            "status"  => true,
        ]);
    }

    /**
     * Despacha la vista del formulario de captura de datos del paciente validando el contexto virtual e institucional.
     */
    public function patient()
    {
        // 1. Proteger el acceso al paso: si no existe intento de reserva activo, abortar de forma contextual
        if (!session()->has('booking_data')) {
            // Fallback inteligente: si no hay sesión, lo saca al home global para no tirar un error de ruta inexistente
            return redirect()->to('/');
        }

        // Extraer la estructura de datos unificada de la sesión
        $bookingData = session('booking_data');
        $addressId = $bookingData['address_id'] ?? null;
        $targetDoctorId = $bookingData['doctor_id'] ?? null;
        $serviceId = $bookingData['service_id'] ?? null;

        $isVirtualAddress = false;

        // 2. 🔒 DETECCIÓN BIMODAL INTEGRAL DE VIRTUALIDAD (Sede o Tipo de Servicio)
        if ($addressId) {
            $address = Address::with(['services' => function($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            }])->find($addressId);

            if ($address) {
                $hasVirtualService = $address->services->contains('type', 'virtual');
                
                // Es virtual si la sede es de telemedicina O si el servicio médico elegido es virtual
                $isVirtualAddress = ($address->type === 'virtual' || $hasVirtualService); 
            }
        }

        // 3. 🛡️ BLINDAJE DE AUTO-AGENDAMIENTO (Self Booking / Recepción Asistida de Clínica)
        $authUserId = Auth::id();
        $isSelfBooking = false;

        if ($authUserId) {
            $user = Auth::user();
            
            if ($user->role === 'doctor' && $user->doctor) {
                // Si el usuario logueado es médico y el ID coincide con el doctor de la cita, es auto-agendamiento (bloquea fraude)
                $isSelfBooking = ($user->doctor->id === (int) $targetDoctorId);
            } elseif ($user->role === 'clinic' && $user->clinic && isset($bookingData['clinic_id'])) {
                // Si el usuario logueado es una clínica y la cita ocurre en su tenant, es reserva asistida desde recepción
                $isSelfBooking = ($user->clinic->id === (int) $bookingData['clinic_id']);
            }
        }
        
        // 4. Inyectamos las variables de control y los datos unificados a la vista estructurada
        return view('appointments.patient', compact(
            'isSelfBooking', 
            'isVirtualAddress', 
            'bookingData'
        ));
    }

    /**
     * Procesa la captura de datos del paciente, gestiona el login/registro automático y crea la transacción.     
     */
    public function processPatient(Request $request)
    {
        // 1. Proteger el acceso al paso: si no existe intento de reserva activo, abortar
        $bookingData = session('booking_data');
        if (!$bookingData || !isset($bookingData['doctor_id'])) {
            return redirect()->to('/')->with('error', 'Sesión inválida o datos de reserva incompletos.');
        }

        $rules = ['notes' => 'required|string|min:10|max:500'];
        $hasAccount = $request->has_account == 'yes';

        // 🔒 ESCUDO DE SEGURIDAD INTERNO: Bloquear si un personal del sistema intenta actuar como paciente
        if (Auth::check() && Auth::user()->role !== 'patient') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tu cuenta actual pertenece al personal del sistema ('.Auth::user()->role.'). No tienes permisos para registrar citas como paciente. Por favor, cierra sesión e intenta de nuevo.');
        }

        // 2. Construcción de reglas dinámicas basadas en el estado del invitado
        if (Auth::guest()) {
            if ($hasAccount) {
                $rules['login_email'] = 'required|email|exists:users,email';
                $rules['login_password'] = 'required';
            } else {
                $rules['name'] = 'required|string|min:3|max:100';
                $rules['email'] = 'required|email|unique:users,email';
                $rules['identification'] = 'required|numeric|unique:patients,identification';
                $rules['phone'] = 'required|numeric';
            }
        }
        
        $request->validate($rules);

        // 3. PROCESAR AUTENTICACIÓN PREVIA (Seguridad Multi-tenant fuera de la transacción SQL)
        if (Auth::guest() && $hasAccount) {
            $login_email = trim(strtolower($request->login_email));
            $login_password = $request->login_password;
            
            // Buscar el usuario antes del login para verificar su rol estrictamente
            $targetUser = User::where('email', $login_email)->first();
            if ($targetUser && $targetUser->role !== 'patient') {
                return back()->withErrors(['login_email' => 'Esta cuenta pertenece al personal del sistema y no puede agendar citas como paciente.'])->withInput();
            }

            if (!Auth::attempt(['email' => $login_email, 'password' => $login_password])) {
                return back()->withErrors(['login_email' => 'Las credenciales introducidas no coinciden con nuestros registros.'])->withInput();
            }
        }
        // 4. EJECUCIÓN ATÓMICA DE REGISTRO (Dentro de la transacción SQL)
        $user = Auth::user();

        if (Auth::guest() && !$hasAccount) {
            $user = DB::transaction(function () use ($request) {
                $cleanIdentification = trim($request->identification);
                $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
                $fullPhone = $request->country_code ? $request->country_code . $cleanPhone : '+57' . $cleanPhone;

                $newUser = User::create([
                    'name'     => trim($request->name),
                    'email'    => trim($request->email),
                    'password' => Hash::make($cleanIdentification),
                    'role'     => 'patient',
                ]);

                // Asignación inmutable del rol a través de Spatie
                $role = Role::firstOrCreate(['name' => 'patient']);
                $newUser->assignRole($role);

                // Control relacional: Crear el perfil clínico en la tabla patients
                Patient::create([
                    'user_id'        => $newUser->id,
                    'identification' => $cleanIdentification,
                    'phone'          => $fullPhone,
                ]);

                return $newUser;
            });

            Auth::login($user);
        }

        // 5. 🛡️ CONTROL EXTRA DE SEGURIDAD POST-AUTENTICACIÓN
        if ($user->role !== 'patient') {
            return back()->with('error', 'Operación cancelada. El usuario autenticado debe tener exclusivamente el rol de paciente.');
        }

        // Recuperar el registro legítimo del perfil clínico
        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        // 6. RESOLUCIÓN DE IDENTIDADES COMERCIALES (Busca por 'id' real, no por user_id)
        $doctor = Doctor::with(['settings', 'user'])
            ->where('id', $bookingData['doctor_id'])
            ->first();

        if (!$doctor) {
            session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);
            return redirect()->to('/')->with('error', 'El médico seleccionado ya no se encuentra disponible.');
        }

        // Cargar la sede física validando el servicio de su tabla pivote
        $address = Address::with(['clinic.settings', 'services' => function($q) use ($bookingData) {
            $q->where('services.id', $bookingData['service_id']);
        }])->find($bookingData['address_id']);

        $serviceSpecific = $address?->services->first();

        if (!$serviceSpecific || !$serviceSpecific->pivot) {
            return redirect()->to('/')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
        }
        
        $duration = (int) $serviceSpecific->pivot->duration;
        $price = $serviceSpecific->pivot->price;
        
        // Calcular cronología exacta del turno
        $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
        $endTime = $startTime->copy()->addMinutes($duration);

        // ====================================================================
        // 🔒 CORRECCIÓN MÁXIMA: VALIDACIÓN EN SERVIDOR CON EL NUEVO MOTOR DE SLOTS
        // ====================================================================
        $availableSlots = $this->appointmentService->getAvailableSlots(
            $address->id,
            $bookingData['date'],
            $doctor->id,
            $address->type === 'virtual' || $serviceSpecific->type === 'virtual',
            $serviceSpecific->id
        );

        // Convertimos la hora elegida (ej: "3:00 PM") a formato estándar para comparar en la colección
        $formattedRequestedHour = Carbon::parse($bookingData['hour'])->format('g:i A');
        $isStillAvailable = collect($availableSlots)->contains('time', $formattedRequestedHour);
        
        if (!$isStillAvailable) {
            return redirect()->to('/')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente de forma simultánea.');
        }
        
        // Resolver políticas de aprobación jerárquica corporativa o privada
        $settings = $address->clinic_id ? $address->clinic->settings : $doctor->settings;
        $requiresApproval = $settings ? (bool)$settings->requires_approval : false;
        $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;

        if ($acceptsPayments) {
            $requiresApproval = false; 
        }
        
        // Estado por defecto según tus reglas de pre-aprobación del SaaS
        $status = $requiresApproval ? \App\Enums\AppointmentStatus::PENDING->value : \App\Enums\AppointmentStatus::CONFIRMED->value;
        $payment_status = \App\Enums\PaymentStatus::PENDING->value;
                    
        // 💾 CONSOLIDACIÓN DE LA RESERVA (Encapsulada en una transacción limpia)
        //reference lo hace en el modelo Appointment en el boot method, así que no es necesario generarla aquí
        $appointment = DB::transaction(function () use ($patient, $doctor, $address, $serviceSpecific, $bookingData, $startTime, $endTime, $duration, $price, $status, $payment_status, $request) {
            return Appointment::create([
                'patient_id'     => (int) $patient->id,
                'doctor_id'      => (int) $doctor->id,
                'clinic_id'      => $address->clinic_id ? (int) $address->clinic_id : null,
                'service_id'     => (int) $serviceSpecific->id,
                'address_id'     => (int) $address->id,
                'date'           => $bookingData['date'],
                'start_time'     => $startTime->format('H:i:s'),
                'end_time'       => $endTime->format('H:i:s'),
                'duration'       => $duration,
                'price'          => $price,
                'status'         => $status,
                'payment_status' => $payment_status,
                'channel'        => 'web',
                'notes'          => trim($request->notes),                
            ]);
        });
        
        // Limpieza absoluta de la memoria de sesión
        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        return redirect()->route('appointments.preview', ['id' => $appointment->id]);
    }
   
    /**
     * 🔥 NUEVA FUNCIÓN: Renderiza la pantalla de resumen de la orden médica.
     * Recibe el ID de la URL y precarga las dependencias del SaaS bajo estricto control de acceso.
     */
    public function preview($id)
    {
        // 1. Recuperar la cita con todas sus relaciones moleculares cargadas
        $appointment = Appointment::with([
            'doctor.user', 
            'clinic', 
            'service', 
            'address.city'
        ])->findOrFail($id);

        // 2. 🔒 CONTROL DE SEGURIDAD OPERATIVA ANTI-ESPIONAJE (Anti Data Leaking)
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'patient') {
                // Si está autenticado como paciente, validamos que la cita le pertenezca legítimamente
                $patient = DB::table('patients')->where('user_id', $user->id)->first();
                if (!$patient || (int)$appointment->patient_id !== (int)$patient->id) {
                    abort(403, 'Acceso denegado a esta orden médica de previsualización.');
                }
            } elseif ($user->role === 'doctor') {
                // Si es médico, validamos que él sea el especialista asignado a la cita
                $doctor = DB::table('doctors')->where('user_id', $user->id)->first();
                if (!$doctor || (int)$appointment->doctor_id !== (int)$doctor->id) {
                    abort(403, 'Acceso denegado. No eres el especialista responsable de esta cita.');
                }
            } elseif ($user->role === 'clinic') {
                // Si es una clínica, validamos que la cita ocurra en sus sucursales institucionales
                $clinic = DB::table('clinics')->where('user_id', $user->id)->first();
                if (!$clinic || (int)$appointment->clinic_id !== (int)$clinic->id) {
                    abort(403, 'Acceso denegado. Esta cita no pertenece a tu infraestructura corporativa.');
                }
            }
        } else {
            // 🛡️ CONTROL PARA INVITADOS: Si el paciente acaba de registrarse pero la sesión se cerró,
            // bloqueamos que cualquiera acceda a la URL de previsualización sin estar logueado.
            abort(401, 'Debes iniciar sesión para visualizar el resumen de tu orden médica.');
        }

        // 3. Despachamos la vista compactando el objeto totalmente aislado
        return view('appointments.preview', compact('appointment'));
    }

    /**
     * Muestra la pantalla de confirmación exitosa de la cita médica validando la tenencia del recurso.
    */    
    public function success(Appointment $appointment)
    {
        // 1. Cargar todas las relaciones necesarias desde el inicio para evitar consultas N+1
        $appointment->load(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user']);

        $activeUser = Auth::user();
        if (!$activeUser) {
            abort(401, 'Debes iniciar sesión para visualizar el comprobante de tu cita.');
        }

        // 🔒 BLINDAJE DE SEGURIDAD MULTI-TENANT RIGIDO
        $hasAccess = false;

        // Caso A: El usuario actual es el paciente dueño de la cita
        if ($activeUser->role === 'patient' && $appointment->patient) {
            if ((int)$appointment->patient->user_id === (int)$activeUser->id) {
                $hasAccess = true;
            }
        }
        // Caso B: El usuario es el médico especialista asignado
        elseif ($activeUser->role === 'doctor' && $appointment->doctor) {
            if ((int)$appointment->doctor_id === (int)$activeUser->doctor?->id) {
                $hasAccess = true;
            }
        }
        // Caso C: El usuario es el personal administrativo de la clínica donde ocurre la cita
        elseif ($activeUser->role === 'clinic') {
            if ((int)$appointment->clinic_id === (int)$activeUser->clinic?->id) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Acceso no autorizado a este recibo o comprobante transaccional de consulta médica.');
        }

        // 1. Verificamos rápidamente en la base de datos si la referencia está registrada como un fallo previo
        $hasCreationFailure = DB::table('zoom_creation_failures')->where('appointment_id', $appointment->id)->exists();
        
        // 2. 🔒 CONTROL DE TELEMEDICINA SEGURO (Previene duplicación de salas en recargas de página)
        if (
            (($appointment->service && $appointment->service->type === 'virtual') || ($appointment->address && $appointment->address->type === 'virtual')) 
            && !$appointment->zoom_meeting_id 
            && !$hasCreationFailure
        ) {            
            try {
                // Usamos una transacción con bloqueo pesimista para evitar que recargas simultáneas dupliquen salas
                DB::transaction(function () use ($appointment) {
                    // Bloqueamos la fila en la base de datos para esta petición
                    $freshAppointment = $appointment->newQuery()->lockForUpdate()->find($appointment->id);

                    // Doble verificación: Si otra petición o el Job ya creó la sala en este instante, abortamos
                    if ($freshAppointment->zoom_meeting_id) {
                        return;
                    }

                    $onlyDate = substr($freshAppointment->date, 0, 10); 
                    $startDateTime = Carbon::parse("{$onlyDate} {$freshAppointment->start_time}")->toIso8601String();
                    $topic = "Telemedicina_Ref: " . $freshAppointment->reference;

                    // Invocación al servicio
                    $zoomMeeting = $this->zoomService->createMeeting($topic, $startDateTime, (int) $freshAppointment->duration);                

                    if ($zoomMeeting) {
                        // El modelo encripta automáticamente gracias a los nuevos mutadores set...Attribute
                        $freshAppointment->update([
                            'zoom_meeting_id' => $zoomMeeting['meeting_id'], //ID numérico de la reunión en Zoom                  
                            'meeting_link'    => $zoomMeeting['url_patient'], //Enlace genérico o exclusivo para el Paciente
                            'zoom_start_url'  => $zoomMeeting['url_partner'], //Enlace exclusivo para que el Doctor inicie como Anfitrión
                        ]);                                        

                        // ÉXITO: Limpiamos la tabla de contingencia asíncrona
                        DB::table('zoom_creation_failures')->where('appointment_id', $freshAppointment->id)->delete();
                        
                        // Sincronizamos el estado fresco (y desencriptado por el accessor) en el objeto original para la vista
                        $appointment->fill($freshAppointment->toArray())->syncOriginal();
                    } else {
                        \Log::error('Fallo al crear la reunión de Zoom con referencia: ' . $freshAppointment->reference . ' - Registrando para contingencia asíncrona.');
                        
                        DB::table('zoom_creation_failures')->updateOrInsert(
                            ['appointment_id' => $freshAppointment->id],
                            [
                                'status'     => 'pending',
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }
                });
            } catch (\Exception $e) {
                \Log::error('Fallo crítico en bloque de contingencia Zoom para la referencia: ' . $appointment->reference . ' - ' . $e->getMessage());
            }
        }

        // 3. ENVÍO CONTROLADO DE CORREO ELECTRÓNICO (Evita caídas por límites de SMTP corporativos)
        if (!$appointment->email_sent) {
            try {
                $patientEmail = $appointment->patient?->user?->email;
                $doctorEmail = $appointment->doctor?->user?->email;

                if ($patientEmail) {
                    Mail::to($patientEmail)->send(new AppointmentConfirmed($appointment, 'patient'));
                }
                
                if ($doctorEmail && $appointment->service->type === 'physical') {
                    Mail::to($doctorEmail)->send(new AppointmentConfirmed($appointment, 'partner'));
                }

                $appointment->update(['email_sent' => true]);
            } catch (Throwable $e) {
                \Log::error("Límite de correo excedido o SMTP caído en el recibo de éxito: " . $e->getMessage());
                
                // Notificación silenciosa opcional para el administrador de la plataforma
                try {
                    $admins = User::where('role', 'admin')->get();
                    \Notification::send($admins, new MailLimitExceededNotification($e->getMessage(), $activeUser->email));
                } catch (\Exception $ne) {
                    // Evitar bucle infinito de excepciones si el Driver de correo está roto
                }
            }
        }            
        
        // Sincronizamos la instancia fresca final para la renderización del Blade
        $appointment = $appointment->fresh(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user']);

        return view('appointments.success', compact('appointment'));
    }

    /**
     * Cancela el flujo de reserva, valida la propiedad del paciente y elimina el registro de forma segura.
    */
    public function cancelFlow(Request $request)
    {
        $appointmentId = $request->integer('id');
        $user = Auth::user(); 

        if ($appointmentId > 0) {
            // Buscamos la cita médica con su relación de paciente
            $appointment = Appointment::with('patient')->find($appointmentId);
            
            if ($appointment) {
                // 🔒 BARRERA DE SEGURIDAD EXCLUSIVA DEL SAAS (Anti-IDOR)
                if ($user) {
                    // Caso A: Si está logueado como paciente, validamos la propiedad directa del perfil clínico
                    if ($user->role === 'patient') {
                        if (!$appointment->patient || (int)$appointment->patient->user_id !== (int)$user->id) {
                            abort(403, 'Operación no autorizada. No tienes propiedad sobre esta orden médica.');
                        }
                    }
                    // Caso B: Si es personal de una clínica o un médico en auto-agendamiento, validamos su tenencia
                    elseif ($user->role === 'clinic') {
                        if ((int)$appointment->clinic_id !== (int)$user->clinic?->id) {
                            abort(403, 'No tienes autorización para alterar registros de otra institución.');
                        }
                    }
                } else {
                    // Caso C: Si es un usuario invitado (Guest), BLINDAMOS el borrado verificando 
                    // que el ID de la cita coincida estrictamente con la última que guardó en su sesión booking_data
                    $bookingData = session('booking_data');
                    
                    // Si no tiene booking_data activo o el ID no coincide, bloqueamos fulminantemente la petición
                    if (!$bookingData || !isset($bookingData['appointment_id']) || (int)$bookingData['appointment_id'] !== $appointmentId) {
                        abort(403, 'Acceso denegado. No puedes cancelar un flujo de reserva que no te pertenece.');
                    }
                }

                // Si supera los escudos, se procede al borrado seguro (aplica SoftDeletes si el modelo lo tiene)
                $appointment->delete(); 
            }
        }
        // Limpieza absoluta de los mapas de datos de la sesión para liberar el entorno
        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        // Redirección contextual unificada al home global inmune a errores de rutas inexistentes
        return redirect()->route('search')->with('info', 'Proceso de reserva cancelado con éxito. El horario de atención ha sido liberado.');
    }

    /**
     * Renderiza la sala de telemedicina incrustada dentro de OpenDoctor
     */
    public function joinRoom(Appointment $appointment, ZoomService $zoomService)
    {
        // 1. Validar que la cita tenga un ID de reunión generado
        if (!$appointment->zoom_meeting_id) {
            return redirect()->route('admin.dashboard')->with('error', 'Esta cita no tiene una videollamada activa.');
        }

        try {
            // 2. Extraemos el ID y desencriptamos la contraseña de la base de datos
            $meetingId = $appointment->zoom_meeting_id;

            // CONTROL DE CONTINGENCIA: Intentamos desencriptar de forma segura
            try {
                $password = Crypt::decryptString($appointment->meeting_link_password);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Si el payload es inválido (texto plano viejo), usamos el valor de la BD directo
                $password = $appointment->meeting_link_password; 
            }
            
            // 3. Generamos la firma segura para el SDK (Rol: 0 para paciente/asistente)
            $signature = $zoomService->generateSdkSignature($meetingId, 0);

            // 4. Inyectamos las variables exactas que la vista "room" necesita recibir
            return view('appointments.room', [
                'appointment' => $appointment,
                'meetingId'   => $meetingId,
                'password'    => $password,
                'signature'   => $signature,
                'sdkKey'      => config('services.zoom.client_id'),
            ]);

        } catch (\Exception $e) {
            Log::error("Error en joinRoom de Zoom SDK (Cita ID {$appointment->id}): " . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', 'No se pudieron recuperar las llaves de acceso del consultorio virtual.');
        }
    }

    /**
     * Termina de forma abrupta la reunión en vivo de Zoom cuando el contador llega a cero.
     */
    public function forceEndMeeting(Appointment $appointment)
    {
        // Validar que la cita tenga un ID de reunión activo
        if (!$appointment->zoom_meeting_id) {
            return response()->json(['status' => 'ignored', 'message' => 'La cita no posee una sala de Zoom activa.'], 400);
        }

        try {
            // Ejecutamos tu método del servicio para expulsar a los usuarios y cerrar la sala
            $success = $this->zoomService->endMeeting($appointment->zoom_meeting_id);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'La reunión de Zoom ha sido finalizada con éxito por expiración de tiempo.'
                ], 200);
            }

            return response()->json(['status' => 'error', 'message' => 'Zoom no pudo procesar la orden de cierre.'], 500);

        } catch (\Exception $e) {
            Log::error("Error forzando cierre de Zoom para cita {$appointment->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Ocurrió un fallo en el servidor.'], 500);
        }
    }

    /**
     * Devuelve el estado actual de la cita (Invocado cada 5 segundos por el Polling de Alpine)
     */
    public function getStatus(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'status' => $appointment->status, // pending, completed, etc.
        ]);
    }

    /**
     * Fuerza el cierre de la reunión en los servidores de Zoom cuando el tiempo expira
     */
    public function endZoomMeeting(Appointment $appointment, ZoomService $zoomService): JsonResponse
    {
        // 1. Validar que la cita tenga un ID de reunión de Zoom registrado
        if (!$appointment->zoom_meeting_id) {
            return response()->json(['message' => 'La cita no tiene una reunión activa.'], 422);
        }

        // 2. Llamamos al método oficial de tu ZoomService para expulsar a los usuarios y cerrar la sala
        $closedInZoom = $zoomService->endMeeting($appointment->zoom_meeting_id);

        if ($closedInZoom) {
            // 3. Actualizamos el estado de la cita en nuestra base de datos como completada o terminada
            $appointment->update([
                'status' => 'completed'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'La videollamada fue finalizada con éxito por expiración de tiempo.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo cerrar la sesión en los servidores de Zoom.'
        ], 500);
    }
}
