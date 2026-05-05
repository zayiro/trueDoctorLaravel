<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed; // Debes crear este Mailable

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

    /*public function processPatient(Request $request)
    {
        $rules = [
            // Identificación: solo números, entre 7 y 10 dígitos (estándar cédula)
            'identification' => 'required|numeric|digits_between:7,12|unique:patients,identification,' . (auth()->user()?->patient?->id ?? 'NULL'),
            
            // Teléfono: formato numérico de 10 dígitos (celular estándar)
            'phone' => 'required|numeric|digits:10',
            
            'notes' => 'required|string|min:10|max:500',
        ];

        if (Auth::guest()) {
            $rules['name'] = 'required|string|min:3|max:100';
            $rules['email'] = 'required|email|unique:users,email';
        }

        $request->validate($rules, [
            'identification.digits_between' => 'La identificación debe tener entre 7 y 12 números.',
            'identification.numeric' => 'La identificación solo debe contener números.',
            'phone.digits' => 'El número de teléfono debe tener exactamente 10 dígitos.',
            'notes.min' => 'Por favor, describe un poco más el motivo de tu consulta.',
        ]);
        
        if (Auth::guest()) {
            // 1. Crear el Usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->identification),
            ]);
            $user->assignRole('patient');

            // 2. Crear el Perfil de Paciente vinculado
            $user->patient()->create([
                'identification' => $request->identification,
                'phone' => $request->phone,
            ]);

            Auth::login($user);
        }

        return redirect()->route('appointments.preview', [
            'service' => $request->service_id,
            'address' => $request->address_id,
            'datetime' => $request->datetime,
            'notes' => $request->notes,
        ]);
    }*/

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

        //dd($bookingData);

        if (!$bookingData) {
            return redirect()->route('search')->with('error', 'La sesión ha expirado.');
        }

        $rules = [
            // Identificación: solo números, entre 7 y 10 dígitos (estándar cédula)
            'identification' => 'required|numeric|digits_between:7,12|unique:patients,identification,' . (auth()->user()?->patient?->id ?? 'NULL'),
            
            // Teléfono: formato numérico de 10 dígitos (celular estándar)
            'phone' => 'required|numeric|digits:10',
            
            'notes' => 'required|string|min:10|max:500',
        ];

        if (Auth::guest()) {
            $rules['name'] = 'required|string|min:3|max:100';
            $rules['email'] = 'required|email|unique:users,email';
        }

        $request->validate($rules, [
            'identification.digits_between' => 'La identificación debe tener entre 7 y 12 números.',
            'identification.numeric' => 'La identificación solo debe contener números.',
            'phone.digits' => 'El número de teléfono debe tener exactamente 10 dígitos.',
            'notes.min' => 'Por favor, describe un poco más el motivo de tu consulta.',
        ]);
        
        if (!Auth::check()) {
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'identification' => 'required|string|unique:patients,identification',
                'phone' => 'required|string',
                'notes' => 'required|string|min:10|max:500',
            ]);
        }

        $request->validate($rules);

        $datetime = $bookingData['date'] . " " . $bookingData['hour'];

        // 2. Crear y Loguear Usuario si es Guest
        if (!Auth::check()) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->identification),
            ]);
            Auth::login($user);
        }

        // 3. Crear la Cita
        // 1. Buscamos el servicio para obtener precio y duración
        $service = Service::findOrFail($bookingData['service_id']);

        // 2. Calculamos los tiempos usando Carbon
        $startTime = \Carbon\Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
        $endTime = (clone $startTime)->addMinutes($service->duration);

        // 3. Creamos la cita con datos reales
        $appointment = Appointment::create([
            'patient_id'   => Auth::id(),
            'doctor_id'    => $bookingData['doctor_id'],
            'service_id'   => $bookingData['service_id'],
            'address_id'   => $bookingData['address_id'],
            'date'         => $bookingData['date'],
            'start_time'   => $startTime->format('H:i:s'),
            'end_time'     => $endTime->format('H:i:s'),
            'duration'     => $service->duration,
            'price'        => $service->price,
            'meeting_link' => ($service->type === 'virtual') ? 'https://zoom.us' : null,            
            'status'       => 'pending', 
            'notes'        => $request->notes,
        ]);

        //dd($appointment);

        // 4. Limpiar sesión y redirigir
        session()->forget('booking_data');

        // 5. Limpiar el doctor seleccionado
        session()->forget('current_doctor_id');

        return redirect()->route('appointments.preview', [
            'id' => $appointment->id,
            'service'  => $request->service_id,
            'address'  => $request->address_id,
            'datetime' => $datetime,
            'endTime'  => $service->price,
            'price'    => $endTime,
            'duration' => $service->duration,
            'notes'   => $request->notes,
        ]);
    }

    public function preview($id)
    {
        // Buscamos la cita asegurándonos que pertenezca al usuario logueado
        $appointment = Appointment::with(['service', 'address', 'doctor.user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);           

        return view('appointments.preview', compact('appointment'));
    }

    public function finalConfirm($id)
    {
        $appointment = Appointment::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            // 1. Actualizar estado
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
}
