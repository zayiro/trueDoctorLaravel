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
use App\Models\PatientHistory;
use App\Services\AppointmentService;
use App\Services\ZoomService;
use App\Services\Wompi\WompiService;
use App\Services\Twilio\WhatsAppTemplateService;
use App\Services\Payments\AppointmentCheckout;
use App\Services\Appointments\AppointmentFinalizer;
use App\Payments\PaymentService;
use App\Models\Payment;
use App\Jobs\SendSmsReminder;
use App\Mail\AppointmentConfirmed;
use App\Mail\VirtualRoomAccessMail;
use App\Mail\PaymentFailedMail;
use App\Notifications\MailLimitExceededNotification;
use App\Http\Requests\SearchAppointmentByReferenceRequest;
use App\Http\Requests\StoreAppointmentStepTwoRequest;
use App\Http\Requests\ProcessPatientAppointmentRequest;
use App\Http\Requests\CancelAppointmentFlowRequest;
use App\Enums\PaymentStatus;
use App\Enums\AppointmentStatus;
use App\Services\Scheduling\SlotOverlapChecker;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotNotAvailableException;
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
use Illuminate\Support\Facades\Cache;
use App\Services\RoomSessionGuard;
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

        // Motor único de disponibilidad (la reserva definitiva vuelve a validar con candado en processPatient).
        $tz = config('booking.timezone', 'America/Bogota');
        $daySlots = app(AvailabilityResolver::class)->slotsForDay(
            new AvailabilityQuery(
                doctorIds: [(int) $targetDoctorId],
                clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
                addressId: $addressId,
                serviceId: (int) $request->service_id,
            ),
            Carbon::parse($dateInput, $tz)
        );

        $requestedTimestamp = Carbon::parse($dateInput . ' ' . $hourInput, $tz)->getTimestamp();
        $slotStillAvailable = collect($daySlots)->contains(
            fn ($slot) => $slot->isAvailable() && $slot->start->getTimestamp() === $requestedTimestamp
        );

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
        
        $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour'], config('booking.timezone', 'America/Bogota'));
        $endTime = $startTime->copy()->addMinutes($duration);

        $settings = $address->clinic_id ? $address->clinic->settings : $doctor->settings;
        $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;
        $requiresApproval = $acceptsPayments ? false : ($settings ? (bool)$settings->requires_approval : false);
        
        $isVirtual = $serviceSpecific->type === 'virtual' || $address->type === 'virtual';
        $onlineRequired = $isVirtual || $acceptsPayments;

        $status = $requiresApproval ? AppointmentStatus::PENDING->value : AppointmentStatus::CONFIRMED->value;
        $payment_status = PaymentStatus::PENDING->value;
        $paymentExpiresAt = null;
        $paidAt = null;

        if ($onlineRequired && $price <= 0) {
            // Servicio sin costo: no hay nada que cobrar.
            $status = AppointmentStatus::CONFIRMED->value;
            $payment_status = PaymentStatus::PAID->value;
            $paidAt = now();
        } elseif ($onlineRequired) {
            // El turno queda retenido hasta pagar; si no se paga, el barrido lo expira y lo libera.
            $status = AppointmentStatus::PENDING->value;
            $paymentExpiresAt = now()->addMinutes((int) config('booking.pending_hold_minutes', 120));
        }
                    
        // 🔒 Motor único de disponibilidad: valida el turno y crea la cita en la MISMA transacción,
        // con el doctor bloqueado (dos pacientes no pueden tomar el mismo turno).
        $availabilityQuery = new AvailabilityQuery(
            doctorIds: [(int) $doctor->id],
            clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
            addressId: (int) $address->id,
            serviceId: (int) $serviceSpecific->id,
        );

        try {
          $appointment = app(AvailabilityResolver::class)->withBookableSlot($availabilityQuery, $startTime, function () use ($patient, $doctor, $address, $serviceSpecific, $bookingData, $startTime, $endTime, $duration, $price, $status, $payment_status, $paymentExpiresAt, $paidAt, $request) {
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
                'payment_expires_at' => $paymentExpiresAt,
                'paid_at'        => $paidAt,
                'notes'          => trim($request->notes),                
            ]);
          });
        } catch (SlotNotAvailableException $e) {
            return redirect()->to('/')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente.');
        }

        session()->forget(['booking_data', 'current_doctor_id', 'current_clinic_user_id']);

        if ($onlineRequired && $price <= 0) {
            return redirect()->route('appointments.success', $appointment);
        }

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

        // Nombre heredado de la vista: ahora significa "esta cita se paga en línea" (virtual, o presencial con pagos en línea).
        $virtualPaymentRequired = AppointmentCheckout::requiresOnlinePayment($appointment);

        $paid = ($appointment->payment_status instanceof \BackedEnum ? $appointment->payment_status->value : (string) $appointment->payment_status) === PaymentStatus::PAID->value;
        if ($paid) {
            return redirect()->route('appointments.success', $appointment);
        }

        $status = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;
        if ($virtualPaymentRequired && in_array($status, ['expired', 'cancelled'], true)) {
            return redirect()->route('home')->with('error', 'Esta reserva ya no está disponible. Agenda una nueva cita.');
        }

        $wompiData = $virtualPaymentRequired ? app(AppointmentCheckout::class)->quote($appointment)->toArray() : null;
                
        return view('appointments.preview', compact('appointment', 'virtualPaymentRequired', 'wompiData'));
    }
   
    /**
     * Inicia el cobro de una cita en línea: crea el intento de pago y manda al paciente a la pasarela.
     * Con un código que cubre todo el precio no hay pasarela: la cita queda pagada y va directo al comprobante.
     */
    public function pay(Request $request, Appointment $appointment, AppointmentCheckout $checkout)
    {
        $this->authorizeOwnerOrFail($appointment);

        $data = $request->validate(['promo_code' => 'nullable|string|max:255']);

        $promo = null;
        if (!empty($data['promo_code'])) {
            $promo = app(\App\Services\Promo\PromoCodeService::class)
                ->findForAppointment($data['promo_code'], $appointment, auth()->id());

            if (!$promo) {
                return redirect()->route('appointments.preview', $appointment->id)
                    ->with('error', 'El código promocional no es válido, expiró o superó el límite de usos.');
            }
        }

        $appointment->loadMissing(['service', 'address', 'doctor.settings', 'clinic.settings']);

        if (!AppointmentCheckout::requiresOnlinePayment($appointment)) {
            return redirect()->route('appointments.preview', $appointment->id)
                ->with('error', 'Esta cita se paga en consulta.');
        }

        try {
            $payment = $checkout->start($appointment, Auth::user(), $promo);
        } catch (\DomainException $e) {
            return redirect()->route('appointments.preview', $appointment->id)->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('appointments.pay_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return redirect()->route('appointments.preview', $appointment->id)
                ->with('error', 'No pudimos iniciar el pago. Inténtalo de nuevo en unos segundos.');
        }

        if ($payment->method === \App\Enums\PaymentMethod::Free) {
            return redirect()->route('appointments.success', $appointment);
        }

        return redirect()->away($checkout->checkoutUrl($payment, route('payments.return', $payment->reference)));
    }

    /** Comprobante de la cita: solo para su paciente/doctor/clínica con sesión iniciada. */
    public function success(Appointment $appointment, AppointmentFinalizer $finalizer)
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $this->authorizeOwnerOrFail($appointment);
        $appointment->load(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user', 'doctor.settings', 'clinic.settings']);

        $paid = ($appointment->payment_status instanceof \BackedEnum ? $appointment->payment_status->value : (string) $appointment->payment_status) === PaymentStatus::PAID->value;

        if (AppointmentCheckout::requiresOnlinePayment($appointment) && !$paid) {
            return redirect()->route('appointments.preview', $appointment->id)
                ->with('error', 'Debes completar el pago para confirmar tu cita.');
        }

        // Idempotente: Zoom, correos y SMS se hacen una sola vez, llegue primero el webhook o el paciente.
        $appointment = $finalizer->finalize($appointment, Auth::user());

        return view('appointments.success', compact('appointment'));
    }

    private function authorizeOwnerOrFail(Appointment $appointment): void
    {
        $user = Auth::user();
        abort_unless($user, 401, 'Debes iniciar sesión.');

        $appointment->loadMissing('patient');

        $ok = match ($user->role) {
            'patient' => $appointment->patient && (int) $appointment->patient->user_id === (int) $user->id,
            'doctor'  => (int) $appointment->doctor_id === (int) $user->doctor?->id,
            'clinic'  => (int) $appointment->clinic_id === (int) $user->clinic?->id,
            'admin'   => true,
            default   => false,
        };

        abort_unless($ok, 403, 'Acceso no autorizado a esta cita.');
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
    
    public function joinRoom(Appointment $appointment, ZoomService $zoomService, RoomSessionGuard $roomSessions)
    {
        $user = auth()->user();
        $this->authorizeRoomAccess($appointment, $user);

        // Sin reunión de Zoom: intentamos crearla al vuelo (si la cita lo permite) en vez de expulsar al usuario
        if (!$appointment->zoom_meeting_id) {
            $reason = $this->tryRecoverZoomMeeting($appointment);
            $appointment->refresh();

            if (!$appointment->zoom_meeting_id) {
                return $this->roomUnavailableView($appointment, $reason);
            }
        }

        try {
            $meetingId = (string) $appointment->zoom_meeting_id;

            // Host (role 1) solo para el doctor dueño de la cita.
            // appointments.doctor_id referencia doctors.id (NO users.id).
            $isHost = ($user->role === 'doctor' || $user->hasRole('doctor'))
                && $user->doctor
                && (int) $user->doctor->id === (int) $appointment->doctor_id;
            $role = $isHost ? 1 : 0;

            $password = $this->resolveMeetingPassword($appointment, $zoomService);

            // El ZAK solo se entrega al anfitrión: es lo que permite INICIAR la reunión desde el SDK.
            $zak = null;
            if ($isHost) {
                $zak = $zoomService->getHostZak();

                if (!$zak) {
                    Log::error("joinRoom: no se pudo obtener el ZAK del host (Cita ID {$appointment->id}). Revisa el scope user:read:token:admin de la app Server-to-Server.");
                }
            }

            $signature = $zoomService->generateSdkSignature($meetingId, $role);

            // Cargamos la historia clínica de esta cita si ya existe (ej. iniciada desde Mis Citas)
            $existingHistory = PatientHistory::where('appointment_id', $appointment->id)->first();

            return view('appointments.room', [
                'appointment'      => $appointment,
                'existingHistory'  => $existingHistory,
                'meetingId'        => $meetingId,
                'password'         => $password,
                'signature'        => $signature,
                'sdkKey'           => $zoomService->getSdkClientId(),
                'zak'              => $zak,
                'userRole'         => $role,
                'zoomStarted'      => $appointment->zoom_started,                
                'roomSessionToken' => $roomSessions->claim($appointment->id, $user->id),
            ]);

        } catch (\Exception $e) {
            Log::error("Error en joinRoom (Cita ID {$appointment->id}): " . $e->getMessage());
            return $this->roomUnavailableView($appointment, 'config');
        }
    }

    /**
     * Solo participan en la sala: el paciente dueño, el doctor de la cita, la clínica de la cita y los administradores.
     */
    private function authorizeRoomAccess(Appointment $appointment, $user): void
    {
        $isAdmin = $user->role === 'admin' || (method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $allowed = $isAdmin
            || ($user->role === 'patient' && (int) $appointment->patient?->user_id === (int) $user->id)
            || ($user->role === 'doctor' && $user->doctor && (int) $appointment->doctor_id === (int) $user->doctor->id)
            || ($user->role === 'clinic' && $user->clinic && (int) $appointment->clinic_id === (int) $user->clinic->id);

        abort_unless($allowed, 403, 'No tienes acceso a esta sala.');
    }

    /**
     * Intenta crear la reunión de Zoom de una cita que llegó a la sala sin ella.
     * Devuelve un código de motivo: recovered | unpaid | cancelled | completed | expired | not_virtual | throttled | failed
     */
    private function tryRecoverZoomMeeting(Appointment $appointment): string
    {
        $enumValue = fn ($v) => $v instanceof \BackedEnum ? $v->value : (string) $v;

        $status = $enumValue($appointment->status);
        if ($status === AppointmentStatus::CANCELLED->value) return 'cancelled';
        if ($status === AppointmentStatus::COMPLETED->value) return 'completed';

        $appointment->loadMissing(['service', 'address']);
        $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');
        if (!$isVirtual) return 'not_virtual';

        if ($enumValue($appointment->payment_status) !== PaymentStatus::PAID->value) return 'unpaid';

        $tz       = config('app.timezone', 'America/Bogota');
        $startsAt = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time, $tz);
        $endsAt   = $startsAt->copy()->addMinutes((int) $appointment->duration);
        if (now($tz)->greaterThan($endsAt)) return 'expired';

        // Enfriamiento: si falló hace menos de 60 s, no volvemos a golpear la API de Zoom
        $cooldownKey = "zoom_recover_cooldown_{$appointment->id}";
        if (Cache::has($cooldownKey)) return 'throttled';

        try {
            $created = DB::transaction(function () use ($appointment, $startsAt, $tz) {
                $fresh = $appointment->newQuery()->lockForUpdate()->find($appointment->id);

                // Otra petición (doctor/paciente) pudo crearla mientras tanto
                if ($fresh->zoom_meeting_id) return true;

                // Si el horario ya empezó, Zoom necesita un start_time vigente
                $start = $startsAt->isPast() ? now($tz)->addMinute() : $startsAt;

                $zoomMeeting = $this->zoomService->createMeeting(
                    "Telemedicina_Ref: {$fresh->reference}",
                    $start->toIso8601String(),
                    (int) $fresh->duration
                );

                if (!$zoomMeeting) return false;

                $this->persistZoomMeeting($fresh, $zoomMeeting);
                DB::table('zoom_creation_failures')->where('appointment_id', $fresh->id)->delete();

                return true;
            });

            if ($created) {
                Log::info("joinRoom: reunión de Zoom recuperada al vuelo (Cita ID {$appointment->id}).");
                return 'recovered';
            }

            $this->registerRecoveryFailure($appointment, 'ZoomService no devolvió datos de la reunión.');
        } catch (Throwable $e) {
            Log::error("joinRoom: no se pudo recuperar la reunión de Zoom (Cita ID {$appointment->id}): " . $e->getMessage());
            $this->registerRecoveryFailure($appointment, $e->getMessage());
        }

        Cache::put($cooldownKey, true, 60);

        return 'failed';
    }

    /**
     * Deja constancia en la tabla de contingencia para el seguimiento manual / dashboards.
     */
    private function registerRecoveryFailure(Appointment $appointment, string $message): void
    {
        try {
            $row = DB::table('zoom_creation_failures')->where('appointment_id', $appointment->id);

            if ($row->exists()) {
                $row->update([
                    'attempts'   => DB::raw('COALESCE(attempts, 0) + 1'),
                    'status'     => 'failed',
                    'last_error' => substr($message, 0, 500),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('zoom_creation_failures')->insert([
                    'appointment_id' => $appointment->id,
                    'attempts'       => 1,
                    'status'         => 'failed',
                    'last_error'     => substr($message, 0, 500),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('No se pudo registrar el fallo de recuperación de Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Mantiene al usuario en la URL de la sala con un mensaje claro en lugar de redirigirlo al dashboard.
     */
    private function roomUnavailableView(Appointment $appointment, string $reason)
    {
        $appointment->loadMissing(['patient.user']);
        $user = auth()->user();

        $isProfessional = in_array($user->role, ['doctor', 'clinic', 'admin'], true)
            || (method_exists($user, 'hasRole') && $user->hasRole('admin'));

        return response()->view('appointments.room-unavailable', [
            'appointment'    => $appointment,
            'reason'         => $reason,
            'isProfessional' => $isProfessional,
            'exitUrl'        => $isProfessional ? route('admin.dashboard') : route('patient.appointments.index'),
            'autoRetry'      => in_array($reason, ['throttled', 'failed'], true),
            'supportUrl'     => config('services.support.url'),
        ]);
    }

    /**
     * Devuelve la password de la reunión de Zoom (el modelo Appointment la descifra al leerla).
     * Si la cita es anterior al guardado de la password (columna NULL), la recupera desde Zoom y la persiste.
     */
    private function resolveMeetingPassword(Appointment $appointment, ZoomService $zoomService): string
    {
        $stored = $appointment->meeting_link_password;

        if (!empty($stored)) {
            return (string) $stored;
        }

        $meeting  = $zoomService->getMeeting($appointment->zoom_meeting_id);
        $password = (string) ($meeting['password'] ?? '');

        if ($password !== '') {
            // Quietly: es una reparación al leer, no debe disparar el AppointmentObserver
            $appointment->updateQuietly(['meeting_link_password' => $password]);
        } else {
            Log::warning("Cita ID {$appointment->id}: la reunión de Zoom no devolvió password (se unirá con password vacía).");
        }

        return $password;
    }

    /**
     * Guarda en la cita los datos de la reunión creada por ZoomService::createMeeting().
     * Claves del servicio: meeting_id, password, url_partner (start_url), url_patient (join_url).
     * El modelo Appointment cifra meeting_link, zoom_start_url y meeting_link_password.
     */
    private function persistZoomMeeting(Appointment $appointment, array $zoomMeeting): void
    {
        $appointment->update([
            'zoom_meeting_id'       => $zoomMeeting['meeting_id'],
            'meeting_link'          => $zoomMeeting['url_patient'],
            'zoom_start_url'        => $zoomMeeting['url_partner'],
            'meeting_link_password' => ($zoomMeeting['password'] ?? '') ?: null,
        ]);
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

    /**
     * Latido de la sala: confirma que esta pestaña sigue siendo la sesión vigente de la persona en la cita.
     * Responde {"active": false} si otra pestaña o dispositivo abrió la sala después.
     */
    public function roomHeartbeat(Request $request, Appointment $appointment, RoomSessionGuard $roomSessions): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeRoomAccess($appointment, $user);

        $token = (string) $request->input('token', '');

        return response()->json([
            'active' => $token !== '' && $roomSessions->touch($appointment->id, $user->id, $token),
        ]);
    }

    public function getStatus(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'status' => $appointment->status->value,
            'zoom_started' => (bool) $appointment->zoom_started,
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
        // 1. Ya completada → idempotente
        if ($appointment->status === AppointmentStatus::COMPLETED) {
            return response()->json([
                'success' => true,
                'message' => 'La consulta ya estaba finalizada.'
            ]);
        }

        // 2. Intentar cerrar Zoom si existe (best-effort, no bloqueante)
        if ($appointment->zoom_meeting_id) {
            try {
                $zoomService->endMeeting($appointment->zoom_meeting_id);
            } catch (\Throwable $e) {
                Log::warning('Zoom falló al cerrar, pero continuamos', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. SIEMPRE marcar como completada (la verdad está en nuestra DB)
        $appointment->update([
            'status'       => AppointmentStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        // 4. Invalidar caché del motor nuevo (importante para Fase 3)
        app(\App\Services\Availability\AvailabilityRepository::class)
            ?->invalidateAvailabilityCache($appointment->doctor_id);

        return response()->json([
            'success' => true,
            'message' => 'Consulta finalizada correctamente.'
        ]);
    }

    /** Ruta heredada de retorno de Wompi: se resuelve por la capa de pagos y se manda a la página pública de resultado. */
    public function paymentResult(Request $request, PaymentService $payments)
    {
        $transactionId = $request->query('id');
        if (!$transactionId) {
            return redirect()->route('home')->with('error', 'No se recibió información del pago.');
        }

        try {
            $payment = $payments->syncByTransactionId((string) $transactionId);
        } catch (Throwable $e) {
            Log::warning('appointments.payment_result_sync_failed', ['error' => $e->getMessage()]);
            $payment = null;
        }

        if (!$payment) {
            return redirect()->route('home')->with('error', 'No se pudo verificar el pago.');
        }

        return redirect()->route('payments.return', $payment->reference);
    }

    /** Calcula el desglose con el código. No guarda nada: el código se vuelve a validar al pagar. */
    public function validatePromoCode(Request $request, AppointmentCheckout $checkout)
    {
        $validated = $request->validate([
            'code'           => 'required|string|max:255',
            'appointment_id' => 'required|integer',
        ]);

        $appointment = Appointment::with(['service', 'address', 'doctor.settings', 'clinic.settings'])->findOrFail($validated['appointment_id']);
        $this->authorizeOwnerOrFail($appointment);

        if (!AppointmentCheckout::requiresOnlinePayment($appointment)) {
            return response()->json(['message' => 'Los códigos solo aplican a citas con pago en línea.'], 422);
        }

        $promo = app(\App\Services\Promo\PromoCodeService::class)
            ->findForAppointment($validated['code'], $appointment, auth()->id());

        if (!$promo) {
            return response()->json(['message' => 'El código promocional no es válido, está desactivado, expiró o superó el límite de usos.'], 422);
        }

        $original = $checkout->quote($appointment);
        $quote    = $checkout->quote($appointment, $promo);

        return response()->json([
            'success'         => true,
            'code'            => $promo->code,
            'type'            => $promo->type,
            'reward'          => $promo->reward,
            'discount_amount' => ($original->totalCents - $quote->totalCents) / 100,
            'base_price'      => $original->totalCents / 100,
            'final_price'     => $quote->totalCents / 100,
            'wompiData'       => $quote->toArray(),
        ], 200);
    }
}
