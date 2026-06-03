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
     * Muestra el cronograma de citas unificado.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', now()->toDateString());
        $showAll = $request->has('all');

        // 1. RESOLVER LA CAJA DE HERRAMIENTAS SEGÚN EL ROL
        if ($user->role === 'clinic') {
            $clinic = $user->clinic;
            
            // 🔥 ESTA ES LA LÓGICA: Cargamos la nómina de médicos de la clínica
            $availableDoctors = $clinic->doctors()->with('user')->get();

            // Filtramos las citas de la clínica (por médico si seleccionó uno)
            $appointmentsQuery = Appointment::where('clinic_id', $clinic->id);
            if ($request->filled('doctor_id')) {
                $appointmentsQuery->where('doctor_id', $request->doctor_id);
            }
        } else {
            $doctor = $user->doctor;
            
            // Si es doctor independiente, la lista de médicos para filtrar va vacía
            $availableDoctors = collect([]); 

            // Filtramos las citas privadas del doctor
            $appointmentsQuery = Appointment::where('doctor_id', $doctor->id);
        }

        // 2. FILTRADO CRONOLÓGICO COMÚN
        if (!$showAll) {
            $appointmentsQuery->where('date', $date);
        } else {
            $appointmentsQuery->where('date', '>=', now()->toDateString());
        }

        // 3. AGRUPAMOS LAS CITAS POR SEDE (Tal como lo pide tu vista con $appointments as $addressId => $group)
        $appointments = $appointmentsQuery->with(['patient.user', 'address', 'service'])
            ->get()
            ->groupBy('address_id');

        // 4. ENVIAMOS TODO COMPACTADO A LA VISTA
        return view('partner.appointments.index', compact('appointments', 'availableDoctors', 'date', 'showAll'));
    }

    /**
     * Calcula las franjas horarias libres para el calendario público del SaaS.
     * Atiende las llamadas de Alpine.js en el perfil público y del formulario de reagendamiento táctico.
     */
    public function getSlots(Request $request)
    {
        // 1. Validamos estrictamente los parámetros requeridos por la matriz de disponibilidad
        $request->validate([
            'address_id' => 'required|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date',
            'doctor_id'  => 'required|exists:doctors,id', // Indispensable para el SaaS corporativo
        ]);

        // 2. Invocamos al servicio centralizado de la aplicación
        $appointmentService = app(AppointmentService::class);

        // 3. Ejecutamos el motor de cálculo cruzado libre de colisiones multiperfil
        $slots = $appointmentService->getAvailableSlots(
            $request->address_id,
            $request->date,
            $request->doctor_id,
            $request->input('is_virtual') === 'true'
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
        } else {
            // El doctor opera si la cita le pertenece a su perfil
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No tienes permiso para gestionar esta cita privada.');
            }
        }
    }

    public function complete(Appointment $appointment)
    {        
        // 🔥 CORREGIDO: Validación de seguridad multiperfil sin bugs de IDs cruzados
        $this->authorizeAppointmentAction($appointment);
     
        $appointment->update([
            'status' => 'completed'
        ]);

        return back()->with('success', 'La cita ha sido marcada como completada.');
    }

    public function cancel(Appointment $appointment)
    {        
        $this->authorizeAppointmentAction($appointment);
     
        $appointment->update([
            'status' => 'cancelled'
        ]);

        return back()->with('success', 'La cita ha sido marcada como cancelada.');
    }

    public function destroy(Appointment $appointment)
    {
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
