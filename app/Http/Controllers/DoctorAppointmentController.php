<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DoctorAppointmentController extends Controller
{    
    public function index(Request $request)
    {
        // Si no viene fecha en el request, usamos hoy por defecto
        $date = $request->get('date', now()->toDateString());
        
        // Si el usuario hizo clic en "Ver Todo", mandamos un parámetro especial 'all'
        $showAll = $request->has('all');

        $query = Appointment::with(['patient.user', 'service', 'address'])
            ->where('doctor_id', auth()->user()->doctor->id)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($showAll) {
            $query->whereDate('date', '>=', now()->toDateString());
            $date = null; // Para que el input de fecha se vea vacío o indique "Todo"
        } else {
            $query->whereDate('date', $date);
        }

        $appointments = $query->get()->groupBy('address_id');     
                
        return view('partner.appointments.index', compact('appointments', 'date', 'showAll'));
    }

    public function complete(Appointment $appointment)
    {        
        if ($appointment->doctor_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar esta cita.');
        }
     
        $appointment->update([
            'status' => 'completed'
        ]);

        return back()->with('success', 'La cita ha sido marcada como completada.');
    }

    public function cancel(Appointment $appointment)
    {        
        if ($appointment->doctor_id !== auth()->id()) {
            abort(403, 'No tienes permiso para cancelar esta cita.');
        }
     
        $appointment->update([
            'status' => 'cancelled'
        ]);

        return back()->with('success', 'La cita ha sido marcada como cancelada.');
    }

    public function destroy(Appointment $appointment)
    {
        if ($appointment->doctor_id !== auth()->id()) {
            abort(403, 'No tienes permiso para eliminar esta cita.');
        }

        $appointment->delete();

        return back()->with('success', 'La cita ha sido eliminada exitosamente.');
    }
}
