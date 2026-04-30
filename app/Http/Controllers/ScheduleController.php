<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Guarda o actualiza los horarios de una sede específica.
     */
    public function store(Request $request, Address $address)
    {
        // Seguridad: Solo el dueño y si la sede está activa
        if ($address->doctor_id !== Auth::user()->doctor->id || !$address->status) {
            abort(403, 'Esta sede no está activa o no te pertenece.');
        }

        $validated = $request->validate([
            'day' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'duration'    => 'required|integer|min:15',
        ]);

        $address->schedules()->create($validated);

        return back()->with('success', 'Horario configurado para esta sede.');
    }
}

