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
    protected $appointmentService;
    protected $zoomService;

    public function __construct(AppointmentService $service, ZoomService $zoomService)
    {
        $this->appointmentService = $service;
        $this->zoomService = $zoomService;
    }

    /**
     * Obtiene el modelo dueño actual (Doctor o Clinic).
     */
    protected function getOwner()
    {
        $user = Auth::user();
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
     * Actualiza el estado de una cita validando los permisos del rol.
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        // 1. Validar que el estado enviado sea uno permitido
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $user = Auth::user();
        $newStatus = $request->status;

        // 2. CONTROL DE SEGURIDAD INTERNA Y PERMISOS POR ROL
        if ($user->role === 'patient') {
            // Un paciente SOLO puede cancelar su propia cita
            if ($appointment->user_id !== $user->id) {
                abort(403, 'Acción no autorizada.');
            }
            if ($newStatus !== 'cancelled') {
                return redirect()->back()->with('error', 'Los pacientes solo pueden cancelar citas.');
            }
        } elseif (in_array($user->role, ['doctor', 'clinic'])) {
            // Doctores y Clínicas solo pueden modificar citas de sus sedes (Tenancy)
            $ownerId = $user->role === 'clinic' ? $user->clinic->id : $user->doctor->id;
            $foreignKey = $user->role === 'clinic' ? 'clinic_id' : 'doctor_id';

            if ($appointment->address->$foreignKey !== $ownerId) {
                abort(403, 'Acción no autorizada.');
            }
        } elseif ($user->role !== 'admin') {
            abort(403, 'Rol no autorizado.');
        }

        // 3. Ejecutar la actualización del estado
        $appointment->update([
            'status' => $newStatus
        ]);

        return redirect()->route('appointments.search', ['reference' => $appointment->reference])
            ->with('success', 'El estado de la reservación ha sido actualizado a: ' . __($newStatus));
    }
    
    public function store(Request $request)
    {
        $service = Service::findOrFail($request->service_id);
        $address = $request->address_id ? Address::find($request->address_id) : null;
        
        $appointmentData = [
            'patient_id' => auth()->id(),
            'doctor_id'  => $request->doctor_id,
            'clinic_id'  => $address ? $address->clinic_id : null, // 🔥 RASTRÉO SAAS: Vincula la clínica de la sede
            'service_id' => $service->id,
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'status'     => 'confirmed',
        ];

        if ($service->type === 'virtual') {
            $appointmentData['meeting_link'] = url('/meet/' . Str::random(10));
        } else {
            $appointmentData['address_id'] = $request->address_id;
        }

        Appointment::create($appointmentData);

        return redirect()->route('patient.appointments')
            ->with('success', 'Cita agendada. ' . ($service->type === 'virtual' ? 'El link de la reunión está listo.' : ''));
    }

    /**
     * Almacena temporalmente en sesión los datos de la cita elegida por el paciente (Multi-tenant).
     */
    public function storeStepTwo(Request $request) 
    {
        // 1. Extraer los datos enviados por la máquina de estados de Alpine.js
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'address_id' => 'required|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'hour'       => 'required',
            'doctor_id'  => 'nullable|exists:doctors,id',
            'clinic_id'  => 'nullable|exists:clinics,id',
        ]);

        // 2. Resolver el ID del doctor objetivo de la reserva
        // Priorizar el enviado por el request (Asignación rápida/Staff), de lo contrario usar el congelado en sesión
        $sessionDoctorId = session('current_doctor_id');
        $targetDoctorId = $request->input('doctor_id', $sessionDoctorId);

        // 3. 🔒 BLINDAJE DE CO-PROPIEDAD: Resolver el ID de la clínica
        // Priorizar el clinic_id enviado en el payload JSON por Alpine.js
        $targetClinicId = $request->input('clinic_id');

        if (!$targetClinicId) {
            // Si no viene en el request, evaluar el address_id seleccionado para ver si le pertenece a una clínica aliada
            $address = Address::find($request->address_id);
            if ($address && $address->clinic_id) {
                $targetClinicId = $address->clinic_id;
            } else {
                // Si la sede es privada pero existía un rastro de sesión institucional, resolverlo por su user_id
                $sessionClinicUserId = session('current_clinic_user_id');
                if ($sessionClinicUserId) {
                    $clinicProfile = Clinic::where('user_id', $sessionClinicUserId)->first();
                    $targetClinicId = $clinicProfile?->id;
                }
            }
        }

        // 🛡️ VALIDACIÓN DE SEGURIDAD OPERATIVA: Validar que exista al menos un médico asignable
        if (!$targetDoctorId) {
            return [
                "message" => "No se pudo identificar al especialista responsable para gestionar esta reserva de cita médica.",
                "status"  => false,
            ];
        }

        // 🛠️ Limpieza absoluta de intentos de reserva previos
        session()->forget('booking_data');

        // 4. Empaquetamos el nuevo objeto estructurado de reserva congelándolo en la sesión nativa
        session([
            'booking_data' => [
                'clinic_id'  => $targetClinicId ? (int) $targetClinicId : null, 
                'doctor_id'  => (int) $targetDoctorId, 
                'service_id' => (int) $request->service_id,
                'address_id' => (int) $request->address_id,
                'date'       => $request->date,
                'hour'       => $request->hour,
            ]
        ]);

        return [
            "message" => "Información de reserva (booking_data) configurada correctamente en el ecosistema de OpenDoctor.",
            "status"  => true,
        ];
    }

    /**
     * Despacha la vista del formulario de captura de datos del paciente validando el contexto virtual e institucional.
     */
    public function patient()
    {
        // 1. Proteger el acceso al paso: si no existe intento de reserva activo, abortar a la búsqueda
        if (!session()->has('booking_data')) {
            return redirect()->route('search');
        }

        // Extraer la estructura de datos unificada de la sesión
        $bookingData = session('booking_data');
        $addressId = $bookingData['address_id'] ?? null;
        $targetDoctorId = $bookingData['doctor_id'] ?? null;

        $isVirtualAddress = false;

        // 2. 🔒 VALIDACIÓN SEGURA DE MODALIDAD VIRTUAL: Mapeada con tu columna física de migraciones 'type'
        if ($addressId) {
            $address = Address::find($addressId);
            if ($address) {
                // Tu esquema define $table->string('type')->default('physical')
                $isVirtualAddress = ($address->type === 'virtual'); 
            }
        }

        // 3. 🛡️ BLINDAJE DE AUTO-AGENDAMIENTO (Self Booking / Recepción de Clínica)
        $authUserId = auth()->id();
        $isSelfBooking = false;

        if ($authUserId) {
            $user = auth()->user();
            
            if ($user->role === 'doctor' && $user->doctor) {
                // Si el usuario logueado es médico y el ID coincide con el doctor de la cita, es un auto-agendamiento
                $isSelfBooking = ($user->doctor->id === (int) $targetDoctorId);
            } elseif ($user->role === 'clinic' && $user->clinic && isset($bookingData['clinic_id'])) {
                // Si el usuario logueado es una clínica y la cita ocurre en sus instalaciones, es una reserva asistida de recepción
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

    public function processPatient(Request $request)
    {
        $bookingData = session('booking_data');
        if (!$bookingData || !isset($bookingData['doctor_id'])) {
            return redirect()->route('search')->with('error', 'Sesión inválida o datos de reserva incompletos.');
        }

        $rules = ['notes' => 'required|string|min:10|max:500'];
        $hasAccount = $request->has_account == 'yes';

        // 1. VALIDACIÓN PREVIA EN CASO DE USUARIO AUTENTICADO
        if (Auth::check() && auth()->user()->role !== 'patient') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tu cuenta actual pertenece al personal del sistema ('.auth()->user()->role.'). No tienes permisos para registrar citas como paciente. Por favor, cierra sesión e intenta de nuevo.');
        }

        if (Auth::guest()) {
            if ($hasAccount) {
                $rules['login_email'] = 'required|email|exists:users,email';
                $rules['login_password'] = 'required';
            } else {
                $rules['name'] = 'required|string|min:3|max:100';
                $rules['email'] = 'required|email|unique:users,email';
                // Añadimos validación única para evitar usar una identificación existente de personal
                $rules['identification'] = 'required|numeric|unique:patients,identification';
                $rules['phone'] = 'required|numeric';
            }
        }
        
        $request->validate($rules);
                                        
        return DB::transaction(function () use ($request, $bookingData, $hasAccount) {
            
            if (Auth::guest()) {
                if ($hasAccount) {
                    $login_email = trim(strtolower($request->login_email));
                    $login_password = $request->login_password;
                    
                    // Buscar el usuario antes del login para verificar su rol
                    $targetUser = User::where('email', $login_email)->first();
                    if ($targetUser && $targetUser->role !== 'patient') {
                        return back()->withErrors(['login_email' => 'Esta cuenta pertenece al personal del sistema y no puede agendar citas como paciente.'])->withInput();
                    }

                    if (!Auth::attempt(['email' => $login_email, 'password' => $login_password])) {
                        return back()->withErrors(['login_email' => 'Las credenciales no coinciden.'])->withInput();
                    }
                    $user = auth()->user();
                } else {                    
                    $cleanIdentification = trim($request->identification);
                    $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
                    $fullPhone = $request->country_code ? $request->country_code . $cleanPhone : '+57' . $cleanPhone;

                    $user = User::create([
                        'name'     => trim($request->name),
                        'email'    => trim($request->email),
                        'password' => Hash::make($cleanIdentification),
                        'role'     => 'patient',
                    ]);

                    // ESTO ES LO QUE LE ASIGNA EL ROL EN SPATIE:
                    $role = Role::firstOrCreate(['name' => 'patient']);
                    $user->assignRole($role);

                    //aqui valido si tiene patient
                    $existingPatient = Patient::where('user_id', $user->id)->first();
                    if (!$existingPatient) {
                        // Crear el registro en la tabla patients usando solo el ID generado                        
                        Patient::create([
                            'user_id' => $user->id,
                            'identification' => $cleanIdentification,
                            'phone'          => $fullPhone,
                        ]);                        
                    }
                    
                    Auth::login($user);
                }
            } else {
                $user = auth()->user();
            }

            // 2. 🛡️ CONTROL EXTRA DE SEGURIDAD POST-AUTENTICACIÓN
            if ($user->role !== 'patient') {
                return back()->with('error', 'Operación cancelada. El usuario autenticado debe tener exclusivamente el rol de paciente.');
            }

            // Capturar la relación del perfil del paciente en la base de datos
            $patient = Patient::where('user_id', $user->id)->firstOrFail();

            // Buscar al doctor validando que su cuenta e ID de usuario sigan activos en el ecosistema
            $doctor = Doctor::with(['settings', 'user'])
                ->where('user_id', $bookingData['doctor_id'])
                ->first();

            if (!$doctor) {
                session()->forget(['booking_data', 'current_doctor_id']);
                return redirect()->route('search')->with('error', 'El médico seleccionado ya no se encuentra disponible.');
            }

            // Cargar la sede física validando el servicio y los precios/duración de su tabla pivote
            $address = Address::with(['clinic.settings', 'services' => function($q) use ($bookingData) {
                $q->where('services.id', $bookingData['service_id']);
            }])->find($bookingData['address_id']);

            $serviceSpecific = $address?->services->first();

            if (!$serviceSpecific || !$serviceSpecific->pivot) {
                return redirect()->route('search')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
            }
            
            // Extraer métricas comerciales de la relación de la sucursal
            $duration = (int) $serviceSpecific->pivot->duration;
            $price = $serviceSpecific->pivot->price;
            
            // Calcular cronología exacta de inicio y finalización del turno médico
            $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
            $endTime = $startTime->copy()->addMinutes($duration);

            // 🔒 VALIDACIÓN CONCURRENTE EN SERVIDOR: Evitar sobreventa si otro usuario reservó en paralelo
            $isAvailable = $this->appointmentService->isAvailable(
                $doctor->id,
                $bookingData['date'],
                $startTime->format('H:i:s'),
                $duration
            );
            
            if (!$isAvailable) {
                return redirect()->route('search')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente de forma simultánea.');
            }
            
            // Resolver las políticas de aprobación según la jerarquía de la sede (Clínica o Particular)
            $settings = $address->clinic_id ? $address->clinic->settings : $doctor->settings;

            $requiresApproval = $settings ? (bool)$settings->requires_approval : false;
            $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;

            // Si la sede acepta pasarelas de pago en línea, la cita se confirma automáticamente al pagar
            if ($acceptsPayments) {
                $requiresApproval = false; 
            }
            
            //esta regla es del plan
            $status = $requiresApproval ? 'pending' : 'confirmed';

            //se coloca pending hasta que el usuario no confirme la cita en el paso de review
            $status = 'pending';
                        
            // 💾 GUARDADO FÍSICO DE LA CITA EN TU TABLA APPOINTMENTS
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id'  => $doctor->id,
                'clinic_id'  => $address->clinic_id, // Guarda automáticamente el rastro de la clínica si aplica
                'service_id' => $serviceSpecific->id,
                'address_id' => $address->id,
                'date'       => $bookingData['date'],
                'start_time' => $startTime->format('H:i:s'),
                'end_time'   => $endTime->format('H:i:s'),
                'duration'   => $duration,
                'price'      => $price,
                'status'     => $status,
                'channel'    => 'web',
                'notes'      => trim($request->notes),
            ]);
            
            // Limpieza absoluta de los mapas temporales de sesión
            session()->forget(['booking_data', 'current_doctor_id']);

            // Redirección exitosa hacia tu método preview pasándole el ID de la cita guardada
            return redirect()->route('appointments.preview', ['id' => $appointment->id])
                ->with('success', $status === 'pending' ? 'Cita solicitada. Esperando aprobación de la administración.' : 'Cita agendada exitosamente.');
        });
    }
   
    /**
     * 🔥 NUEVA FUNCIÓN: Renderiza la pantalla de resumen de la orden médica.
     * Recibe el ID de la URL y precarga las dependencias del SaaS.
     */
    public function preview($id)
    {
        // Buscamos la cita médica con todas sus relaciones unificadas
        $appointment = Appointment::with(['doctor.user', 'clinic', 'service', 'address.city'])->findOrFail($id);

        // Despachamos la vista compactando el objeto
        return view('appointments.preview', compact('appointment'));
    }

    /**
     * Muestra la pantalla de confirmación exitosa de la cita médica validando la tenencia del recurso.
     */    
    public function success(Appointment $appointment)
    {
        $activeUser = auth()->user();

        // 1. Cargar todas las relaciones necesarias desde el inicio para evitar consultas N+1
        $appointment->load(['doctor.user', 'clinic', 'service', 'address.city', 'patient']);

        // 🔒 BLINDAJE DE SEGURIDAD SaaS
        if ($activeUser && $appointment->patient && $appointment->patient->user_id === $activeUser->id) {
            // Acceso concedido automáticamente por propiedad directa
        } else {
            // Si no es el dueño directo en caliente, ejecutamos los filtros SaaS tradicionales de forma fresca
            $activeUser = $activeUser->fresh(['patient', 'doctor', 'clinic']);

            if ($activeUser->role === 'patient') {
                $patientProfile = $activeUser->patient;
                if (!$patientProfile || $appointment->patient_id !== $patientProfile->id) {
                    abort(403, 'Acceso no autorizado a este recibo de consulta médica.');
                }
            }
        }

        if ($activeUser->role === 'doctor' && $appointment->doctor_id !== $activeUser->doctor?->id) {
            abort(403, 'No tienes privilegios para auditar esta transacción.');
        }

        if ($activeUser->role === 'clinic' && $appointment->clinic_id !== $activeUser->clinic?->id) {
            abort(403, 'Esta cita no corresponde al registro transaccional de tu institución.');
        }

        // 2. GENERACIÓN AUTOMÁTICA DE ZOOM (Basada en tu validación de servicio)
        if ($appointment->service->type === 'virtual' && !$appointment->hasZoom()) {
            
            // Fusionamos fecha y hora en el formato ISO requerido por Zoom
            $onlyDate = substr($appointment->date, 0, 10); 
            $startDateTime = Carbon::parse("{$onlyDate} {$appointment->start_time}")
                ->format('Y-m-d\TH:i:s');
                
            $topic = "Teleconferencia Médica - Ref: " . $appointment->reference;

            $zoomMeeting = $this->zoomService->createMeeting($topic, $startDateTime, $appointment->duration);

            if ($zoomMeeting) {
                // Actualizamos la instancia directamente para que la vista reciba los cambios
                $appointment->update([
                    'zoom_meeting_id'       => $zoomMeeting['meeting_id'],                    
                    'zoom_start_url'        => Crypt::encryptString($zoomMeeting['url_partner']),   // Llave real de tu ZoomService
                    'meeting_link'          => Crypt::encryptString($zoomMeeting['url_patient']), // Llave real de tu ZoomService    
                    'meeting_link_password' => Crypt::encryptString($zoomMeeting['password']),                
                ]);
            } else {
                Log::error('ZoomService Error: ' . $appointment->reference);                
                // Usamos firstOrCreate para evitar duplicados si el usuario reintenta manualmente
                ZoomCreationFailure::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => 0,
                        'status' => 'pending',
                        'last_error' => '[' . now()->toDateTimeString() . '] Error inicial en ZoomService al crear el link.'
                    ]
                );
            }
        }

        $appointment->update(['status' => 'confirmed']);

        $appointment = $appointment->fresh(['doctor.user', 'clinic', 'service', 'address.city', 'patient']);

        // 3. ENVÍO DE CORREO ELECTRÓNICO
        if (!$appointment->email_sent) {
            try {
                // Al enviar $appointment, el correo ya incluirá los links generados arriba descifrados por tus Accessors
                Mail::to($activeUser->email)->send(new AppointmentConfirmed($appointment));
                $appointment->update(['email_sent' => true]);
            } catch (Throwable $e) {
                $admins = User::where('role', 'admin')->get();
                Notification::send($admins, new MailLimitExceededNotification($e->getMessage(), $activeUser->email));
            }
        }            
        
        return view('appointments.success', compact('appointment'));
    }

    /**
     * Cancela el flujo de reserva, valida la propiedad del paciente y elimina el registro de forma segura.
     */
    public function cancelFlow(Request $request)
    {
        $appointmentId = $request->input('id');
        $user = auth()->user(); // Capturamos el usuario logueado en la sesión

        if ($appointmentId) {
            // Buscamos la cita médica con su relación de paciente
            $appointment = Appointment::with('patient')->find($appointmentId);
            
            if ($appointment) {
                // 🛡️ BARRERA DE SEGURIDAD EXCLUSIVA DEL SAAS:
                // Si el usuario es un paciente, validamos que el patient_id de la cita coincida con SU id de paciente.
                // Si es un usuario invitado (Guest), validamos a través del ID de sesión temporal.
                if ($user && $user->role === 'patient') {
                    if ($appointment->patient->user_id !== $user->id) {
                        abort(403, 'Operación no autorizada. No tienes permisos sobre esta orden médica.');
                    }
                }

                // Si la validación es exitosa, se procede al borrado físico de la cita fantasma
                $appointment->delete(); 
            }
        }

        // Limpieza absoluta de los mapas de datos de la sesión
        session()->forget(['booking_data', 'current_doctor_id']);

        return redirect()->route('search')
            ->with('info', 'Proceso de reserva cancelado con éxito. El horario ha sido liberado.');
    }

    /**
     * Renderiza la sala de telemedicina incrustada dentro de OpenDoctor
     */
    public function joinRoom(Appointment $appointment, ZoomService $zoomService)
    {
        // 1. Validar que la cita tenga un ID de reunión generado
        if (!$appointment->zoom_meeting_id) {
            return redirect()->route('dashboard')->with('error', 'Esta cita no tiene una videollamada activa.');
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
