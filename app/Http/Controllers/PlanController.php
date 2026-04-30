<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['plan' => 'required|in:basico,avanzado']);
        $doctor = auth()->user()->doctor;

        // CASO 1: Lógica de Downgrade (Avanzado -> Basico)
        if ($request->plan === 'basico' && $doctor->plan === 'avanzado') {
            
            $addresses = $doctor->addresses()->orderBy('created_at', 'asc')->get();

            if ($addresses->count() > 2) {
                // 1. Activar los 2 primeros (por fecha de creación)
                $doctor->addresses()
                    ->whereIn('id', $addresses->take(2)->pluck('id'))
                    ->update(['status' => true]);

                // 2. Inactivar el resto (del tercero en adelante)
                $doctor->addresses()
                    ->whereIn('id', $addresses->skip(2)->pluck('id'))
                    ->update(['status' => false]);
            }
        }

        // CASO 2: SUBIR DE PLAN (Upgrade)
        if ($request->plan === 'avanzado') {
            $doctor->addresses()->update(['status' => true]);
        }

        $doctor->update(['plan' => $request->plan]);

        return back()->with('success', 'Plan actualizado. Se han ajustado tus consultorios activos.');
    }
}
