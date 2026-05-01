<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show(Doctor $doctor)
    {        
        $doctor->load(['specialties', 'services', 'addresses' => function($q) {
            $q->where('status', true);
        }]);

        return view('public.doctor-profile', compact('doctor'));
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

}
