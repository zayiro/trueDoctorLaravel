<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\Unavailability; // Inyectado para el mapeo analítico de ausencias
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
    public function index(Request $request)
    {
        $clinic = $this->getClinicContext();

        // 1. Obtener únicamente las sedes físicas o consultorios vigentes de esta clínica
        $addresses = Address::where('clinic_id', $clinic->id)
            ->whereNull('doctor_id')
            ->whereNull('deleted_at')
            ->get();

        // Resolver de forma segura la sede física activa para el cronograma (por defecto la primera)
        $addressId = $request->input('address_id', $addresses->first()?->id);
        $address = $addresses->firstWhere('id', $addressId);

        // Si la clínica no posee infraestructura registrada, evitamos excepciones de objeto nulo en Blade
        if (!$address) {
            return view('partner.schedules.index', [
                'address' => null,
                'schedules' => collect(),
                'schedulesByDay' => collect(),
                'unavailabilities' => collect(),
                'availableDoctors' => collect(),
                'addresses' => collect(),
                'isReadOnly' => false,
                'daysMap' => [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo']
            ])->with('error', 'Por favor, registre primero una sede física para su clínica.');
        }

        // 2. Recuperar el Staff de médicos aprobados en la nómina corporativa
        $availableDoctors = $clinic->doctors()
            ->where('clinic_doctor.status', 'approved')
            ->with('user')
            ->get();

        // 3. Consultar los horarios semanales vigentes de la sede seleccionada
        $schedules = Schedule::where('address_id', $address->id)
            ->with(['doctor.user', 'address'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        // 4. Extraer las ausencias vigentes de todo el staff adscrito para la sección secundaria
        $doctorIds = $availableDoctors->pluck('id')->toArray();
        $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
            ->where('end_date', '>=', now()->toDateString())
            ->with('doctor.user')
            ->orderBy('start_date', 'asc')
            ->get();

        // Mapeo e indexación grupal para que coincida milimétricamente con las directivas de tu Blade (1=Lun, 7=Dom)
        $schedulesByDay = $schedules->groupBy('day');
        $daysMap = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

        // BANDERAS REQUISITO: Al ser el rol corporativo de la clínica administrando, la escritura está habilitada
        $isReadOnly = false;

        // 🌟 LA MAGIA: Apuntamos al Blade unificado pasando todas las llaves requeridas por la UI para borrar el error
        return view('partner.clinic.schedules.index', compact(
            'address', 
            'schedules', 
            'schedulesByDay', 
            'unavailabilities', 
            'availableDoctors', 
            'addresses', 
            'isReadOnly', 
            'daysMap'
        ));
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
            'day'            => 'required|integer|min:1|max:7', // Día principal de origen (1=Lun, 7=Dom)
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
                    // Buscamos colisiones horarias del doctor, pero eximiendo los cruces Híbridos (Físico vs Virtual)
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
                        ->whereHas('address', function ($query) use ($address) {
                            // REGLA DE ORO MULTI-TENANT SAAS:
                            // Solo disparamos la colisión si AMBAS sedes (la nueva y la existente) son 'physical'.
                            // Si cualquiera de las dos es 'virtual', se autoriza la coexistencia horaria en producción.
                            if ($address->type === 'physical') {
                                $query->where('type', 'physical');
                            } else {
                                // Si la sede de destino es virtual, solo choca si ya hay otro turno virtual a esa hora
                                $query->where('type', 'virtual');
                            }
                        })
                        ->with('address')
                        ->first();

                    if ($hasGlobalOverlap) {
                        $lugarChoque = $hasGlobalOverlap->address->clinic_id === $clinic->id 
                            ? "en tu sede '{$hasGlobalOverlap->address->name}'" 
                            : "en su agenda externa / privada independiente";
                            
                        throw new \Exception("Conflicto de agenda el día {$daysMap[$dayId]}: El especialista ya cuenta con un bloque configurado de {$hasGlobalOverlap->start_time->format('H:i')} a {$hasGlobalOverlap->end_time->format('H:i')} {$lugarChoque}.");
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

            // Redirección con parámetros limpios para mantener la sede activa en pantalla tras el envío
            return redirect()->route('partner.clinic.schedules.index', ['address_id' => $validated['address_id']])
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

        $addressId = $schedule->address_id;

        // Se elimina la fila base de la disponibilidad
        $schedule->delete();

        return redirect()->route('partner.clinic.schedules.index', ['address_id' => $addressId])
            ->with('success', 'El horario de atención ha sido removido. El motor de búsquedas ha liberado el espacio en la web.');
    }
} // Cierre definitivo del controlador ClinicScheduleController
