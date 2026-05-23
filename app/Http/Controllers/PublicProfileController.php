<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;

use App\Notifications\NewAppointmentNotification;

use App\Models\Doctor;
use App\Models\Service;
use App\Models\Address;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PublicProfileController extends Controller
{
    public function show(Doctor $partner)
    {
        $partner->load([
            'user',
            'specialties', 
            'addresses' => function($q) {
                $q->where('status', true)
                ->with(['city', 'services' => function($query) {
                    $query->where('active', true);
                }]);
            }
        ]);

        session(['current_doctor_id' => $partner->id]);
                
        $seoTitle = "Dr(a). " . ucfirst($partner->user->name) . ' | Orientación Médica';
        $seoDescription = $partner->bio ?? 'Especialista certificado de OpenDoctor, reserva tu cita en línea';
        $metaRobots = "index, follow";
        
        return view('public.public-profile', ['doctor' => $partner, 'seoTitle' => $seoTitle, 'seoDescription' => $seoDescription, 'metaRobots' => $metaRobots]);
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
}
