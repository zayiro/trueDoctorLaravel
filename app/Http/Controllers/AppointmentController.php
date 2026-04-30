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
        // 1. Validar datos básicos
        $request->validate([
            'doctor_id' => 'required',
            'address_id' => 'required',
            'appointment_date' => 'required|date',
            // el paciente suele ser el usuario autenticado
        ]);

        // 2. Usar el Service para validar disponibilidad real (seguridad)
        $isAvailable = $this->appointmentService->isAvailable(
            $request->doctor_id,
            $request->address_id,
            $request->appointment_date
        );

        if (!$isAvailable) {
            return back()->withErrors('El horario ya no está disponible.');
        }

        // 3. El Controlador usa el Modelo para guardar
        Appointment::create([
            'patient_id' => auth()->user()->patient->id,
            'doctor_id' => $request->doctor_id,
            'address_id' => $request->address_id,
            'appointment_date' => $request->appointment_date,
            'status' => 'pending'
        ]);

        return redirect()->route('appointments.index')->with('success', 'Cita reservada.');
    }
}

