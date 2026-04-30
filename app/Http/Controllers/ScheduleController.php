<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Address $address)
    {
        // Seguridad: ¿Es del doctor logueado?
        if ($address->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $schedules = $address->schedules()->orderBy('day')->get();

        return view('doctor.schedules.index', compact('address', 'schedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'day' => 'required|integer|between:0,6',
            'repeat_days' => 'nullable|array',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'duration' => 'required|integer',
        ]);

        $daysToRegister = collect($request->input('repeat_days', []))
            ->push($request->day)
            ->unique();

        // 📍 AQUÍ VA LA LÓGICA DE BORRADO
        // Esto evita que si ya había un horario el lunes, se duplique con el nuevo
        Schedule::where('address_id', $request->address_id)
                ->whereIn('day', $daysToRegister)
                ->delete();

        // Luego procedes a crear los nuevos
        foreach ($daysToRegister as $day) {
            Schedule::create([
                'address_id' => $request->address_id,
                'day' => $day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => $request->duration,
            ]);
        }

        return back()->with('success', '¡Horarios actualizados correctamente!');
    }

    public function destroy(Schedule $schedule)
    {
        // Seguridad: verificar que el horario pertenece a una sede del doctor logueado
        if ($schedule->address->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $schedule->delete();

        return back()->with('success', 'Franja horaria eliminada.');
    }
}
