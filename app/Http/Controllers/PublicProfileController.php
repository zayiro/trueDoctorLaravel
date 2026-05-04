<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;

use App\Notifications\NewAppointmentNotification;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PublicProfileController extends Controller
{
    public function show(Doctor $doctor)
    {
        if (!$doctor) {
            return redirect('/')
                ->with('error', 'El perfil del doctor solicitado no existe.');
        }

        $doctor->load([
            'specialties', 
            'services' => fn($q) => $q->where('active', true),
            'addresses' => fn($q) => $q->where('status', true)->with('city')
        ]);

        return view('public.public-profile', compact('doctor'));
    }

    public function getAvailability(Doctor $doctor, Request $request)
    {
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);
        
        $events = [];
        $schedules = $doctor->addresses()->with('schedules')->get()->pluck('schedules')->flatten();

        foreach ($schedules as $schedule) {
            // Generamos eventos visuales para FullCalendar basados en los horarios base
            // (Similar a la lógica anterior pero filtrando por el ID del doctor)
        }

        return response()->json($events);
    }

    public function preview(Request $request)
    {
        $service = Service::with('doctor.user')->findOrFail($request->service);
        $address = $request->address ? Address::with('city')->find($request->address) : null;
        $datetime = Carbon::parse($request->datetime);
        
        // Datos del paciente que vienen del paso previo
        $notes = $request->notes;
        $phone = $request->phone;
        $identification = $request->identification;

        return view('public.appointments.preview', compact(
            'service', 'address', 'datetime', 'notes', 'phone', 'identification'
        ));
    }

    public function book(Request $request, AppointmentService $appointmentService)
    {
        // Validar que los datos lleguen correctamente
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'datetime'   => 'required',
            'address_id' => 'nullable|exists:addresses,id',
        ]);

        // Buscar el servicio para obtener precio y duración "congelada"
        $service = Service::findOrFail($request->service_id);
        $dateTime = Carbon::parse($request->datetime);
        
        // Validar choque de horarios
        $available = $appointmentService->isAvailable(
            $service->doctor_id, 
            $dateTime->toDateString(), 
            $dateTime->toTimeString(), 
            $service->duration
        );

        if (!$available) {
            return back()->with('error', 'Lo sentimos, este horario acaba de ser ocupado. Por favor elige otro.');
        }

        // Crear la cita
        $appointment = Appointment::create([
            'patient_id' => auth()->user()->patient->id,
            'doctor_id'  => $service->doctor_id,
            'service_id' => $service->id,
            'address_id' => $request->address_id,
            'date'       => $dateTime->toDateString(),
            'start_time' => $dateTime->toTimeString(),
            'end_time'   => $dateTime->copy()->addMinutes($service->duration)->toTimeString(),
            'duration'     => $service->duration,
            'price'        => $service->price,
            'status'     => 'pending',
            'notes'      => $request->notes,
            'meeting_link' => ($service->type === 'virtual') ? url('/meet/' . Str::random(10)) : null,
        ]);

        // 1. Enviar correo al paciente
        Mail::to($appointment->patient->user->email)->send(new AppointmentConfirmed($appointment));

        // 2. Notificación al Doctor
        $doctorUser = $appointment->doctor->user;
        $doctorUser->notify(new NewAppointmentNotification($appointment));

        // Redirigir a la vista de éxito
        return redirect()->route('appointments.success', $appointment->id)
            ->with('success', '¡Tu cita ha sido agendada correctamente!');
    }

    public function success(Appointment $appointment)
    {
        // Seguridad: Solo el dueño de la cita puede verla
        if ($appointment->patient_id !== auth()->id()) { abort(403); }

        $appointment->load(['doctor.user', 'service', 'address.city']);
        return view('public.appointments.success', compact('appointment'));
    }

    public function processPatient(Request $request)
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
    }
}
