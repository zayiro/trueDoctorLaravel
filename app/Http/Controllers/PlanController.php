<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\Service;

class PlanController extends Controller
{
    public function update(Request $request)
    {
        /*$request->validate(['plan' => 'required|exists:plans,plan']);

        $doctor = auth()->user()->doctor;
        $nuevoPlan = Plan::where('plan', $request->plan)->first();
        $limiteGlobal = $nuevoPlan->max_services; // Ejemplo: 3

        // 1. Ajustar Sedes
        $addresses = $doctor->addresses()->orderBy('created_at', 'asc')->get();
        foreach ($addresses as $index => $address) {
            $address->update(['status' => ($index < $nuevoPlan->max_addresses)]);
        }

        // 2. Ajustar Servicios (Lógica Robusta)
        // Traemos TODOS los servicios del doctor
        $allServices = Service::whereHas('addresses', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->orderBy('created_at', 'asc')->get();

        foreach ($allServices as $index => $service) {            
            $service->update([
                'active' => ($index < $limiteGlobal) 
            ]);
        }

        // 3. Actualizar Settings y Doctor
        $doctor->settings()->update(['plan_id' => $nuevoPlan->id]);
        $doctor->update(['plan' => $nuevoPlan->plan]);*/

        return back()->with('success', "Plan actualizado.");
    }

    public function showPlans()
    {        
        $plans = Plan::where('active', 1)
            ->orderBy('price', 'asc')
             ->get();
             
        return view('plans.index', compact('plans'));
    }
    
}
