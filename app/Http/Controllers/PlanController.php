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
        // Confirmación de pasar al plan gratuito (planreglas). Los planes de pago se contratan con el botón de pago.
        $request->validate(['plan' => 'required|string']);

        $user   = auth()->user();
        $rules  = app(\App\Services\Plans\PlanRules::class);
        $target = Plan::where('plan', $request->plan)->first();

        if (!$target || (float) $target->price > 0) {
            return redirect($rules->homeFor($user))->with('error', 'Este cambio de plan no está disponible.');
        }

        $decision = $rules->evaluate($user, $target);
        if (!$decision['ok']) {
            return redirect($rules->homeFor($user))->with('error', $decision['message']);
        }

        $doctor = $rules->doctorOf($user);
        if (!$doctor) {
            return redirect($rules->homeFor($user))->with('error', 'No encontramos tu perfil de médico.');
        }

        $summary = $rules->downgradeToFree($doctor, $target, 'solicitado por el médico');

        $msg = "Listo, ahora estás en el {$target->name}.";
        if ($summary['addresses_off'] > 0) {
            $msg .= " Se desactivaron {$summary['addresses_off']} " . ($summary['addresses_off'] === 1 ? 'sede' : 'sedes') . ' que superan el límite del plan; no se borró nada.';
        }

        return redirect($rules->homeFor($user))->with('success', $msg);
    }

    public function showPlans()
    {        
        $plans = Plan::where('active', 1)
            ->orderBy('price', 'asc')
             ->get();
             
        return view('plans.index', compact('plans'));
    }
    
}
