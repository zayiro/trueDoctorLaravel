<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Patient;
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

class AppointmentController extends Controller
{
    protected $appointmentService;
    protected $zoomService;

    public function __construct(AppointmentService $service, ZoomService $zoomService)
    {
        $this->appointmentService = $service;
        $this->zoomService = $zoomService;
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

        $appointment = Appointment::create($appointmentData);

        return redirect()->route('patient.appointments')
            ->with('success', 'Cita agendada. ' . ($service->type === 'virtual' ? 'El link de la reunión está listo.' : ''));
    }

    public function storeStepTwo(Request $request) 
    {
        $doctorId = session('current_doctor_id');

        if (!$doctorId) {
            return [
                "message" => "Doctor no seleccionado correctamente",
                "status" => false,
            ];
        }
        
        session([
            'booking_data' => [
                'doctor_id'  => $doctorId, 
                'service_id' => $request->service_id,
                'address_id' => $request->address_id,
                'date'       => $request->date,
                'hour'       => $request->hour,
            ]
        ]);

        return [
            "message" => "booking_data configurada correctamente",
            "status" => true,
        ];
    }

    public function patient()
    {
        if (!session()->has('booking_data')) {
            return redirect()->route('search');
        }

        $doctorId = session('current_doctor_id');
        $userId = auth()->id();
        
        $isSelfBooking = ($doctorId === $userId);
        
        return view('appointments.patient', compact('isSelfBooking'));
    }

    public function processPatient(Request $request)
    {        
        $bookingData = session('booking_data');
        if (!$bookingData || !isset($bookingData['doctor_id'])) {
            return redirect()->route('search')->with('error', 'Sesión inválida o datos de reserva incompletos.');
        }

        $rules = ['notes' => 'required|string|min:10|max:500'];
        $hasAccount = $request->has_account == 'yes';

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
            $hasAccount = $request->has_account == 'yes';
            
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

            $doctor = Doctor::with(['settings', 'user'])->where('user_id', $bookingData['doctor_id'])->first();
            if (!$doctor) {
                session()->forget(['booking_data', 'current_doctor_id']);
                return redirect()->route('search')->with('error', 'El médico seleccionado ya no se encuentra disponible.');
            }

            // Mapeo avanzado de la Sede con su respectiva relación con la Clínica
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

            // Validar disponibilidad en el motor core
            $isAvailable = $this->appointmentService->isAvailable(
                $doctor->id,
                $bookingData['date'],
                $startTime->format('H:i:s'),
                $duration
            );
            
            if (!$isAvailable) {
                return redirect()->route('search')->with('error', 'Lo sentimos, ese horario acaba de ser reservado por otro paciente.');
            }
            
            // 🔥 CONTROL INTELIGENTE DE REGLAS DE NEGOCIO (Clínica vs Consulta Privada)
            if ($address->clinic_id) {
                // Si la sede es de una clínica, heredamos las políticas de la institución
                $settings = $address->clinic->settings;
            } else {
                // Si es consulta privada, heredamos las políticas del médico autónomo
                $settings = $doctor->settings;
            }

            $requiresApproval = $settings ? (bool)$settings->requires_approval : false;
            $acceptsPayments = $settings ? (bool)$settings->accepts_online_payments : false;

            if ($acceptsPayments) {
                $requiresApproval = false; 
            }

            $status = $requiresApproval ? 'pending' : 'confirmed';

            // 💾 Crear la cita con trazabilidad total en el SaaS [2]
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id'  => $doctor->id,
                'clinic_id'  => $address->clinic_id, // Guarda el ID institucional si aplica [2]
                'service_id' => $serviceSpecific->id,
                'address_id' => $address->id,
                'date'       => $bookingData['date'],
                'start_time' => $startTime->format('H:i:s'),
                'end_time'   => $endTime->format('H:i:s'),
                'duration'   => $duration,
                'price'      => $price,
                'status'     => $status,
                'notes'      => $request->notes,
            ]);

            // Gestión automatizada de links de Telemedicina
            if ($serviceSpecific->type === 'virtual') {
                $appointment->update([
                    'meeting_link' => url('/meet/' . Str::random(10))
                ]);
            }

            // Limpieza del embudo de reserva en sesión
            session()->forget(['booking_data', 'current_doctor_id']);

            return redirect()->route('patient.appointments')
                ->with('success', $status === 'pending' ? 'Cita solicitada. Esperando aprobación del centro.' : 'Cita agendada exitosamente.');
        });
    }
}
