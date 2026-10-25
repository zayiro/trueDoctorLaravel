<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

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

    /**
     * Busca un paciente en el sistema por su número de identificación encriptado.
     */
    public function searchPatient(Request $request): JsonResponse
    {
        $identification = $request->input('identification');

        if (empty($identification)) {
            return response()->json(['found' => false, 'message' => 'Identificación vacía.'], 400);
        }

        // 1. Generamos el mismo hash para la identificación buscada
        $blindIndex = Patient::generateBlindIndex($identification);

        // 2. Buscamos de forma directa, indexada e instantánea en la BD
        $patient = Patient::with('user')
            ->where('identification_blind_index', $blindIndex)
            ->where('active', true)
            ->first();

        if ($patient) {
            return response()->json([
                'found' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->user->name ?? 'Paciente sin nombre',
                    'email' => $patient->user->email ?? '',
                    'phone' => $patient->phone ?? '', 
                    'identification' => $identification,
                ]
            ]);
        }

        return response()->json(['found' => false]);
    }
}
