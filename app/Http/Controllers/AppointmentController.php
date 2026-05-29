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
use Illuminate\Support\Facades\Log;
use App\Notifications\MailLimitExceededNotification;

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
     * Busca una reservación por su referencia filtrando según el rol del usuario.
     */
    public function searchByReference(Request $request)
    {
        // 1. Validar la entrada
        $request->validate([
            'reference' => 'required|string|max:20',
        ]);

        $user = Auth::user();
        $reference = strtoupper(trim($request->reference));

        // 2. Consulta base con relaciones optimizadas
        $query = Appointment::where('reference', $reference)
            ->with(['address.clinic', 'address.doctor', 'service', 'user']);

        // 3. Aplicar reglas de seguridad por Rol (Multi-tenancy & Multi-profile)
        switch ($user->role) {
            case 'clinic':
                $query->whereHas('address', function ($q) use ($user) {
                    $q->where('clinic_id', $user->clinic->id);
                });
                break;

            case 'doctor':
                $query->whereHas('address', function ($q) use ($user) {
                    $q->where('doctor_id', $user->doctor->id);
                });
                break;

            case 'patient':
                $query->where('user_id', $user->id);
                break;

            case 'admin':
                // El administrador global no tiene filtros, puede ver todo.
                break;

            default:
                abort(403, 'Rol no autorizado.');
        }

        // 4. Ejecutar la consulta
        $appointment = $query->first();

        // 5. Validar existencia y pertenencia
        if (!$appointment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se encontró la reservación o no tiene permisos para verla.');
        }

        // 6. Redirección a vistas según el perfil
        if (in_array($user->role, ['doctor', 'clinic', 'admin'])) {
            return view('appointments.show-internal', compact('appointment'));
        }

        return view('admin.appointments.show', compact('appointment', 'user'));
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
     * Almacena temporalmente en sesión los datos de la cita elegida por el paciente.
     */
    public function storeStepTwo(Request $request) 
    {
        // 1. Extraemos los identificadores unificados congelados previamente
        $doctorId = session('current_doctor_id');
        $clinicUserId = session('current_clinic_user_id');

        // 🔒 BLINDAJE MULTI-TENANT: Validamos que exista al menos una entidad comercial activa
        if (!$doctorId && !$clinicUserId) {
            return [
                "message" => "No se ha detectado una clínica o especialista seleccionado para procesar la reserva.",
                "status"  => false,
            ];
        }

        $clinicId = null;
        if ($clinicUserId) {
            $clinicProfile = Clinic::where('user_id', $clinicUserId)->first();
            $clinicId = $clinicProfile?->id;
        }

        $targetDoctorId = $request->input('doctor_id', $doctorId);

        // 🛠️ OPTIMIZACIÓN: Limpieza absoluta de intentos de reserva previos
        session()->forget('booking_data');

        // 2. Empaquetamos el nuevo objeto estructurado de reserva de forma limpia y aislada
        session([
            'booking_data' => [
                'clinic_id'  => $clinicId, 
                'doctor_id'  => $targetDoctorId, 
                'service_id' => $request->service_id,
                'address_id' => $request->address_id,
                'date'       => $request->date,
                'hour'       => $request->hour,
            ]
        ]);

        return [
            "message" => "Información de reserva (booking_data) configurada correctamente en el ecosistema.",
            "status"  => true,
        ];
    }

    public function patient()
    {
        if (!session()->has('booking_data')) {
            return redirect()->route('search');
        }

        // 1. Extraer los datos estructurados de la sesión
        $bookingData = session('booking_data');
        $addressId = $bookingData['address_id'] ?? null;

        // 2. Variable por defecto
        $isVirtualAddress = false;

        // 3. Consultar la base de datos si existe el ID de la sede
        if ($addressId) {
            $address = Address::find($addressId);
            
            if ($address) {
                // Evaluamos la columna de tu tabla. Ajusta según el nombre exacto de tu campo:
                // Opción A (Booleano): $address->is_virtual
                // Opción B (Enum/Texto): $address->type === 'virtual'
                $isVirtualAddress = (bool) $address->is_virtual; 
            }
        }

        $doctorId = session('current_doctor_id');
        $userId = auth()->id();
        
        $isSelfBooking = ($doctorId === $userId);
        
        // 4. Inyectamos la variable a la vista
        return view('appointments.patient', compact('isSelfBooking', 'isVirtualAddress'));
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

            // 2. CONTROL EXTRA DE SEGURIDAD POST-AUTENTICACIÓN
            if ($user->role !== 'patient') {
                return back()->with('error', 'Operación cancelada. El usuario autenticado debe tener exclusivamente el rol de paciente.');
            }

            $patient = Patient::where('user_id', $user->id)->first();

            $doctor = Doctor::with(['settings', 'user'])->where('user_id', $bookingData['doctor_id'])->first();
            if (!$doctor) {
                session()->forget(['booking_data', 'current_doctor_id']);
                return redirect()->route('search')->with('error', 'El médico seleccionado ya no se encuentra disponible.');
            }

            $address = Address::with(['clinic.settings', 'services' => function($q) use ($bookingData) {
                $q->where('services.id', $bookingData['service_id']);
            }])->find($bookingData['address_id']);

            $serviceSpecific = $address?->services->first();

            if (!$serviceSpecific || !$serviceSpecific->pivot) {
                return redirect()->route('search')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
            }
            
            $duration = (int) $serviceSpecific->pivot->duration;
            $price = $serviceSpecific->pivot->price;
            $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
            $endTime = $startTime->copy()->addMinutes($duration);

            $isAvailable = $this->appointmentService->isAvailable(
                $doctor->id,
                $bookingData['date'],
                $startTime->format('H:i:s'),
                $duration
            );
            
            if (!$isAvailable) {
                return redirect()->route('search')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente.');
            }
            
            if ($address->clinic_id) {
                $settings = $address->clinic->settings;
            } else {
                $settings = $doctor->settings;
            }

            $requiresApproval = $settings ? (bool)$settings->requires_approval : false;
            $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;

            if ($acceptsPayments) {
                $requiresApproval = false; 
            }

            $status = $requiresApproval ? 'pending' : 'confirmed';
                        
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id'  => $doctor->id,
                'clinic_id'  => $address->clinic_id, 
                'service_id' => $serviceSpecific->id,
                'address_id' => $address->id,
                'date'       => $bookingData['date'],
                'start_time' => $startTime->format('H:i:s'),
                'end_time'   => $endTime->format('H:i:s'),
                'duration'   => $duration,
                'price'      => $price,
                'status'     => $status,
                'channel'    => 'web',
                'notes'      => $request->notes,
            ]);

            if ($address->is_virtual || $serviceSpecific->type === 'virtual') {
                $appointment->update([
                    'meeting_link' => url('/meet/' . Str::random(10))
                ]);
            }

            session()->forget(['booking_data', 'current_doctor_id']);

            return redirect()->route('appointments.preview', ['id' => $appointment->id])
                ->with('success', $status === 'pending' ? 'Cita solicitada. Esperando aprobación.' : 'Cita agendada exitosamente.');
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

        // 🔒 BLINDAJE DE EMERGENCIA: Si el usuario actual coincide con el creador del registro del usuario
        // de la cita, le permitimos el acceso directo saltando la caché de relaciones de Spatie/Eloquent.
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

        // Filtros para médicos y clínicas permanecen igual...
        if ($activeUser->role === 'doctor' && $appointment->doctor_id !== $activeUser->doctor?->id) {
            abort(403, 'No tienes privilegios para auditar esta transacción.');
        }

        if ($activeUser->role === 'clinic' && $appointment->clinic_id !== $activeUser->clinic?->id) {
            abort(403, 'Esta cita no corresponde al registro transaccional de tu institución.');
        }

        $appointment->load(['doctor.user', 'clinic', 'service', 'address.city']);

        return view('appointments.success', compact('appointment'));
    }

    /**
     * Cancela el flujo de reserva actual y limpia la sesión.
     */
    public function cancelFlow()
    {
        // Limpiamos los datos del embudo de reserva
        session()->forget(['booking_data', 'current_doctor_id']);

        return redirect()->route('search')
            ->with('info', 'Proceso de reserva cancelado. Puedes iniciar una nueva búsqueda.');
    }

}
