<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\patient;
use App\Models\Doctor;
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

class AppointmentController extends Controller
{
    protected $appointmentService;
    protected $zoomService;

    // Inyectamos el servicio en el constructor
    public function __construct(AppointmentService $service, ZoomService $zoomService)
    {
        $this->appointmentService = $service;
        $this->zoomService = $zoomService;
    }

    public function store(Request $request)
    {
        $service = Service::findOrFail($request->service_id);
        
        $appointmentData = [
            'patient_id' => auth()->id(),
            'doctor_id'  => $request->doctor_id,
            'service_id' => $service->id,
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'status'     => 'confirmed',
        ];

        // Lógica para el link automático
        if ($service->type === 'virtual') {
            // Opción A: Generar un link único interno (ej: /meet/ABC-123)
            $appointmentData['meeting_link'] = url('/meet/' . Str::random(10));
            
            // Opción B: Podrías conectar con la API de Zoom aquí
        } else {
            $appointmentData['address_id'] = $request->address_id;
        }

        $appointment = Appointment::create($appointmentData);

        return redirect()->route('patient.appointments')
            ->with('success', 'Cita agendada. ' . ($service->type === 'virtual' ? 'El link de la reunión está listo.' : ''));
    }

    public function storeStepTwo(Request $request) 
    {
        // Rescatamos el ID de la sesión
        $doctorId = session('current_doctor_id');

        if (!$doctorId) {
            return [
                "message" => "Doctor no seleccionado correctamente",
                "status" => false,
            ];
        }
        
        // Guardamos los datos de la selección en la sesión temporalmente
        session([
            'booking_data' => [
                'doctor_id'  => $doctorId, 
                'service_id' => $request->service_id,
                'address_id' => $request->address_id,
                'date' => $request->date,
                'hour' => $request->hour,
            ]
        ]);

        return [
            "message" => "booking_data configurada correctamente",
            "status" => true,
        ];
    }

    public function patient()
    {
        // Verificamos que existan datos en la sesión, si no, volvemos al inicio
        if (!session()->has('booking_data')) {
            return redirect()->route('search');
        }

        $doctorId = session('current_doctor_id');
        $userId = auth()->user()->id;

        //si el usuario que esta reservando es el mismo doctor o clinica no deja avanzar en el proceso
        $isSelfBooking = false;
        if ($doctorId === $userId) {
            $isSelfBooking = true;
        }
        
        return view('appointments.patient', compact('isSelfBooking'));
    }

    public function processPatient(Request $request)
    {    
        $bookingData = session('booking_data');
        if (!$bookingData) return redirect()->route('search')->with('error', 'Sesión expirada.');

        $rules = ['notes' => 'required|string|min:10|max:500'];

        $hasAccount = $request->has_account == 'yes' ? true : false;

        if (Auth::guest()) {
            if ($hasAccount) {
                $rules['login_email'] = 'required|email|exists:users,email';
                $rules['login_password'] = 'required';
            } else {
                $rules['name'] = 'required|string|min:3|max:100';
                $rules['email'] = 'required|email|unique:users,email';
                $rules['identification'] = 'required|numeric';
                $rules['phone'] = 'required|numeric';
            }
        }
        
        $request->validate($rules);        
                                        
        return DB::transaction(function () use ($request, $bookingData) {
            $hasAccount = $request->has_account == 'yes' ? true : false;
            if (Auth::guest()) {
                if ($hasAccount) {
                    $login_email = trim(strtolower($request->login_email));
                    $login_password = $request->login_password;
                    
                    if (!Auth::attempt(['email' => $login_email, 'password' => $login_password])) {
                        return back()->withErrors(['login_email' => 'Las credenciales no coinciden.'])->withInput();
                    }
                    $user = auth()->user();
                } else {
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->identification),
                    ]);

                    $user->assignRole('patient');
                    Auth::login($user);
                }
            } else {
                $user = auth()->user();
            }
            
            $patient = $user->patient;
            if (!$patient) {
                $patient = Patient::create([
                    'user_id' => $user->id,
                    'identification' => $request->identification ?? '000000' . $user->id,
                    'phone' => $request->phone ?? '00000000',
                ]);
            }

            // Obtener precio y duración específicos de la tabla intermedia (Pivot)
            $address = Address::with(['services' => function($q) use ($bookingData) {
                $q->where('services.id', $bookingData['service_id']);
            }])->find($bookingData['address_id']);

            $serviceSpecific = $address?->services->first();

            if (!$serviceSpecific || !$serviceSpecific->pivot) {
                return redirect()->route('search')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
            }

            $duration = (int) $serviceSpecific->pivot->duration;
            $price = $serviceSpecific->pivot->price;

            $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);

            // Asegurar disponibilidad real en el último milisegundo
            $isAvailable = $this->appointmentService->isAvailable(
                $bookingData['doctor_id'],
                $bookingData['date'],
                $startTime->format('H:i:s'),
                $duration
            );

            if (!$isAvailable) {
                return redirect()->route('search')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente. Por favor selecciona otra hora.');
            }
            
            // Obtener la configuración del doctor            
            $doctor = Doctor::with(['settings', 'user'])->findOrFail($bookingData['doctor_id']);
            
            // Evaluamos si el doctor requiere aprobación manual.
            $requiresApproval = $doctor->settings && $doctor->settings->requires_approval;
            $acceptsPayments = $doctor->settings && $doctor->settings->accepts_online_payments;

            if ($acceptsPayments) {
                $requiresApproval = false; 
            }

            $status = $requiresApproval ? 'pending' : 'confirmed';

            // Variables de control para videoconferencia
            $meetingLink = null;
            $zoomMeetingId = null;
            $zoomStartUrl = null;

            // 👇 INTEGRACIÓN DE VIDEO-CONFERENCIA DE ZOOM CORREGIDA
            if ($serviceSpecific->type === 'virtual') {
                $platform = $doctor->settings->virtual_meeting_platform ?? 'internal';

                if ($platform === 'zoom') {
                    $startDateTimeISO = $startTime->format('Y-m-d\TH:i:s');
                    $topicName = "Consulta 1-on-1: " . $serviceSpecific->name . " - Paciente: " . $patient->user->name;

                    // El servicio ahora retorna un array asociativo con la información de Zoom
                    $zoomResponse = $this->zoomService->createMeeting($topicName, $startDateTimeISO, $duration);

                    if ($zoomResponse) {
                        $meetingLink = $zoomResponse['url_paciente']; // Enlace para el paciente
                        $zoomMeetingId = $zoomResponse['meeting_id'];  // ID de la reunión
                        $zoomStartUrl = $zoomResponse['url_doctor'];   // Enlace anfitrión para el Doctor
                    }
                }

                // Seguro de respaldo (Fallback): Si falla la API de Zoom o prefiere la plataforma interna
                if (!$meetingLink) {
                    $roomCode = 'room-' . Str::lower(Str::random(4)) . '-' . time();                    
                    $meetingLink = route('patient.appointments.waiting_room', ['room_code' => $roomCode]);
                }
            }

            // Crear la cita utilizando el estado dinámico y los enlaces calculados
            $appointment = Appointment::create([
                'patient_id'      => $patient->id,
                'doctor_id'       => $bookingData['doctor_id'],
                'service_id'      => $bookingData['service_id'],
                'address_id'      => $serviceSpecific->type === 'virtual' ? null : $bookingData['address_id'], 
                'date'            => $bookingData['date'],
                'start_time'      => $startTime->format('H:i:s'),
                'end_time'        => $startTime->copy()->addMinutes($duration)->format('H:i:s'), 
                'duration'        => $duration, 
                'price'           => $price,    
                'meeting_link'    => $meetingLink,      // Se almacena el enlace de invitado
                'zoom_meeting_id' => $zoomMeetingId,    // ID de Zoom
                'zoom_start_url'  => $zoomStartUrl,     // Enlace exclusivo para el médico
                'status'          => $status, 
                'notes'           => $request->notes
            ]);
            
            session()->forget(['booking_data', 'current_doctor_id']);

            $message = $status === 'pending' 
                ? 'Tu solicitud de cita ha sido enviada. El doctor debe aprobarla.' 
                : 'Cita agendada y confirmada correctamente.';

            return redirect()->route('appointments.preview', ['id' => $appointment->id])->with('success', $message);       
        });
    }

    public function preview($id)
    {
        $patient = auth()->user()->patient;

        if (!$patient) {
            return redirect()->route('search')->with('error', 'Perfil de paciente no encontrado.');
        }

        // Buscamos la cita asegurando que pertenezca al paciente logueado
        $appointment = Appointment::with(['service', 'address.city', 'doctor.user', 'doctor.settings'])
            ->where('patient_id', $patient->id)
            ->findOrFail($id);  
            
        return view('appointments.preview', compact('appointment'));
    }

    public function confirm($id)
    {
        // A. Sanitización básica: Validar que sea un número entero positivo
        if (!ctype_digit($id)) {
            abort(404, 'Formato de cita no válido.');
        }
        
        $appointment = Appointment::where('id', $id)
            ->where('patient_id', auth()->user()->patient->id) // Crucial: solo sus citas
            ->with(['service', 'doctor.user', 'address'])
            ->first();

        $user = auth()->user();
        // Validación: ¿El usuario es el mismo doctor de la cita?
        if ($user->doctor && $user->doctor->id === $appointment->doctor_id) {
            return redirect()->route('search')
                ->with('error', 'No puedes agendar una cita contigo mismo.');
        }
            
        try {
            DB::beginTransaction();

            // 1. Actualizar estado
            //si el partner tiene habilitado el cobro online validar la respuesta 
            // del gateway de la transaccion, aca se actualiza el estado del appointment
            $appointment->update(['status' => 'confirmed']);

            // 2. Enviar correo (Opcional: usar queue para no retrasar la carga)
            Mail::to(Auth::user()->email)->send(new AppointmentConfirmed($appointment));
            
            // 3. Notificar al doctor (Ejemplo simple)
            // Mail::to($appointment->doctor->user->email)->send(new NewAppointmentAlert($appointment));

            DB::commit();

            return redirect()->route('appointments.success', $appointment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Hubo un error al confirmar tu cita. Inténtalo de nuevo.');
        }
    }

    public function success($id)
    {
        // A. Sanitización básica: Validar que sea un número entero positivo
        if (!ctype_digit($id)) {
            abort(404, 'Formato de cita no válido.');
        }

        // B. Validación de Propiedad (Seguridad Real)
        // Esto evita que el usuario 1 vea la cita del usuario 2 cambiando el ID en la URL
        $appointment = Appointment::where('id', $id)
            ->where('patient_id', auth()->user()->patient->id) // Crucial: solo sus citas
            ->with(['service', 'doctor.user', 'address'])
            ->first();

        // C. Manejo de Error de Existencia
        if (!$appointment) {
            //return redirect()->route('dashboard')->with('error', 'La cita no existe o no tienes permiso para verla.');
        }

        return view('appointments.success', compact('appointment'));
    }

    public function rescheduleView(Appointment $appointment)
    {
        // Validar que el doctor sea el dueño de la cita
        if ($appointment->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        // Obtenemos la fecha del request o por defecto la de la cita
        $date = request('date', $appointment->date);
        
        // Usamos tu servicio para obtener slots disponibles para esa fecha
        // Asumo que tu servicio tiene un método similar a getAvailableSlots
        $availableSlots = $this->appointmentService->getAvailableSlots(
            $appointment->doctor_id, 
            $date, 
            $appointment->service_id
        );

        return view('partner.appointments.reschedule', compact('appointment', 'availableSlots', 'date'));
    }

    public function rescheduleProcess(Request $request, Appointment $appointment)
    {
        $request->validate([
            'date' => 'required|date',
            'hour' => 'required',
        ]);

        $newStart = Carbon::parse($request->date . ' ' . $request->hour);
        $newEnd = $newStart->copy()->addMinutes($appointment->duration);

        // Solo validamos traslado si la cita es PRESENCIAL
        if ($appointment->service->type !== 'virtual') {
            
            // 1. Buscar la cita inmediatamente anterior ese día
            $prevApp = Appointment::where('doctor_id', $appointment->doctor_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->where('end_time', '<=', $newStart->format('H:i:s'))
                ->orderBy('end_time', 'desc')
                ->first();

            // 2. Si hay una cita antes en OTRA sede, validar margen de 30 min (ajustable)
            if ($prevApp && $prevApp->address_id !== $appointment->address_id) {
                $prevEnd = Carbon::parse($prevApp->date . ' ' . $prevApp->end_time);
                if ($newStart->diffInMinutes($prevEnd) < 30) {
                    return back()->with('error', 'Conflicto de traslado: Necesitas al menos 30 min para llegar desde ' . $prevApp->address->name);
                }
            }

            // 3. Buscar la cita inmediatamente después
            $nextApp = Appointment::where('doctor_id', $appointment->doctor_id)
                ->where('date', $request->date)
                ->where('id', '!=', $appointment->id)
                ->where('start_time', '>=', $newEnd->format('H:i:s'))
                ->orderBy('start_time', 'asc')
                ->first();

            if ($nextApp && $nextApp->address_id !== $appointment->address_id) {
                $nextStart = Carbon::parse($nextApp->date . ' ' . $nextApp->start_time);
                if ($nextStart->diffInMinutes($newEnd) < 30) {
                    return back()->with('error', 'Conflicto de traslado: La siguiente cita en ' . $nextApp->address->name . ' empieza muy pronto.');
                }
            }
        }

        // Si pasa las validaciones, actualizamos
        $appointment->update([
            'date'       => $request->date,
            'start_time' => $newStart->format('H:i:s'),
            'end_time'   => $newEnd->format('H:i:s'),
            'status'     => 'confirmed'
        ]);

        return redirect()->route('partner.appointments.index', ['date' => $appointment->date])
            ->with('success', 'Cita reagendada correctamente.');
    }

    /**
     * POST o PUT /appointments/{id}/cancel
     * Cancela la cita desde la interfaz web del paciente.
     */
    public function cancelWeb($id)
    {
        // 1. Validar reglas de tiempo con el servicio
        $checkStatus = $this->appointmentService->checkIfCanModify($id);

        if (!$checkStatus['allowed']) {
            return back()->with('error', $checkStatus['message']);
        }

        // 2. Buscar la cita asegurando que pertenezca al paciente autenticado
        $patient = auth()->user()->patient;
        if (!$patient) {
            return back()->with('error', 'Perfil de paciente no encontrado.');
        }
        
        $appointment = Appointment::where('patient_id', $patient->id)->findOrFail($id);

        // 3. Ejecutar la cancelación en base de datos y plataformas externas
        // Guardamos el resultado de la transacción en una variable para usarla después
        $cancelledAppointment = DB::transaction(function () use ($appointment) {
            
            // CONFIGURACIÓN DE ZOOM: Si la cita tiene un ID de reunión válido se da de baja
            if ($appointment->zoom_meeting_id) {
                try {
                    // Instanciamos de forma dinámica el servicio de Zoom
                    $zoomService = app(ZoomService::class);
                    
                    // Ejecutamos la baja en los servidores de Zoom
                    $zoomService->deleteMeeting($appointment->zoom_meeting_id);
                    
                    Log::info("Reunión de Zoom {$appointment->zoom_meeting_id} eliminada correctamente por el paciente ID: " . auth()->id());
                } catch (\Exception $e) {
                    // El fallo de Zoom no debe truncar la cancelación local, se registra en logs
                    Log::error("Error no crítico al intentar borrar cita en Zoom: " . $e->getMessage());
                }
            }

            // Cancelar la cita localmente
            $appointment->update([
                'status' => 'cancelled'
            ]);

            return $appointment;
        });

        // 4. 👇 SE DISPARA EL ENVIÓ DEL CORREO FUERA DE LA TRANSACCIÓN (Estructura corregida)
        // Pasamos la variable legítima que retornó la transacción
        event(new AppointmentCancelled($cancelledAppointment));

        // 5. Redirección final con mensaje de éxito
        return redirect()->route('patient.appointments.index')
            ->with('success', 'Tu cita ha sido cancelada correctamente y el doctor ha sido notificado.');
    }

    /**
     * POST /partner/appointments/{id}/generate-zoom
     * Permite al médico regenerar o crear el enlace de Zoom si el fallback automático se activó.
     */
    public function generateZoomLink($id)
    {
        // 1. Buscar la cita con sus relaciones necesarias (Doctor y Paciente)
        $appointment = Appointment::with(['doctor.user', 'patient.user', 'service'])->findOrFail($id);

        // Candado de seguridad: Verificar que la cita sea virtual y no tenga ya un ID de Zoom
        if ($appointment->service->type !== 'virtual') {
            return back()->with('error', 'Este servicio no es de modalidad virtual.');
        }

        if ($appointment->zoom_meeting_id) {
            return back()->with('error', 'Esta cita ya cuenta con un enlace de Zoom asignado.');
        }

        // 2. Preparar los datos para la API de Zoom
        $zoomService = app(ZoomService::class);
        $startDateTimeISO = \Carbon\Carbon::parse($appointment->date . ' ' . $appointment->start_time)->format('Y-m-d\TH:i:s');
        $topicName = "Consulta 1-on-1: " . $appointment->service->name . " - Paciente: " . $appointment->patient->user->name;

        // 3. Llamar a la API de Zoom
        $zoomResponse = $zoomService->createMeeting($topicName, $startDateTimeISO, $appointment->duration);

        if (!$zoomResponse) {
            return back()->with('error', 'La API de Zoom sigue sin responder. Por favor, verifica tus credenciales o vuelve a intentarlo en unos minutos.');
        }

        // 4. Actualizar la cita sustituyendo el link fallback por los accesos reales de Zoom
        $appointment->update([
            'meeting_link'    => $zoomResponse['url_paciente'], // Sustituye el fallback por el del paciente
            'zoom_meeting_id' => $zoomResponse['meeting_id'],
            'zoom_start_url'  => $zoomResponse['url_doctor'],   // Enlace de inicio para el médico
        ]);

        return back()->with('success', '¡Enlace de Zoom generado con éxito! El paciente ya puede visualizarlo desde su panel.');
    }

    /**
     * GET /patient/meet/{room_code}
     * Muestra la sala de espera local al paciente si Zoom no se generó a tiempo.
     */
    public function waitingRoom($roomCode)
    {
        // Reconstruimos la URL completa para buscarla en la base de datos
        $fullUrl = url("/patient/meet/{$roomCode}");

        // Buscamos la cita que tenga asignado este enlace de respaldo
        $appointment = Appointment::with(['doctor.user', 'service'])
            ->where('meeting_link', $fullUrl)
            ->firstOrFail();

        $startTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time);
        $endTime = $startTime->copy()->addMinutes($appointment->duration);
        
        // El botón se habilitará 15 minutos antes de la hora de la cita
        $activationTime = $startTime->copy()->subMinutes(15);
        $isAvailable = now()->between($activationTime, $endTime);

        return view('patient.appointments.waiting_room', [
            'appointment' => $appointment,
            'startTime'   => $startTime,
            'isAvailable' => $isAvailable,
            'roomCode'    => $roomCode
        ]);
    }
}
