<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UnavailabilityController extends Controller
{
    /**
     * Resuelve el doctor_id objetivo aplicando filtros estrictos de seguridad de Co-propiedad.
     */
    private function resolveDoctorId(Request $request, $user): int
    {
        if ($user->role === 'clinic') {
            $request->validate(['doctor_id' => 'required|exists:doctors,id']);
            
            // 🛡️ Seguridad Multi-tenant: Validar que el médico esté adscrito al staff de la clínica
            $isLinked = $user->clinic->doctors()->where('doctor_id', $request->doctor_id)->exists();
            if (!$isLinked) {
                abort(403, 'El especialista seleccionado no trabaja para esta clínica.');
            }
            return (int) $request->doctor_id;
        }

        // Si el rol es doctor, el ID es el suyo de forma directa y blindada
        return (int) $user->doctor->id;
    }

    /**
     * Despacha la vista principal de indisponibilidades inyectando las dependencias por rol.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $addresses = collect();
        $clinicDoctors = collect();

        // 1. Cargar datos si el actor es una Clínica institucional
        if ($user->role === 'clinic') {
            $clinic = $user->clinic;
            
            // Obtener médicos adscritos a la nómina de la clínica
            $clinicDoctors = $clinic->doctors()->with('user')->get();
            
            // Obtener únicamente las sedes físicas o virtuales de la clínica
            $addresses = Address::where('clinic_id', $clinic->id)->where('status', true)->get();

            // Capturar el listado de ausencias de todos los médicos dentro de las sedes de esta clínica
            $addressIds = $addresses->pluck('id')->toArray();
            $unavailabilities = Unavailability::whereIn('address_id', $addressIds)
                ->with(['doctor.user', 'address'])
                ->orderBy('start_date', 'asc')
                ->get();
        } else {
            // 2. Cargar datos si el actor es un Médico Independiente
            $doctor = $user->doctor;
            
            // Obtener sedes propias y de clínicas donde el médico tiene horarios asignados
            $addresses = Address::where('doctor_id', $doctor->id)
                ->orWhereIn('id', function($q) use ($doctor) {
                    $q->select('address_id')->from('schedules')->where('doctor_id', $doctor->id);
                })
                ->where('status', true)
                ->get();

            // Capturar las ausencias exclusivas de este médico
            $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                ->with(['doctor.user', 'address'])
                ->orderBy('start_date', 'asc')
                ->get();
        }

        // Capturar ID seleccionado en caso de venir de un flujo de recarga por conflicto de citas
        $selectedDoctorId = $request->input('doctor_id');

        return view('partner.unavailabilities.index', compact(
            'unavailabilities', 
            'addresses', 
            'clinicDoctors', 
            'selectedDoctorId'
        ));
    }

    /**
     * Valida la pertenencia y propiedad física de la sede si se envía en la petición.
     */
    private function checkAddressOwnership($addressId, $user): void
    {
        if ($addressId) {
            $address = Address::findOrFail($addressId);
            
            if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
                abort(403, 'La sede seleccionada no pertenece a tu clínica institucional.');
            }
            
            if ($user->role === 'doctor' && $address->doctor_id !== $user->doctor->id) {
                abort(403, 'La sede seleccionada no te pertenece de forma privada.');
            }
        }
    }
    /**
     * Almacena el bloqueo validando las fechas, horas exactas y choques contra la tabla appointments.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validación adaptada a Laravel 11 usando la regla Rule para el softdelete de sedes
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'address_id' => ['nullable', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
            'start_time' => 'required_with:end_time|nullable|date_format:H:i',
            'end_time'   => 'required_with:start_time|nullable|date_format:H:i|after:start_time',
            'reason'     => 'nullable|string|max:255',
        ]);

        $doctorId = $this->resolveDoctorId($request, $user);
        $this->checkAddressOwnership($request->address_id, $user);

        // 🔍 MÁQUINA DE DETECCIÓN DE CONFLICTOS HORARIOS CON PACIENTES
        $appointmentsQuery = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['confirmed', 'pending']);

        // Filtrar por sede si el bloqueo es específico y no global
        $appointmentsQuery->when($request->address_id, function($q) use ($request) {
            return $q->where('address_id', $request->address_id);
        });

        // Filtrar por rango horario de tu migración (Si start_time y end_time no son nulos)
        if ($request->filled('start_time') && $request->filled('end_time')) {
            $appointmentsQuery->where(function ($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                  ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
            });
        }

        $conflicts = $appointmentsQuery->with('patient.user')->get();

        // Si hay conflictos y el formulario no viaja con la confirmación forzada de riesgo
        if ($conflicts->count() > 0 && !$request->has('force_save')) {
            return back()->with([
                'conflict_appointments' => $conflicts,
                'old_data'              => $request->all()
            ])->withInput();
        }

        // 💾 Persistencia en la tabla unavailabilities con los campos exactos de tu migración
        Unavailability::create([
            'doctor_id'  => $doctorId,
            'address_id' => $request->address_id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'start_time' => $request->start_time, // Inyectamos hora inicio
            'end_time'   => $request->end_time,   // Inyectamos hora fin
            'reason'     => $request->reason,
        ]);

        return back()->with('success', 'Ausencia e indisponibilidad guardada correctamente.');
    }

    /**
     * Remueve el bloqueo validando jerarquías Multi-tenant estrictas.
     */
    public function destroy(Unavailability $unavailability)
    {
        $user = Auth::user();

        if ($user->role === 'clinic') {
            // 🛡️ SEGURIDAD DE CO-PROPIEDAD: La clínica solo borra el bloqueo si ocurrió en una sede de SU propiedad
            if (empty($unavailability->address_id)) {
                abort(403, 'Las clínicas no pueden remover bloqueos globales de los médicos.');
            }

            $ownsAddress = Address::where('id', $unavailability->address_id)
                ->where('clinic_id', $user->clinic->id)
                ->exists();

            if (!$ownsAddress) {
                return back()->with('error', 'No tienes autorización para remover ausencias configuradas fuera de tus instalaciones.');
            }
        } else {
            // El doctor independiente solo modifica sus propios registros autónomos privados
            if ($unavailability->doctor_id !== $user->doctor->id) {
                return back()->with('error', 'No tienes permiso para eliminar esta ausencia.');
            }
        }

        $unavailability->delete();

        return back()->with('success', 'El periodo de indisponibilidad ha sido eliminado. Los turnos vuelven al buscador público.');
    }
}
