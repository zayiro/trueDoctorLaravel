<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileDoctorController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        // Cargamos la relación del doctor o clínica
        $doctor = $user->doctor; 
        
        return view('doctor.profile.edit', compact('user', 'doctor'));
    }
}
