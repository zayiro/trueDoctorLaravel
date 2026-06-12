<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Si el rol es doctor, el ID es el suyo de forma directa y blindada (Aplica para particular y staff)
        return (int) $user->doctor->id;
    }

    /**
     * Despacha la vista principal de indisponibilidades inyectando las dependencias por rol o contexto activo.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');
        $addresses = collect();
        $clinicDoctors = collect();

        // ESCENARIO 1: El actor es una Clínica institucional pura (Tu lógica de producción intacta)
        if ($user->role === 'clinic') {
            $clinic = $user->clinic;
            $clinicDoctors = $clinic->doctors()->with('user')->get();
            $addresses = Address::where('clinic_id', $clinic->id)->where('status', true)->get();

            $addressIds = $addresses->pluck('id')->toArray();
            $unavailabilities = Unavailability::whereIn('address_id', $addressIds)
                ->with(['doctor.user', 'address'])
                ->orderBy('start_date', 'asc')
                ->get();
        } 
        // ESCENARIO 2: El actor es un Médico (Evaluando el espacio de trabajo conmutado)
        else {
            $doctor = $user->doctor;

            // Caso A: El médico está operando dentro del contexto de una Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                $clinicId = (int)$context['id'];

                // El médico solo puede seleccionar sedes pertenecientes a esta clínica corporativa activa
                $addresses = Address::where('clinic_id', $clinicId)->where('status', true)->get();
                $addressIds = $addresses->pluck('id')->toArray();

                // Captura únicamente las ausencias de este médico dentro de la infraestructura corporativa
                $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                    ->whereIn('address_id', $addressIds)
                    ->with(['doctor.user', 'address'])
                    ->orderBy('start_date', 'asc')
                    ->get();
            } 
            // Caso B: El médico opera en su Consultorio Particular (Lógica de producción intacta)
            else {
                $addresses = Address::where('doctor_id', $doctor->id)
                    ->orWhereIn('id', function($q) use ($doctor) {
                        $q->select('address_id')->from('schedules')->where('doctor_id', $doctor->id);
                    })
                    ->where('status', true)
                    ->get();

                $unavailabilities = Unavailability::where('doctor_id', $doctor->id)
                    ->with(['doctor.user', 'address'])
                    ->orderBy('start_date', 'asc')
                    ->get();
            }
        }

        $selectedDoctorId = $request->input('doctor_id');

        return view('partner.unavailabilities.index', compact(
            'unavailabilities', 
            'addresses', 
            'clinicDoctors', 
            'selectedDoctorId'
        ));
    }
    /**
     * Valida la pertenencia y propiedad física de la sede si se envía en la petición de forma multiperfil.
     */
    private function checkAddressOwnership($addressId, $user): void
    {
        if ($addressId) {
            $address = Address::findOrFail($addressId);
            $context = session('doctor_context');
            
            if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
                abort(403, 'La sede seleccionada no pertenece a tu clínica institucional.');
            }
            
            if ($user->role === 'doctor') {
                // Sub-caso: Médico registrando en contexto de clínica aliada
                if (($context['type'] ?? 'particular') === 'clinic') {
                    if ((int)$address->clinic_id !== (int)$context['id']) {
                        abort(403, 'La sede seleccionada no corresponde a la clínica activa de tu espacio de trabajo.');
                    }
                    
                    // Doble verificación: Garantizar vínculo en nómina aprobada
                    $isLinked = DB::table('clinic_doctor')
                        ->where('clinic_id', $address->clinic_id)
                        ->where('doctor_id', $user->doctor->id)
                        ->where('status', 'approved')
                        ->exists();
                        
                    if (!$isLinked) {
                        abort(403, 'No tienes privilegios de staff autorizados para esta sede.');
                    }
                } 
                // Sub-caso: Médico en consultorio propio (Producción estándar)
                else {
                    if ($address->doctor_id !== $user->doctor->id) {
                        abort(403, 'La sede seleccionada no te pertenece de forma privada.');
                    }
                }
            }
        }
    }

    /**
     * Almacena el bloqueo validando las fechas, horas exactas y choques contra la tabla appointments.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $context = session('doctor_context');

        // Validación adaptada a Laravel 11 usando la regla Rule para el softdelete de sedes
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'address_id' => [
                // Si es doctor en contexto clínica, forzamos la selección de sede, no permitimos bloqueo global
                ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') ? 'required' : 'nullable',
                Rule::exists('addresses', 'id')->whereNull('deleted_at')
            ],
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

        $appointmentsQuery->when($request->address_id, function($q) use ($request) {
            return $q->where('address_id', $request->address_id);
        });

        if ($request->filled('start_time') && $request->filled('end_time')) {
            $appointmentsQuery->where(function ($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                  ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
            });
        }

        $conflicts = $appointmentsQuery->with('patient.user')->get();

        if ($conflicts->count() > 0 && !$request->has('force_save')) {
            return back()->with([
                'conflict_appointments' => $conflicts,
                'old_data'              => $request->all()
            ])->withInput();
        }

        Unavailability::create([
            'doctor_id'  => $doctorId,
            'address_id' => $request->address_id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'start_time' => $request->start_time, 
            'end_time'   => $request->end_time,   
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
        $context = session('doctor_context');

        if ($user->role === 'clinic') {
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
            // El doctor independiente o staff solo remueve sus propios registros
            if ($unavailability->doctor_id !== $user->doctor->id) {
                return back()->with('error', 'No tienes permiso para eliminar esta ausencia.');
            }

            // Si está en contexto clínico, validar además que la indisponibilidad corresponda a sedes de esta clínica
            if (($context['type'] ?? 'particular') === 'clinic') {
                $addressClinicId = Address::where('id', $unavailability->address_id)->value('clinic_id');
                if ((int)$addressClinicId !== (int)$context['id']) {
                    abort(403, 'No posees autorización sobre este registro en el entorno activo.');
                }
            }
        }

        $unavailability->delete();

        return back()->with('success', 'El periodo de indisponibilidad ha sido eliminado. Los turnos vuelven al buscador público.');
    }
}
