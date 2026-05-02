<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DoctorAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $doctor = auth()->user()->doctor;
        
        // Filtro por fecha (por defecto hoy)
        $date = $request->get('date', Carbon::today()->toDateString());

        $appointments = $doctor->appointments()
            ->with(['patient.user', 'service', 'address'])
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get();

        return view('doctor.appointments.index', compact('appointments', 'date'));
    }
}
