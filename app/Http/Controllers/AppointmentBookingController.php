<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppointmentBookingController extends Controller
{
    /**
     * Muestra el formulario unificado de agendamiento (SPA).
     */
    public function create()
    {
        return view('partner.booking.create');
    }

    /**
     * Actualiza los campos del flujo de agendamiento en la sesión.
     */
    public function updateSession(Request $request)
    {
        $group = $request->input('group'); // Puede ser 'root', 'patient' o 'appointment'
        $key = $request->input('key');
        $value = $request->input('value');

        if ($group === 'root') {
            session(["appointment_flow.{$key}" => $value]);
        } elseif ($group === 'patient' && $key === 'all') {
            session(["appointment_flow.patient" => $value]);
        } else {
            session(["appointment_flow.{$group}.{$key}" => $value]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Limpia los datos temporales del flujo en la sesión al finalizar.
     */
    public function clearSession()
    {
        session()->forget('appointment_flow');
        return response()->json(['status' => 'success']);
    }
}
