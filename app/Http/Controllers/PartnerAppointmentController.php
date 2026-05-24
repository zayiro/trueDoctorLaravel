<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Address;
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
}
