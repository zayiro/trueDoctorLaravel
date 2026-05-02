<?php

namespace App\Http\Controllers;

use App\Models\Appointment; // Importa el modelo
use App\Services\AppointmentService;
use Illuminate\Http\Request;

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
}
