<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;

class ProfileDoctorController extends Controller
{
    public function edit()
    {
        // Traemos todos los planes disponibles
        $plans = Plan::orderBy('price', 'asc')->get();
        
        // Obtenemos el doctor actual y sus configuraciones
        $doctor = auth()->user()->doctor;
        $doctor->load('settings.plan');
        
        return view('doctor.profile.edit', compact('doctor', 'plans'));
    }
}
