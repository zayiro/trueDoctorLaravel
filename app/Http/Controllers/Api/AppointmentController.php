<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use App\Services\AppointmentService;
use App\Services\AppointmentEventSourcing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotPresenter;
use App\Services\Availability\SlotNotAvailableException;

class AppointmentController extends Controller
{
    /**
     * Instancia del servicio de citas
     */
    private AppointmentService $appointmentService;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;

        // Aplicar middlewares de seguridad al API Gateway
        //$this->middleware('api.rate.limit');
        //$this->middleware('api.validate.key')->except(['getStatus']);
    }

    /**
     * Consulta de slots disponibles (API Abierta/Externa)
     * 
     * GET /api/appointments/slots
     * Rate Limit: 60 requests/minuto
     */

    /*
    public function getSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'address_id' => 'required|exists:addresses,id',
            'is_virtual' => 'required|in:true,false,1,0',
            'duration' => 'nullable|integer|min:5|max:120',
            'exclude_id' => 'nullable|integer'
        ]);

        $slots = $this->calculateAvailableSlots(
            $request->date,
            $request->address_id,
            filter_var($request->is_virtual, FILTER_VALIDATE_BOOLEAN),
            (int) $request->input('duration', 20),
            $request->exclude_id
        );

        if ($slots === null) {
            return response()->json([], 200);
        }

        return response()->json($slots, 200);
    }*/

    public function getSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'address_id' => 'required|exists:addresses,id,deleted_at,NULL',
            'is_virtual' => 'required|in:true,false,1,0',
            'duration' => 'nullable|integer|min:5|max:120',
            'exclude_id' => 'nullable|integer'
        ]);
 
        try {
            $address = Address::find($request->address_id);

            // Una sede sin doctor propio no devuelve horarios.
            if (!$address || !$address->doctor_id) {
                return response()->json([], 200);
            }

            $query = new AvailabilityQuery(
                doctorIds: [(int) $address->doctor_id],
                clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
                addressId: (int) $address->id,
                excludeAppointmentId: $request->filled('exclude_id') ? (int) $request->exclude_id : null,
            );

            // La API siempre devolvió también los turnos pasados u ocupados, con available=false.
            $slots = app(AvailabilityResolver::class)->slotsForDay(
                $query,
                Carbon::parse($request->date, config('booking.timezone', 'America/Bogota')),
                includePast: true
            );

            return response()->json(SlotPresenter::legacyApi($slots), 200);
        } catch (\Throwable $e) {
            Log::error('getSlots api: falló el motor de disponibilidad', [
                'address_id' => $request->address_id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['error' => 'No se pudo consultar la disponibilidad.'], 500);
        }
    }

    /**
     * Crear una cita con doble validación de seguridad
     * 
     * POST /api/appointments
     * Rate Limit: 10 requests/minuto
     * Requiere: X-API-Key header
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'service_id' => 'required|exists:services,id',
                'address_id' => 'required|exists:addresses,id',
                'date' => 'required|date_format:Y-m-d|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'duration' => 'required|integer|min:5|max:120',
                'price' => 'required|numeric|min:0',
                'is_virtual' => 'required|boolean',
                'meeting_link' => 'nullable|url',
                'notes' => 'nullable|string|max:500'
            ]);

            $address = Address::findOrFail($request->address_id);
            $doctorId = $address->doctor_id;
            if (!$doctorId) {
                return response()->json(['error' => 'La sede no tiene un doctor asignado.'], 422);
            }

            $startTime = Carbon::parse($request->start_time)->format('H:i:s');
            $endTime = Carbon::parse($request->start_time)->addMinutes($request->duration)->format('H:i:s');

            // Motor único: valida el turno y crea la cita en la misma transacción, con el doctor bloqueado.
            $query = new AvailabilityQuery(
                doctorIds: [(int) $doctorId],
                clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
                addressId: (int) $address->id,
                serviceId: (int) $request->service_id,
            );

            try {
                $appointment = app(AvailabilityResolver::class)->withBookableSlot(
                    $query,
                    Carbon::parse($request->date . ' ' . $startTime, config('booking.timezone', 'America/Bogota')),
                    function () use ($request, $doctorId, $startTime, $endTime) {
                        $appointment = Appointment::create([
                            'patient_id' => $request->patient_id,
                            'doctor_id' => $doctorId,
                            'service_id' => $request->service_id,
                            'address_id' => $request->is_virtual ? null : $request->address_id,
                            'date' => $request->date,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'duration' => $request->duration,
                            'price' => $request->price,
                            'meeting_link' => $request->is_virtual ? $request->meeting_link : null,
                            'status' => 'pending',
                            'notes' => $request->notes,
                        ]);

                        AppointmentEventSourcing::recordCreated($appointment, [
                            'source' => 'api_gateway',
                            'api_client_id' => $request->attributes->get('api_client')?->id,
                        ]);

                        return $appointment;
                    }
                );
            } catch (SlotNotAvailableException $e) {
                return response()->json([
                    'error' => 'El horario seleccionado ya no está disponible o el doctor se encuentra ausente.'
                ], 422);
            }

            Log::info('API Gateway: Cita creada exitosamente', [
                'appointment_id' => $appointment->id,
                'api_client_id' => $request->attributes->get('api_client')?->id,
            ]);

            return response()->json([
                'message' => 'Cita agendada con éxito.',
                'data' => $appointment
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Gateway: Error al crear cita', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'No se pudo procesar la solicitud en el servidor.'
            ], 500);
        }
    }

    /**
     * PUT /api/appointments/{id}/cancel
     * Cancela una cita desde una aplicación externa validando las reglas de tiempo.
     * 
     * Rate Limit: 20 requests/minuto
     * Requiere: X-API-Key header
     */
    public function cancel($id)
    {
        try {
            // 1. Buscar la cita
            $appointment = Appointment::findOrFail($id);

            // 2. Ejecutar la validación matemática del servicio centralizado
            $checkStatus = $this->appointmentService->checkIfCanModify($id);

            if (!$checkStatus['allowed']) {
                Log::warning('API Gateway: Cancelación rechazada', [
                    'appointment_id' => $id,
                    'reason' => $checkStatus['message'],
                    'api_client_id' => request()->attributes->get('api_client')?->id,
                ]);

                return response()->json([
                    'error' => $checkStatus['message']
                ], 422);
            }

            // 3. Actualizar estado en transacción
            DB::transaction(function () use ($appointment) {
                $appointment->update([
                    'status' => 'cancelled'
                ]);

                // Registrar evento de auditoría
                AppointmentEventSourcing::recordCancelled($appointment, 'Cancelada vía API Gateway');
            });

            Log::info('API Gateway: Cita cancelada exitosamente', [
                'appointment_id' => $appointment->id,
                'api_client_id' => request()->attributes->get('api_client')?->id,
            ]);

            return response()->json([
                'message' => 'Appointment cancelled successfully.',
                'data'    => $appointment
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Appointment not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('API Gateway: Error al cancelar cita', [
                'appointment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'No se pudo procesar la solicitud en el servidor.'
            ], 500);
        }
    }

    /**
         * Devuelve el estado actual de la cita (Invocado cada 5 segundos por el Polling de Alpine)
         */
        /**
     * Devuelve el estado actual de la cita (Invocado cada 5 segundos por el Polling de Alpine)
     */
    public function getStatus($id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            return response()->json([
                'status' => $appointment->status,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Appointment not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('getStatus error', [
                'appointment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al obtener estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
