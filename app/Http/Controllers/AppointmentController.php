<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\patient;
use App\Services\AppointmentService;
use App\Models\User;
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

    // Inyectamos el servicio en el constructor
    public function __construct(AppointmentService $service)
    {
        $this->appointmentService = $service;
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

        return view('appointments.patient');
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

            // 👇 NUEVA LÓGICA: Obtener precio y duración específicos de la tabla intermedia (Pivot)
            $address = \App\Models\Address::with(['services' => function($q) use ($bookingData) {
                $q->where('services.id', $bookingData['service_id']);
            }])->find($bookingData['address_id']);

            $serviceSpecific = $address?->services->first();

            // Validación de seguridad por si el servicio fue removido de la sede durante el proceso
            if (!$serviceSpecific || !$serviceSpecific->pivot) {
                return redirect()->route('search')->with('error', 'El servicio seleccionado ya no está disponible en esta sede.');
            }

            // Rescatamos los valores reales de la tabla pivot
            $duration = (int) $serviceSpecific->pivot->duration;
            $price = $serviceSpecific->pivot->price;

            $startTime = Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
            
            // Crear la cita utilizando las variables específicas calculadas
            $appointment = Appointment::create([
                'patient_id'   => $patient->id,
                'doctor_id'    => $bookingData['doctor_id'],
                'service_id'   => $bookingData['service_id'],
                'address_id'   => $bookingData['address_id'],
                'date'         => $bookingData['date'],
                'start_time'   => $startTime->format('H:i:s'),
                'end_time'     => $startTime->copy()->addMinutes($duration)->format('H:i:s'), // Usa la duración del pivot
                'duration'     => $duration, // Cambiado de $service->duration a $duration
                'price'        => $price,    // Cambiado de $service->price a $price
                'meeting_link' => ($serviceSpecific->type === 'virtual') ? 'https://zoom.us' : null,            
                'status'       => 'pending', 
                'notes'        => $request->notes
            ]);
            
            session()->forget(['booking_data', 'current_doctor_id']);

            return redirect()->route('appointments.preview', ['id' => $appointment->id])->with('success', 'Cita agendada correctamente');       
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
}
