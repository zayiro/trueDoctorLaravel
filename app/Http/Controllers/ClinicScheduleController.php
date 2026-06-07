<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Address;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicScheduleController extends Controller
{
    /**
     * Helper metodológico para capturar el contexto comercial de la clínica de forma segura.
     */
    private function getClinicContext()
    {
        $user = Auth::user();
        if ($user->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para clínicas habilitadas.');
        }
        return $user->clinic ?? abort(404, 'Perfil de centro médico no encontrado.');
    }

    /**
     * Listar la grilla horaria actual de los especialistas en las sedes físicas de la clínica.
     */
    public function index()
    {
        $clinic = $this->getClinicContext();

        // 1. Obtener únicamente las sedes físicas o consultorios vigentes de esta clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        $addressIds = $addresses->pluck('id');

        // 2. Recuperar el Staff de médicos aprobados en la nómina corporativa
        $staffDoctors = $clinic->doctors()
            ->where('clinic_doctor.status', 'approved')
            ->with('user')
            ->get();

        // 3. Consultar los horarios semanales vigentes golpeando tus índices compuestos
        $schedules = Schedule::whereIn('address_id', $addressIds)
            ->with(['doctor.user', 'address'])
            ->orderBy('address_id')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        return view('partner.clinic.schedules.index', compact('schedules', 'addresses', 'staffDoctors', 'clinic'));
    }
    
    /**
     * Registrar un nuevo bloque de atención horaria semanal para un especialista.
     * 🛡️ BLINDAJE SAAS: Soporta clonación masiva y valida choques globales e inhabilidades.
     */
    public function store(Request $request)
    {
        $clinic = $this->getClinicContext();

        // 1. Validaciones de formato estrictas
        $validated = $request->validate([
            'doctor_id'      => 'required|exists:doctors,id',
            'address_id'     => 'required|exists:addresses,id',
            'day'            => 'required|integer|min:1|max:7', // Día principal de origen
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
            'replicate_days' => 'nullable|array', // Checkboxes dinámicos de Alpine
            'replicate_days.*' => 'integer|min:1|max:7',
        ]);

        // Confirmar propiedad de la sede y vigencia del doctor en el staff
        $address = Address::where('id', $validated['address_id'])->where('clinic_id', $clinic->id)->firstOrFail();
        $isDoctorInStaff = $clinic->doctors()->where('doctor_id', $validated['doctor_id'])->where('clinic_doctor.status', 'approved')->exists();

        if (!$isDoctorInStaff) {
            return back()->with('error', 'El especialista seleccionado no forma parte activa de tu staff médico aprobado.');
        }

        // Armamos el listado completo de días a procesar (El principal + los clonados)
        $targetDays = [$validated['day']];
        if ($request->has('replicate_days')) {
            // Combinamos los arrays y eliminamos duplicados por si marcaron el mismo día de origen
            $targetDays = array_unique(array_merge($targetDays, $validated['replicate_days']));
        }

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        try {
            // 2. Ejecutar el guardado masivo dentro de una transacción segura
            DB::transaction(function () use ($targetDays, $validated, $address, $startTime, $endTime, $clinic) {
                
                $daysMap = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

                foreach ($targetDays as $dayId) {
                    
                    // A. 🛑 CONTROL DE CHOQUE GLOBAL CORREGIDO (orWhere reemplaza al .or erróneo)
                    $hasGlobalOverlap = Schedule::where('doctor_id', $validated['doctor_id'])
                        ->where('day', $dayId)
                        ->where(function ($query) use ($startTime, $endTime) {
                            $query->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($q) use ($startTime, $endTime) {
                                      $q->where('start_time', '<=', $startTime)
                                        ->where('end_time', '>=', $endTime);
                                  });
                        })
                        ->with('address')
                        ->first();

                    if ($hasGlobalOverlap) {
                        $lugarChoque = $hasGlobalOverlap->address->clinic_id === $clinic->id 
                            ? "en tu sede '{$hasGlobalOverlap->address->name}'" 
                            : "en su agenda externa / privada independiente";
                            
                        throw new \Exception("Conflicto de agenda el día {$daysMap[$dayId]}: El especialista ya cuenta con un bloque configurado de {$hasGlobalOverlap->start_time} a {$hasGlobalOverlap->end_time} {$lugarChoque}.");
                    }

                    // B. ⚠️ DETECCIÓN DE INHABILIDADES VIGENTES
                    $hasUnavailability = DB::table('unavailabilities')
                        ->where('doctor_id', $validated['doctor_id'])
                        ->where(function ($query) use ($address) {
                            $query->whereNull('address_id')->orWhere('address_id', $address->id);
                        })
                        ->where('end_date', '>=', now()->toDateString())
                        ->where(function ($query) use ($startTime, $endTime) {
                            $query->where(function ($q) use ($startTime, $endTime) {
                                $q->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($subQ) use ($startTime, $endTime) {
                                      $subQ->where('start_time', '<=', $startTime)->where('end_time', '>=', $endTime);
                                  });
                            })
                            ->orWhere(function ($q) {
                                $q->whereNull('start_time')->whereNull('end_time');
                            });
                        })
                        ->first();

                    if ($hasUnavailability) {
                        $motivo = $hasUnavailability->reason ? " ('{$hasUnavailability->reason}')" : "";
                        throw new \Exception("No se pudo asignar el día {$daysMap[$dayId]}. El especialista tiene registrada una inhabilidad activa para ese rango horario{$motivo}.");
                    }

                    // C. Inserción limpia si pasó todos los filtros de la red
                    Schedule::create([
                        'doctor_id'  => $validated['doctor_id'],
                        'address_id' => $validated['address_id'],
                        'day'        => $dayId,
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                    ]);
                }
            });

            return redirect()->route('partner.clinic.schedules.index')
                ->with('success', 'La jornada horaria y sus respectivas replicaciones masivas han sido sincronizadas con éxito.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Dar de baja un bloque horario semanal del especialista.
     */
    public function destroy(Schedule $schedule)
    {
        $clinic = $this->getClinicContext();

        // Validar propiedad de la sede del horario antes de impactar la base de datos
        $isOwner = Address::where('id', $schedule->address_id)->where('clinic_id', $clinic->id)->exists();
        if (!$isOwner) { 
            abort(403, 'Operación denegada.'); 
        }

        // Se elimina la fila base de la disponibilidad
        $schedule->delete();

        return redirect()->route('partner.clinic.schedules.index')
            ->with('success', 'El horario de atención ha sido removido. El motor de búsquedas ha liberado el espacio en la web.');
    }
} // Cierre definitivo del controlador ClinicScheduleController
