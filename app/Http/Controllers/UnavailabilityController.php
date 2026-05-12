<?php

namespace App\Http\Controllers;

use App\Models\Unavailability;
use App\Models\Appointment;
use Illuminate\Http\Request;

class UnavailabilityController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'address_id' => 'nullable|exists:addresses,id',
        ]);

        $doctorId = auth()->user()->doctor->id;

        // 🔍 BUSCAR CONFLICTOS ANTES DE GUARDAR
        $conflicts = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['confirmed', 'pending'])
            ->when($request->address_id, function($q) use ($request) {
                return $q->where('address_id', $request->address_id);
            })
            ->with('patient.user')
            ->get();

        // Si hay conflictos y no se ha confirmado forzosamente
        if ($conflicts->count() > 0 && !$request->has('force_save')) {
            return back()->with([
                'conflict_appointments' => $conflicts,
                'old_data' => $request->all()
            ])->withInput();
        }

        // Guardar si no hay conflictos o si se aceptó el riesgo
        Unavailability::create([
            'doctor_id'  => $doctorId,
            'address_id' => $request->address_id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
        ]);

        return back()->with('success', 'Ausencia registrada.');
    }

    public function destroy(Unavailability $unavailability)
    {
        // 1. Validar que el doctor autenticado sea el dueño de este registro
        if ($unavailability->doctor_id !== auth()->user()->doctor->id) {
            return back()->with('error', 'No tienes permiso para eliminar esta ausencia.');
        }

        // 2. Ejecutar la eliminación
        $unavailability->delete();

        // 3. Retornar con mensaje de éxito
        return back()->with('success', 'El periodo de ausencia ha sido eliminado y el horario vuelve a estar disponible.');
    }
}
