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

        if (!$bookingData) {
            return redirect()->route('search')->with('error', 'La sesión ha expirado.');
        }

        // 1. Validación Dinámica
        $rules = [
            'notes' => 'required|string|min:10|max:500',
        ];

        if (Auth::guest()) {
            $rules['name'] = 'required|string|min:3|max:100';
            $rules['email'] = 'required|email|unique:users,email';
            $rules['identification'] = 'required|numeric|digits_between:7,12|unique:patients,identification';
            $rules['phone'] = 'required|numeric|digits:10';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $bookingData) {
            // 2. Manejo de Usuario y Perfil de Paciente
            if (!Auth::check()) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->identification),
                ]);
                $user->assignRole('patient');

                $patient = Patient::create([
                    'user_id' => $user->id,
                    'identification' => $request->identification,
                    'phone' => $request->phone,
                ]);
                Auth::login($user);
            } else {
                $patient = auth()->user()->patient;
                
                // Si el usuario logueado no tiene perfil de paciente, lo creamos
                if (!$patient) {
                    $patient = Patient::create([
                        'user_id' => auth()->id(),
                        'identification' => $request->identification ?? '0000' . Str::lower(Str::random(5)),
                        'phone' => $request->phone ?? '0000000000',
                    ]);
                }
            }

            // 3. Preparar Datos de la Cita
            $service = Service::findOrFail($bookingData['service_id']);
            $startTime = \Carbon\Carbon::parse($bookingData['date'] . ' ' . $bookingData['hour']);
            $endTime = (clone $startTime)->addMinutes($service->duration);

            // 4. Crear la Cita (Usando $patient->id)
            $appointment = Appointment::create([
                'patient_id'   => $patient->id, // CLAVE: Usamos el ID del paciente, no del usuario
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

            // 5. Limpieza de Sesión
            session()->forget(['booking_data', 'current_doctor_id']);

            // 6. Redirección limpia al Preview
            return redirect()->route('appointments.preview', ['id' => $appointment->id])
                            ->with('success', 'Cita agendada correctamente');
        });
    }

    public function preview($id)
    {
        // 1. Obtenemos el perfil de paciente del usuario logueado
        $patient = auth()->user()->patient;

        if (!$patient) {
            return redirect()->route('search')->with('error', 'Perfil de paciente no encontrado.');
        }

        // 2. Buscamos la cita usando el ID del PACIENTE
        $appointment = Appointment::with(['service', 'address.city', 'doctor.user'])
            ->where('patient_id', $patient->id) // <--- Cambiado de Auth::id() a $patient->id
            ->findOrFail($id);  
            
            //dd($appointment);

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
