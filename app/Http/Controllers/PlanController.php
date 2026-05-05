<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;

class PlanController extends Controller
{
    public function update(Request $request)
    {
        // 1. Validar que el valor enviado coincida con un plan real
        $request->validate([
            'plan' => 'required|exists:plans,plan'
        ]);

        $doctor = auth()->user()->doctor;
        
        // 2. Buscar el nuevo plan por el campo 'plan'
        $nuevoPlan = Plan::where('plan', $request->plan)->first();

        // 3. Ajustar estados de Sedes y Servicios según los límites
        $addresses = $doctor->addresses()->orderBy('created_at', 'asc')->get();

        foreach ($addresses as $index => $address) {
            // ¿Esta sede está dentro del límite del nuevo plan?
            $sedeActiva = ($index < $nuevoPlan->max_addresses);
            $address->update(['status' => $sedeActiva]);

            // Si la sede queda activa, ajustamos sus servicios internos
            if ($sedeActiva) {
                $services = $address->services()->orderBy('created_at', 'asc')->get();
                foreach ($services as $sIndex => $service) {
                    $service->update([
                        'active' => ($sIndex < $nuevoPlan->max_services_per_address)
                    ]);
                }
            }
        }

        // 4. Sincronizar la tabla de configuraciones
        $doctor->settings()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'plan_id' => $nuevoPlan->id,
                'max_addresses' => $nuevoPlan->max_addresses,
                'max_services_per_address' => $nuevoPlan->max_services_per_address
            ]
        );

        // 5. Actualizar el campo descriptivo en el modelo Doctor
        $doctor->update(['plan' => $nuevoPlan->plan]);

        return back()->with('success', "Plan {$nuevoPlan->name} actualizado con éxito.");
    }
}
