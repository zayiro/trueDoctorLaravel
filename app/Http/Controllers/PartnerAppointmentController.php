<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Address;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\AppointmentService;

class PartnerAppointmentController extends Controller
{
    /**
     * Muestra el cronograma de citas unificado filtrado por el espacio de trabajo activo.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $context = session('doctor_context');
        $date = $request->input('date', now()->toDateString());
        $showAll = $request->has('all');

        // 1. RESOLVER LA NÓMINA DISPONIBLE PARA FILTRADO SEGÚN EL ÁMBITO
        if ($user->role === 'clinic') {
            $clinic = $user->clinic;
            // La clínica carga todo su staff médico aprobado para la barra de filtros institucional
            $availableDoctors = $clinic->doctors()->with('user')->get();
        } else {
            // El médico (Particular o Staff) opera su agenda de forma individualizada
            $availableDoctors = collect([]); 
        }

        // 2. MÁQUINA DE CONSULTA CENTRALIZADA MULTI-TENANT CONTEXTUAL
        // Usamos el scope del modelo que separa automáticamente: Clínica Pura vs Médico Particular vs Médico Staff
        $appointmentsQuery = Appointment::forCurrentContext();

        // Si es una clínica administrando, le permitimos filtrar adicionalmente por un médico de su nómina
        if ($user->role === 'clinic' && $request->filled('doctor_id')) {
            $appointmentsQuery->where('doctor_id', $request->doctor_id);
        }

        // 3. FILTRADO CRONOLÓGICO COMÚN (Producción intacta)
        if (!$showAll) {
            $appointmentsQuery->where('date', $date);
        } else {
            $appointmentsQuery->where('date', '>=', now()->toDateString());
        }

        // 4. AGRUPAMOS LAS CITAS POR SEDE (Garantiza compatibilidad absoluta con tu Blade original)
        $appointments = $appointmentsQuery->with(['patient.user', 'address', 'service'])
            ->orderBy('start_time', 'asc')
            ->get()
            ->groupBy('address_id');

        return view('partner.appointments.index', compact('appointments', 'availableDoctors', 'date', 'showAll'));
    }

        /**
     * Calcula las franjas horarias libres para el calendario público del SaaS.
     * Atiende las llamadas de Alpine.js en el perfil público y del formulario de reagendamiento táctico.
     */
    public function getSlots(Request $request)
    {
        // 1. Validación polimórfica flexible adaptada a la co-propiedad del SaaS
        $request->validate([
            'address_id' => 'required|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'doctor_id'  => 'required_without:clinic_id|nullable|integer',
            'clinic_id'  => 'required_without:doctor_id|nullable|integer',
            'is_virtual' => 'nullable|string'
        ]);

        $addressId = $request->integer('address_id');
        $dateInput = $request->input('date');
        $isVirtual = $request->input('is_virtual') === 'true';
        
        // Recuperamos el modelo de la sede para auditar los contextos de seguridad
        $address = Address::findOrFail($addressId);
        $doctorId = null;

        // --------------------------------------------------------------------
        // ESCENARIO A: Petición desde el Perfil de una Clínica Corporativa
        // --------------------------------------------------------------------
        if ($request->filled('clinic_id')) {
            $clinicId = $request->integer('clinic_id');

            // Regla de Oro: Validamos que la sede física o virtual pertenezca legítimamente a esta clínica
            if ($address->clinic_id !== $clinicId) {
                return response()->json(['error' => 'La sede seleccionada no coincide con la infraestructura de la clínica.'], 403);
            }

            // Identificamos cuál es el doctor asignado a la agenda de esta sede institucional.
            // Para entornos corporativos, buscamos el propietario de la franja en la tabla schedules.
            $doctorId = DB::table('schedules')
                ->where('address_id', $addressId)
                ->where('day', Carbon::parse($dateInput)->dayOfWeekIso)
                ->value('doctor_id');

            // Fallback preventivo: Si la agenda está vacía para el día, intentamos recuperar el primer médico 
            // aprobado en la nómina de la clínica que atienda la especialidad requerida.
            if (!$doctorId) {
                $doctorId = DB::table('clinic_doctor')
                    ->where('clinic_id', $clinicId)
                    ->where('status', 'approved')
                    ->value('doctor_id');
            }

            if (!$doctorId) {
                return response()->json([]); // Retorno limpio si la clínica no tiene personal asignado aún
            }
        } 
        // --------------------------------------------------------------------
        // ESCENARIO B: Petición desde el Perfil de un Médico Particular Autónomo
        // --------------------------------------------------------------------
        else {
            $doctorId = $request->integer('doctor_id');

            // Seguridad de Aislamiento: Validar que el doctor exista y esté aprobado en producción
            $doctorExists = DB::table('doctors')
                ->where('id', $doctorId)
                ->where('validation_status', 'approved')
                ->exists();

            if (!$doctorExists) {
                return response()->json(['error' => 'El especialista solicitado no está habilitado.'], 422);
            }
        }

        // 2. Invocamos al servicio centralizado de la aplicación con el ID del doctor resuelto
        $appointmentService = app(AppointmentService::class);

        // 3. Ejecutamos el motor de cálculo cruzado libre de colisiones multiperfil
        $slots = $appointmentService->getAvailableSlots(
            $addressId,
            $dateInput,
            $doctorId,
            $isVirtual
        );

        // 4. Retornamos la matriz en un JSON limpio para que Alpine.js pinte los botones
        return response()->json($slots);
    }

    /**
     * Devuelve los servicios vinculados a una sede con sus precios del pivot.
     * Alimenta de forma reactiva el Paso 2 de servicios del perfil público.
     */
    public function getServices(Address $address)
    {
        // 1. Validamos que la sede se encuentre activa en la plataforma
        if (!$address->status) {
            return response()->json([], 404);
        }

        // 2. Mapeamos los servicios asignados con sus precios y tiempos del pivot address_service
        $services = $address->services()
            ->where('active', true)
            ->get()
            ->map(function ($service) {
                return [
                    'id'       => $service->id,
                    'name'     => $service->name,
                    'type'     => $service->type,
                    'duration' => (int) $service->pivot->duration, // Duración específica de esta sede
                    'price'    => (float) $service->pivot->price,    // Precio específico de esta sede
                ];
            });

        // 3. Retornamos la respuesta en un JSON limpio para que Alpine.js no se rompa
        return response()->json($services);
    }

    private function validateAppointmentAccess(Appointment $appointment)
    {
        $user = auth()->user();
        $context = session('doctor_context');

        if ($context['type'] === 'particular') {
            // Solo puede acceder a sus propias citas particulares
            if ($appointment->doctor_id !== $user->doctor->id || $appointment->clinic_id !== null) {
                abort(403, 'No tienes permiso para gestionar esta cita.');
            }
        } elseif ($context['type'] === 'clinic') {
            // Solo puede acceder a citas de su clínica
            if ($appointment->clinic_id !== $context['id']) {
                abort(403, 'No tienes permiso para gestionar esta cita.');
            }
        }
    }
        
    /**
     * Filtro estricto de seguridad multi-inquilino para las acciones de la cita.
     */
    private function authorizeAppointmentAction(Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->role === 'clinic') {
            // La clínica solo opera si la cita ocurrió en sus instalaciones
            if ($appointment->clinic_id !== $user->clinic->id) {
                abort(403, 'No tienes permiso para gestionar esta cita institucional.');
            }
        } else if ($user->role === 'doctor') {
            // ⭐ NUEVO: Doctor en contexto de clínica
            $doctorContext = session('doctor_context');
            
            if ($doctorContext && $doctorContext['type'] === 'clinic') {
                // Validar contra clinic_id si está en contexto de clínica
                if ($appointment->clinic_id !== $doctorContext['id']) {
                    abort(403, 'No tienes permiso para gestionar esta cita institucional.');
                }
            } else {
                // Validar contra doctor_id si está en contexto particular
                if ($appointment->doctor_id !== $user->doctor->id) {
                    abort(403, 'No tienes permiso para gestionar esta cita privada.');
                }
            }
        }
    }

    public function complete(Appointment $appointment)
    {        
        // Primero valida el acceso (que no sea URL directo de otro doctor)
        $this->validateAppointmentAccess($appointment);

        // 🔥 CORREGIDO: Validación de seguridad multiperfil sin bugs de IDs cruzados
        $this->authorizeAppointmentAction($appointment);
        
     
        $appointment->update([
            'status' => 'completed'
        ]);

        return back()->with('success', 'La cita ha sido marcada como completada.');
    }

    public function cancel(Appointment $appointment)
    {        
        // Primero valida el acceso (que no sea URL directo de otro doctor)
        $this->validateAppointmentAccess($appointment);

        $this->authorizeAppointmentAction($appointment);
     
        $appointment->update([
            'status' => 'cancelled'
        ]);

        return back()->with('success', 'La cita ha sido marcada como cancelada.');
    }

    public function destroy(Appointment $appointment)
    {
        // Primero valida el acceso (que no sea URL directo de otro doctor)
        $this->validateAppointmentAccess($appointment);

        $this->authorizeAppointmentAction($appointment);

        $appointment->delete();

        return back()->with('success', 'La cita ha sido eliminada exitosamente del historial.');
    }

    /**
     * Procesa el reagendamiento táctico desde el dashboard médico/clínica.
     * Cumple con el endpoint: partner.appointments.reschedule.process
     */
    public function rescheduleProcess(Request $request, Appointment $appointment)
    {
        // Primero valida el acceso (que no sea URL directo de otro doctor)
        $this->validateAppointmentAccess($appointment);

        $user = auth()->user();

        // 1. CONTROL DE ACCESO MULTI-TENANT (Abstracción del Owner)
        if ($user->role === 'clinic') {
            if ($appointment->clinic_id !== $user->clinic->id) {
                abort(403, 'No autorizado. Esta cita no pertenece a tu centro clínico.');
            }
            $settings = ClinicSetting::where('clinic_id', $user->clinic->id)->first();
        } else {
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No autorizado. Esta cita pertenece a otra agenda médica.');
            }
            $settings = DoctorSetting::where('doctor_id', $user->doctor->id)->first();
        }

        if (!$settings) {
            return back()->with('error', 'La configuración de la agenda no está disponible.');
        }

        // 2. VALIDAR INPUTS (Recibe los segundos :00 del select reactivo)
        $validated = $request->validate([
            'new_date'       => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i:s',
        ]);

        $newDate      = $validated['new_date'];
        $newStartTime = $validated['new_start_time']; // Ejemplo: "17:40:00"

        // 3. CALCULAR HORA DE FIN (Basado en la duración original y el buffer del médico)
        $newEndTime = Carbon::parse($newStartTime)->addMinutes($appointment->duration)->format('H:i:s');
        $endTimeWithBuffer = Carbon::parse($newEndTime)->addMinutes($settings->buffer_time_minutes ?? 0)->format('H:i:s');

        // 4. VALIDACIÓN DE CRUCES (Evitar Double-Booking con el mismo doctor)
        $isSlotOccupied = Appointment::where('doctor_id', $appointment->doctor_id)
            ->where('date', $newDate)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($newStartTime, $endTimeWithBuffer) {
                $query->where(function ($q) use ($newStartTime, $endTimeWithBuffer) {
                    $q->where('start_time', '<=', $newStartTime)->where('end_time', '>', $newStartTime);
                })->orWhere(function ($q) use ($newStartTime, $endTimeWithBuffer) {
                    $q->where('start_time', '<', $endTimeWithBuffer)->where('end_time', '>=', $endTimeWithBuffer);
                });
            })
            ->exists();

        if ($isSlotOccupied) {
            return back()->with('error', 'Operación cancelada. El horario seleccionado colisiona con otra cita médica vigente.');
        }

        // 5. PERSISTENCIA ATÓMICA ADMINISTRATIVA (Auto-confirma de inmediato)
        DB::transaction(function () use ($appointment, $newDate, $newStartTime, $newEndTime) {
            $appointment->update([
                'date'       => $newDate,
                'start_time' => $newStartTime,
                'end_time'   => $newEndTime,
                'status'     => 'confirmed',
                'email_sent' => false, // Fuerza el reenvío de las notificaciones con los datos correctos
            ]);
        });

        return back()->with('success', 'La consulta médica ha sido reprogramada y confirmada exitosamente.');
    }
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,pending,completed,cancelled'
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'email_sent' => false
        ]);

        return back()->with('success', 'El estado de la consulta ha sido actualizado correctamente.');
    }    
}
