<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Patient;
use App\Models\Setting;
use Spatie\Permission\Models\Role;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\User;
use App\Models\DoctorPayout;
use App\Models\PromoCode;
use App\Services\AppointmentService;
use App\Services\ZoomService;
use App\Services\Wompi\WompiService;
use App\Services\Twilio\WhatsAppTemplateService;
use App\Jobs\ExpireUnpaidAppointment;
use App\Jobs\SendSmsReminder;
use App\Mail\AppointmentConfirmed;
use App\Mail\VirtualRoomAccessMail;
use App\Mail\PaymentFailedMail;
use App\Notifications\MailLimitExceededNotification;
use App\Http\Requests\SearchAppointmentByReferenceRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\StoreAppointmentStepTwoRequest;
use App\Http\Requests\ProcessPatientAppointmentRequest;
use App\Http\Requests\CancelAppointmentFlowRequest;
use App\Enums\PaymentStatus;
use App\Enums\AppointmentStatus;
use App\Services\Scheduling\SlotOverlapChecker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Throwable;

class AppointmentController extends Controller
{
    use \App\Traits\SendsReminderSms;

    protected AppointmentService $appointmentService;
    protected ZoomService $zoomService;
    protected $whatsapp;
    protected WompiService $wompi;

    public function __construct(
        AppointmentService $service, 
        ZoomService $zoomService, 
        WhatsAppTemplateService $whatsapp, 
        WompiService $wompi
    ) {
        $this->appointmentService = $service;
        $this->zoomService = $zoomService;
        $this->whatsapp = $whatsapp;
        $this->wompi = $wompi;
    }

    protected function getOwner()
    {
        $user = Auth::user();
        if (!$user) return null;
        return $user->role === 'clinic' ? $user->clinic : $user->doctor;
    }

    public function searchByReference(SearchAppointmentByReferenceRequest $request)
    {
        $user = Auth::user();
        $reference = strtoupper(trim($request->query('reference')));

        $query = Appointment::where('reference', $reference)
            ->with(['address', 'service', 'patient.user', 'doctor.user', 'clinic']);

        switch ($user->role) {
            case 'clinic':
                $query->where('clinic_id', $user->clinic->id);
                break;
            case 'doctor':
                $query->where('doctor_id', $user->doctor->id);
                break;
            case 'patient':
                $query->whereHas('patient', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                break;
            case 'admin':
                break;
            default:
                return response()->json(['error' => 'Rol de usuario no autorizado en la plataforma.'], 403);
        }

        $appointment = $query->first();

        if (!$appointment) {
            return response()->json([
                'error' => "No se encontró ninguna cita médica con la referencia '{$reference}' o no cuentas con los permisos requeridos."
            ], 404);
        }

        return response()->json([
            'reference'   => $appointment->reference,
            'patient'     => $appointment->patient?->user?->name ?? 'Paciente no registrado',
            'doctor'      => $appointment->doctor?->user?->name ?? 'Profesional no asignado',
            'clinic'      => $appointment->clinic?->name ?? null,
            'service'     => $appointment->service?->name ?? 'Consulta General',
            'type'        => $appointment->service?->type ?? 'physical',
            'date'        => $appointment->date->format('d/m/Y'), // ✅ Seguro gracias al cast del modelo
            'time'        => Carbon::parse($appointment->start_time)->format('g:i A'),
            'duration'    => $appointment->duration,
            'price'       => number_format($appointment->price, 2) . ' COP',
            'status'      => $appointment->status->value,
            'address'     => $appointment->address?->name ?? 'Sede Virtual / Telemedicina',
            'notes'       => $appointment->notes ?? 'El paciente no registró síntomas o notas adicionales.'
        ], 200);
    }
    
    public function store(StoreAppointmentRequest $request)
    {
        $patient = DB::table('patients')->where('user_id', Auth::id())->first();
        if (!$patient) {
            return redirect()->back()->withErrors(['error' => 'No se encontró un perfil de paciente válido vinculado a tu cuenta.']);
        }

        $service = Service::findOrFail($request->service_id);
        $address = Address::findOrFail($request->address_id);
        $doctorId = (int) $request->input('doctor_id');
        $dateInput = $request->input('date');
        $startTime = Carbon::parse($request->start_time)->format('H:i:s');

        $pivotDuration = DB::table('address_service')
            ->where('address_id', $address->id)
            ->where('service_id', $service->id)
            ->value('duration') ?? 20;

        $endTime = Carbon::parse($startTime)->addMinutes((int)$pivotDuration)->format('H:i:s');

        // 🔒 ESCUDO DE CONCURRENCIA (delegado al checker centralizado)
        $isAlreadyBooked = SlotOverlapChecker::overlapsWithAppointments(
            doctorId: $doctorId,
            date: $dateInput,
            newStart: $startTime,
            newEnd: $endTime,
            statuses: ['pending', 'confirmed', 'completed']
        );

        if ($isAlreadyBooked) {
            return redirect()->route('partner.public.profile', $request->input('doctor_slug', 'search'))
                ->with('error', 'El turno seleccionado fue reservado por otro paciente. Por favor, elige uno nuevo.');
        }

        $appointmentData = [
            'patient_id'     => (int) $patient->id,
            'doctor_id'      => $doctorId,
            'clinic_id'      => $address->clinic_id ? (int) $address->clinic_id : ($request->filled('clinic_id') ? (int) $request->clinic_id : null),
            'service_id'     => (int) $service->id,
            'address_id'     => (int) $address->id,
            'date'           => $dateInput,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'status'         => AppointmentStatus::CONFIRMED->value, 
            'payment_status' => PaymentStatus::PENDING->value,
        ];

        // 1. ✅ CREAR LA CITA PRIMERO (Para tener el ID válido antes de cualquier fallo de API)
        $appointment = Appointment::create($appointmentData);

        // 2. TELEMEDICINA: Intentar crear sala de Zoom
        if ($service->type === 'virtual' || $address->type === 'virtual') {
            try {                
                $startDateTime = Carbon::parse($dateInput . ' ' . $startTime)->toIso8601String();
                $zoomMeeting = $this->zoomService->createMeeting("Telemedicina_Ref: {$service->name}", $startDateTime, (int) $pivotDuration);

                if ($zoomMeeting) {
                    $appointment->update([
                        'zoom_meeting_id' => $zoomMeeting['id'],
                        'zoom_start_url'  => $zoomMeeting['start_url'],
                        'zoom_join_url'   => $zoomMeeting['join_url'],
                        'meeting_link'    => $zoomMeeting['join_url'],
                    ]);
                }
            } catch (\Exception $e) {
                // ✅ AHORA SÍ tenemos el $appointment->id para registrar el fallo de forma segura
                DB::table('zoom_creation_failures')->insert([
                    'appointment_id' => $appointment->id,
                    'error_log'      => $e->getMessage(),
                    'created_at'     => now(),
                    'updated_at'     => now()
                ]);
            }
        }
        
        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        return redirect()->route('appointments.success', $appointment->id);
    }

    public function storeStepTwo(StoreAppointmentStepTwoRequest $request) 
    {
        $addressId = (int) $request->input('address_id');
        $dateInput = $request->input('date');
        $hourInput = $request->input('hour');
        $address = Address::findOrFail($addressId);

        $targetDoctorId = (int) $request->input('doctor_id');

        if (!$targetDoctorId) {
            $sessionDoctorUserId = session('current_doctor_id');
            if ($sessionDoctorUserId) {
                $targetDoctorId = DB::table('doctors')->where('user_id', $sessionDoctorUserId)->value('id');
            }
        }

        if (!$targetDoctorId && $address->clinic_id) {
            $targetDoctorId = DB::table('schedules')
                ->where('address_id', $addressId)
                ->where('day', Carbon::parse($dateInput)->dayOfWeekIso)
                ->value('doctor_id');
        }

        $targetClinicId = (int) $request->input('clinic_id');
        if (!$targetClinicId && $address->clinic_id) {
            $targetClinicId = $address->clinic_id;
        }

        if (!$targetDoctorId) {
            return response()->json(["message" => "No se pudo identificar al especialista responsable.", "status" => false], 422);
        }

        $availableSlots = $this->appointmentService->getAvailableSlots(
            $addressId, $dateInput, $targetDoctorId, $address->type === 'virtual', (int) $request->service_id
        );

        $formattedRequestedHour = Carbon::parse($hourInput)->format('g:i A');
        $slotStillAvailable = collect($availableSlots)->contains('time', $formattedRequestedHour);

        if (!$slotStillAvailable) {
            return response()->json(["message" => "El turno seleccionado acaba de ser reservado por otro paciente.", "status" => false], 409);
        }

        session()->forget('booking_data');
        session([
            'booking_data' => [
                'clinic_id'  => $targetClinicId ?: null, 
                'doctor_id'  => $targetDoctorId, 
                'service_id' => (int) $request->service_id,
                'address_id' => $addressId,
                'date'       => $dateInput,
                'hour'       => $hourInput,
            ]
        ]);

        return response()->json(["message" => "Información de reserva configurada correctamente.", "status" => true]);
    }

    public function patient()
    {
        if (!session()->has('booking_data')) {
            return redirect()->to('/');
        }

        $bookingData = session('booking_data');
        $addressId = $bookingData['address_id'] ?? null;
        $targetDoctorId = $bookingData['doctor_id'] ?? null;
        $serviceId = $bookingData['service_id'] ?? null;
        $isVirtualAddress = false;

        if ($addressId) {
            $address = Address::with(['services' => function($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            }])->find($addressId);

            if ($address) {
                $hasVirtualService = $address->services->contains('type', 'virtual');
                $isVirtualAddress = ($address->type === 'virtual' || $hasVirtualService); 
            }
        }

        $authUserId = Auth::id();
        $isSelfBooking = false;

        if ($authUserId) {
            $user = Auth::user();
            if ($user->role === 'doctor' && $user->doctor) {
                $isSelfBooking = ($user->doctor->id === (int) $targetDoctorId);
            } elseif ($user->role === 'clinic' && $user->clinic && isset($bookingData['clinic_id'])) {
                $isSelfBooking = ($user->clinic->id === (int) $bookingData['clinic_id']);
            }
        }
        
        return view('appointments.patient', compact('isSelfBooking', 'isVirtualAddress', 'bookingData'));
    }

    public function processPatient(ProcessPatientAppointmentRequest $request)
    {        
        $bookingData = session('booking_data');
        if (!$bookingData || !isset($bookingData['doctor_id'])) {
            Log::warning('No hay bookingData en processPatient');
            return redirect()->to('/')->with('error', 'Sesión inválida o datos de reserva incompletos.');
        }

        $hasAccount = $request->has_account == 'yes';

        if (Auth::check() && Auth::user()->role !== 'patient') {
            return redirect()->back()->withInput()->with('error', 'Tu cuenta actual pertenece al personal del sistema. No tienes permisos para registrar citas como paciente.');
        }

        if (Auth::guest() && $hasAccount) {
            $login_email = trim(strtolower($request->login_email));
            $login_password = $request->login_password;
            
            $targetUser = User::where('email', $login_email)->first();
            if ($targetUser && $targetUser->role !== 'patient') {
                return back()->withErrors(['login_email' => 'Esta cuenta pertenece al personal del sistema.'])->withInput();
            }

            if (!Auth::attempt(['email' => $login_email, 'password' => $login_password])) {
                return back()->withErrors(['login_email' => 'Las credenciales introducidas no coinciden.'])->withInput();
            }
        }

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

                $role = Role::firstOrCreate(['name' => 'patient']);
                $newUser->assignRole($role);

                Patient::create([
                    'user_id'        => $newUser->id,
                    'identification' => $cleanIdentification,
                    'phone'          => $fullPhone,
                ]);

                return $newUser;
            });
            Auth::login($user);
        }

        if ($user->role !== 'patient') {
            return back()->with('error', 'Operación cancelada. El usuario debe tener exclusivamente el rol de paciente.');
        }

        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        $doctor = Doctor::with(['settings', 'user'])->where('id', $bookingData['doctor_id'])->first();

        if (!$doctor) {
            session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);
            return redirect()->to('/')->with('error', 'El médico seleccionado ya no se encuentra disponible.');
        }

        $address = Address::with(['clinic.settings', 'services' => function($q) use ($bookingData) {
            $q->where('services.id', $bookingData['service_id']);
        }])->find($bookingData['address_id']);

        $serviceSpecific = $address?->services->first();

        if (!$serviceSpecific || !$serviceSpecific->pivot) {
            return redirect()->to('/')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
        }
        
        $duration = (int) $serviceSpecific->pivot->duration;
        $price = (float) $serviceSpecific->pivot->price;
        
        $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
        $endTime = $startTime->copy()->addMinutes($duration);

        $availableSlots = $this->appointmentService->getAvailableSlots(
            $address->id, $bookingData['date'], $doctor->id, 
            $address->type === 'virtual' || $serviceSpecific->type === 'virtual', $serviceSpecific->id
        );

        $formattedRequestedHour = Carbon::parse($bookingData['hour'])->format('g:i A');
        $isStillAvailable = collect($availableSlots)->contains('time', $formattedRequestedHour);
        
        if (!$isStillAvailable) {
            return redirect()->to('/')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente.');
        }
        
        $settings = $address->clinic_id ? $address->clinic->settings : $doctor->settings;
        $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;
        $requiresApproval = $acceptsPayments ? false : ($settings ? (bool)$settings->requires_approval : false);
        
        $status = $requiresApproval ? AppointmentStatus::PENDING->value : AppointmentStatus::CONFIRMED->value;
        $payment_status = PaymentStatus::PENDING->value;
                    
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

        if ($appointment->service->type === 'virtual') {
            dispatch(new ExpireUnpaidAppointment($appointment))->delay(now()->addHours(2));
        }
        
        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        return redirect()->route('appointments.preview', ['id' => $appointment->id]);
    }
   
    public function preview($id)
    {
        $appointment = Appointment::with(['doctor.user', 'clinic', 'service', 'address.city'])->findOrFail($id);

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'patient') {
                $patient = DB::table('patients')->where('user_id', $user->id)->first();
                if (!$patient || (int)$appointment->patient_id !== (int)$patient->id) abort(403, 'Acceso denegado.');
            } elseif ($user->role === 'doctor') {
                $doctor = DB::table('doctors')->where('user_id', $user->id)->first();
                if (!$doctor || (int)$appointment->doctor_id !== (int)$doctor->id) abort(403, 'Acceso denegado.');
            } elseif ($user->role === 'clinic') {
                $clinic = DB::table('clinics')->where('user_id', $user->id)->first();
                if (!$clinic || (int)$appointment->clinic_id !== (int)$clinic->id) abort(403, 'Acceso denegado.');
            }
        } else {
            abort(401, 'Debes iniciar sesión para visualizar el resumen de tu orden médica.');
        }

        $virtualPaymentRequired = $appointment->service->type === 'virtual';
        $wompiData = $virtualPaymentRequired ? $this->wompi->buildAppointmentCheckoutUrl($appointment) : null;
                
        return view('appointments.preview', compact('appointment', 'virtualPaymentRequired', 'wompiData'));
    }
   
    public function success(Appointment $appointment)
    {      
        $appointment->load(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user']);

        if (session()->has('pending_promo_code')
            && (float) session('pending_discount_amount') >= (float) $appointment->price
            && $appointment->payment_status->value !== PaymentStatus::PAID->value) {

            abort_unless($appointment->patient?->user?->id === auth()->id(), 403);

            $promoCode = PromoCode::where('code', session('pending_promo_code'))->first();
      
            if (!$promoCode || !$promoCode->isValid(auth()->id()) || !$this->consumePromo($promoCode)) {
                session()->forget(['pending_promo_code', 'pending_discount_amount']);
                return redirect()->route('home')->with('error', 'El código promocional ya no está disponible.');
            }

            $appointment->update([
                'promo_code'      => $promoCode->code,
                'discount_amount' => $appointment->price,
                'status'          => AppointmentStatus::CONFIRMED->value,
                'payment_status'  => PaymentStatus::PAID->value,
                'paid_at'         => now(),
            ]);
            $appointment->refresh();
            session()->forget(['pending_promo_code', 'pending_discount_amount']);
        }
        
        if ($appointment->service->type === 'virtual' && $appointment->payment_status->value !== PaymentStatus::PAID->value) {
            return redirect()->route('home')->with('error', 'Debes completar el pago para confirmar tu cita virtual.');
        }

        $activeUser = Auth::user();
        $comingFromPayment = $appointment->paid_at && $appointment->paid_at->diffInMinutes(now()) <= 10;

        if (!$activeUser && !$comingFromPayment) {
            abort(401, 'Debes iniciar sesión para visualizar el comprobante de tu cita.');
        }

        if ($activeUser && !$comingFromPayment) {
            $hasAccess = false;
            if ($activeUser->role === 'patient' && $appointment->patient && (int)$appointment->patient->user_id === (int)$activeUser->id) $hasAccess = true;
            elseif ($activeUser->role === 'doctor' && $appointment->doctor && (int)$appointment->doctor_id === (int)$activeUser->doctor?->id) $hasAccess = true;
            elseif ($activeUser->role === 'clinic' && (int)$appointment->clinic_id === (int)$activeUser->clinic?->id) $hasAccess = true;

            if (!$hasAccess) abort(403, 'Acceso no autorizado a este comprobante.');
        }

        $appointment = $this->processPostConfirmation($appointment, $activeUser);
        
        return view('appointments.success', compact('appointment'));
    }

    private function consumePromo(PromoCode $promo): bool
    {
        return PromoCode::whereKey($promo->id)
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'))
            ->increment('uses') > 0;
    }

    public function cancelFlow(CancelAppointmentFlowRequest $request)
    {
        $appointmentId = $request->integer('id');
        $user = Auth::user(); 

        if ($appointmentId > 0) {
            $appointment = Appointment::with('patient')->find($appointmentId);
            
            if ($appointment) {
                $isAuthorized = false;

                if ($user) {
                    if ($user->role === 'patient' && $appointment->patient) {
                        $isAuthorized = ((int)$appointment->patient->user_id === (int)$user->id);
                    } elseif ($user->role === 'clinic') {
                        $isAuthorized = ((int)$appointment->clinic_id === (int)$user->clinic?->id);
                    } elseif ($user->role === 'doctor') {
                        $isAuthorized = ((int)$appointment->doctor_id === (int)$user->doctor?->id);
                    }
                }

                if (!$isAuthorized) {
                    abort(403, 'Operación no autorizada. No tienes propiedad sobre esta orden médica.');
                }

                // ✅ GRACIAS AL MODELO CON SOFTDELETES, ESTO AHORA HACE UN BORRADO LÓGICO (AUDITABLE)
                $appointment->delete(); 
            }
        }
        
        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        return redirect()->route('search')->with('info', 'Proceso de reserva cancelado con éxito. El horario de atención ha sido liberado.');
    }
    
    public function joinRoom(Appointment $appointment, ZoomService $zoomService)
    {
        if (!$appointment->zoom_meeting_id) {
            return redirect()->route('admin.dashboard')->with('error', 'Esta cita no tiene una videollamada activa.');
        }

        try {
            $user = auth()->user();
            $meetingId = $appointment->zoom_meeting_id;
            
            $isDoctor = $user->id === $appointment->doctor_id || auth()->user()->hasRole('doctor');
            $role = $isDoctor ? 1 : 0;

            try {
                $password = Crypt::decryptString($appointment->meeting_link_password ?? '');
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $password = $appointment->meeting_link_password ?? ''; 
            }
            
            $signature = $zoomService->generateSdkSignature($meetingId, $role);

            return view('appointments.room', [
                'appointment' => $appointment,
                'meetingId'   => $meetingId,
                'password'    => $password,
                'signature'   => $signature,
                'sdkKey'      => config('services.zoom.client_id'),
                'userRole'    => $role,
                'zoomStarted' => $appointment->zoom_started,
            ]);

        } catch (\Exception $e) {
            Log::error("Error en joinRoom (Cita ID {$appointment->id}): " . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', 'No se pudieron recuperar las llaves de acceso.');
        }
    }

    public function startZoom(Appointment $appointment)
    {
        $appointment->update(['zoom_started' => true]);

        $patientEmail = $appointment->patient?->user?->email;
        $patientName = $appointment->patient?->user?->name;

        if ($patientEmail) {
            $roomUrl = route('appointments.room', ['appointment' => $appointment->id]);
            Mail::to($patientEmail)->send(new VirtualRoomAccessMail($patientName, $roomUrl, $appointment->id));            
        }

        return response()->json(['success' => true]);
    }

    public function forceEndMeeting(Appointment $appointment)
    {
        if (!$appointment->zoom_meeting_id) {
            return response()->json(['status' => 'ignored', 'message' => 'La cita no posee una sala de Zoom activa.'], 400);
        }

        try {
            $success = $this->zoomService->endMeeting($appointment->zoom_meeting_id);
            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'La reunión de Zoom ha sido finalizada con éxito.'], 200);
            }
            return response()->json(['status' => 'error', 'message' => 'Zoom no pudo procesar la orden de cierre.'], 500);
        } catch (\Exception $e) {
            Log::error("Error forzando cierre de Zoom para cita {$appointment->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Ocurrió un fallo en el servidor.'], 500);
        }
    }

    public function getStatus(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'status' => $appointment->status->value,
            'completed_at' => $appointment->updated_at
        ]);
    }

    public function saveNotes(Request $request, Appointment $appointment)
    {
        $appointment->update(['notes' => $request->input('notes')]);
        return response()->json(['success' => true]);
    }
    
    public function endZoomMeeting(Appointment $appointment, ZoomService $zoomService): JsonResponse
    {
        // 1. Validar que exista una reunión de Zoom vinculada
        if (!$appointment->zoom_meeting_id) {
            // Si no hay zoom, igual podemos marcarla como completada localmente
            $appointment->update(['status' => AppointmentStatus::COMPLETED->value]);
            return response()->json([
                'success' => true, 
                'message' => 'La consulta ha sido marcada como finalizada.'
            ]);
        }

        // 2. Intentar cerrar la reunión en Zoom
        $closedInZoom = $zoomService->endMeeting($appointment->zoom_meeting_id);

        if ($closedInZoom) {
            // 3. Éxito: Zoom cerró la reunión, actualizamos nuestra BD
            $appointment->update(['status' => AppointmentStatus::COMPLETED->value]);
            
            return response()->json([
                'success' => true, 
                'message' => 'La videollamada fue finalizada con éxito.'
            ]);
        }

        // 4. FALLBACK DE RESILIENCIA: Si Zoom falla (ej: error 4711 de scopes), 
        // igual marcamos la cita como completada en NUESTRA base de datos para no bloquear al usuario.
        Log::warning('Zoom falló al cerrar la reunión, pero se marcó como completada localmente', [
            'appointment_id' => $appointment->id,
            'zoom_meeting_id' => $appointment->zoom_meeting_id,
        ]);

        $appointment->update(['status' => AppointmentStatus::COMPLETED->value]);

        return response()->json([
            'success' => true, // Devolvemos true al frontend para que el usuario pueda salir
            'message' => 'La consulta ha sido finalizada en el sistema (Nota: Ocurrió un problema de sincronización con Zoom).'
        ], 200);
    }

    public function paymentResult(Request $request)
    {        
        $transactionId = $request->query('id');
        if (!$transactionId) return redirect()->route('home')->with('error', 'No se recibió información del pago.');

        $response = Http::withToken(config('services.wompi.private_key'))
            ->get("https://production.wompi.co/v1/transactions/{$transactionId}");

        if (!$response->successful()) return redirect()->route('home')->with('error', 'No se pudo verificar el pago.');

        $transaction = $response->json('data');
        $reference   = $transaction['reference'];
        $status      = strtolower($transaction['status']);

        $appointment = Appointment::where('wompi_reference', $reference)->first();
        if (!$appointment) return redirect()->route('home')->with('error', 'No se encontró la cita asociada al pago.');
  
        if ($status === 'approved') {
            $appointment->update([
                'payment_status' => PaymentStatus::PAID->value,
                'paid_at'        => now(),
                'status'         => AppointmentStatus::CONFIRMED->value,
            ]);

            $payable = $appointment->clinic_id ? $appointment->clinic : $appointment->doctor;
            $wompiFee = Setting::where('key', 'wompi_fee')->value('value') ?? 2.9;                

            DoctorPayout::create([
                'appointment_id'      => $appointment->id,
                'payable_id'          => $payable->id,
                'payable_type'        => get_class($payable),
                'total_charged'       => $appointment->price + ($appointment->commission_amount ?? 0),
                'wompi_fee'           => round(($appointment->price + ($appointment->commission_amount ?? 0)) * ($wompiFee / 100), 2),
                'platform_commission' => $appointment->commission_amount ?? 0,
                'amount_to_pay'       => $appointment->doctor_amount ?? $appointment->price,
                'status'              => PaymentStatus::PENDING->value,
                'due_date'            => now()->addDays(3),
            ]);

            return redirect()->route('appointments.success', $appointment);
        }

        if ($status === 'declined' || $status === 'error') {
            $appointment->update(['payment_status' => PaymentStatus::PENDING->value]);
            $patientEmail = $appointment->patient?->user?->email;
            if ($patientEmail) {
                Mail::to($patientEmail)->send(new PaymentFailedMail($appointment));
            }
        }

        return view('appointments.payment-result', compact('appointment', 'transaction', 'status'));
    }

    protected function processPostConfirmation(Appointment $appointment, ?User $activeUser): Appointment
    {
        $hasCreationFailure = DB::table('zoom_creation_failures')->where('appointment_id', $appointment->id)->exists();
        $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');

        if ($isVirtual && !$appointment->zoom_meeting_id && !$hasCreationFailure) {
            try {
                DB::transaction(function () use ($appointment) {
                    $fresh = $appointment->newQuery()->lockForUpdate()->find($appointment->id);
                    if ($fresh->zoom_meeting_id) return;

                    // ✅ AHORA ES SEGURO: $fresh->date ya es un objeto Carbon gracias al cast del modelo
                    $startDateTime = Carbon::parse($fresh->date->format('Y-m-d') . ' ' . $fresh->start_time)->toIso8601String();
                    $zoomMeeting = $this->zoomService->createMeeting("Telemedicina_Ref: {$fresh->reference}", $startDateTime, (int) $fresh->duration);

                    if ($zoomMeeting) {
                        $fresh->update([
                            'zoom_meeting_id' => $zoomMeeting['meeting_id'] ?? $zoomMeeting['id'],
                            'meeting_link'    => $zoomMeeting['url_patient'] ?? $zoomMeeting['join_url'],
                            'zoom_start_url'  => $zoomMeeting['url_partner'] ?? $zoomMeeting['start_url'],
                        ]);
                        DB::table('zoom_creation_failures')->where('appointment_id', $fresh->id)->delete();
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error crítico Zoom en post-confirmación: ' . $e->getMessage());
            }
        }

        if (!$appointment->email_sent) {
            try {
                $patientEmail = $appointment->patient?->user?->email;
                $doctorEmail  = $appointment->doctor?->user?->email;

                if ($patientEmail) Mail::to($patientEmail)->send(new AppointmentConfirmed($appointment, 'patient'));
                if ($doctorEmail)  Mail::to($doctorEmail)->send(new AppointmentConfirmed($appointment, 'partner'));
            } catch (Throwable $e) {
                Log::error('Error en notificaciones post-confirmación: ' . $e->getMessage());
                try {
                    $admins = User::where('role', 'admin')->get();
                    Notification::send($admins, new MailLimitExceededNotification($e->getMessage(), $activeUser?->email));
                } catch (\Exception $ne) { /* silencioso */ }
            }         
            
            try {
                // ✅ LIMPIO Y SEGURO: Aprovechamos los casts del modelo
                $dateStr = $appointment->date->format('Y-m-d');
                $timeStr = (string)$appointment->start_time;
                $appointmentDateTime = Carbon::parse($dateStr . ' ' . $timeStr);
                
                $name = $appointment->patient?->user?->name ?? 'Paciente';
                $date = $appointmentDateTime->format('d/m/Y');
                $time = $appointmentDateTime->format('H:i');                
                $confirmationMessage = "Hola {$name}, tu cita fue confirmada para el {$date} a las {$time}";

                SendSmsReminder::dispatch(
                    $appointment->patient?->phone,
                    $confirmationMessage,
                    "appointment_{$appointment->reference}_confirmation"
                );

                $reminderMessage = "Recordatorio: Tu cita {$appointment->reference} comienza en 20 min.";
                $this->scheduleReminderBeforeEvent(
                    $appointment->patient?->phone,
                    $reminderMessage,
                    $appointmentDateTime,
                    20,
                    "appointment_{$appointment->reference}_reminder"
                );                
            } catch (\Exception $e) {
                Log::error('ERROR al programar recordatorios SMS', ['error' => $e->getMessage()]);
            }

            $appointment->update(['email_sent' => true]);
        }

        return $appointment->fresh(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user']);
    }

    public function validatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'appointment_id' => 'required|integer',
        ]);

        $code = strtoupper($validated['code']);
        $promoCode = PromoCode::where('code', $code)->first();

        if (!$promoCode || !$promoCode->isValid(auth()->id())) {
            return response()->json(['message' => 'El código promocional no es válido, está desactivado, expiró o superó el límite de usos.'], 422);
        }

        $appointment = Appointment::findOrFail($validated['appointment_id']);
        $basePrice = (float) $appointment->price;
        $originalCommission = (float) ($appointment->commission_amount ?? 0);
        $originalTotal = $basePrice + $originalCommission;

        if ($promoCode->type === 'percent') {
            $discountAmount = ($basePrice * $promoCode->reward) / 100;
        } else {
            $discountAmount = min($promoCode->reward, $basePrice);
        }

        $discountAmount = floor($discountAmount); 
        $newDoctorPrice = $basePrice - $discountAmount;
        $finalPrice = $newDoctorPrice + $originalCommission; 
        $wompiData = null;
        $virtualPaymentRequired = $appointment->service->type === 'virtual';

        if ($virtualPaymentRequired && $finalPrice > 0) {
            $clonedAppointment = $appointment->replicate();
            $clonedAppointment->price = $newDoctorPrice;
            $wompiData = $this->wompi->buildAppointmentCheckoutUrl($clonedAppointment);
            
            if (isset($wompiData['total'])) {
                $finalPrice = (float) $wompiData['total'];
            }
        }

        $realVisualDiscount = $originalTotal - $finalPrice;

        session([
            'pending_promo_code' => $promoCode->code,
            'pending_discount_amount' => $discountAmount
        ]);

        return response()->json([
            'success' => true,
            'code' => $promoCode->code,
            'type' => $promoCode->type,
            'reward' => $promoCode->reward,
            'discount_amount' => $realVisualDiscount,
            'base_price' => $originalTotal,
            'final_price' => $finalPrice,
            'wompiData' => $wompiData,
        ], 200);
    }
}