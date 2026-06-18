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
        $this->middleware('api.rate.limit');
        $this->middleware('api.validate.key')->except(['getStatus']);
    }

    /**
     * Consulta de slots disponibles (API Abierta/Externa)
     * 
     * GET /api/appointments/slots
     * Rate Limit: 60 requests/minuto
     */
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
            $startTime = Carbon::parse($request->start_time)->format('H:i:s');
            $endTime = Carbon::parse($request->start_time)->addMinutes($request->duration)->format('H:i:s');

            // REGLA DE ORO: Validar disponibilidad real en este instante exacto (Previene condiciones de carrera)
            $slots = $this->calculateAvailableSlots(
                $request->date,
                $request->address_id,
                $request->is_virtual,
                $request->duration
            );

            // Buscar si el slot específico solicitado está marcado como "available => true"
            $slotDisponible = collect($slots)->first(function ($slot) use ($startTime) {
                return Carbon::parse($slot['time'])->format('H:i:s') === $startTime && $slot['available'] === true;
            });

            if (!$slotDisponible) {
                return response()->json([
                    'error' => 'El horario seleccionado ya no está disponible o el doctor se encuentra ausente.'
                ], 422);
            }

            // Insertar la cita de forma segura en una transacción
            $appointment = DB::transaction(function () use ($request, $doctorId, $startTime, $endTime) {
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

                // Registrar evento de auditoría
                AppointmentEventSourcing::recordCreated($appointment, [
                    'source' => 'api_gateway',
                    'api_client_id' => $request->attributes->get('api_client')?->id,
                ]);

                return $appointment;
            });

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
     * Motor lógico centralizado de cálculo de bloques horarios (Privado y Reutilizable)
     */
    private function calculateAvailableSlots($date, $addressId, $isVirtual, $duration, $excludeId = null)
    {
        $fechaConsultada = Carbon::parse($date);
        $diaSemana = $fechaConsultada->dayOfWeek;
        $diaMigracion = $diaSemana === 0 ? 7 : $diaSemana; // Conversión: Carbon 0=Dom a Base de datos 7=Dom
        $ahora = Carbon::now();

        $address = Address::find($addressId);
        if (!$address) return null;
        $doctorId = $address->doctor_id;

        // 1. Validar ausencias o bloqueos
        $isUnavailable = Unavailability::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $fechaConsultada)
            ->whereDate('end_date', '>=', $fechaConsultada)
            ->where(function($q) use ($addressId, $isVirtual) {
                if ($isVirtual) {
                    $q->whereNull('address_id');
                } else {
                    $q->whereNull('address_id')->orWhere('address_id', $addressId);
                }
            })
            ->exists();

        if ($isUnavailable) return null;

        // 2. Obtener base del horario laboral
        if ($isVirtual) {
            $schedule = Schedule::join('addresses', 'schedules.address_id', '=', 'addresses.id')
                ->where('addresses.doctor_id', $doctorId)
                ->where('schedules.day', $diaMigracion)
                ->selectRaw('MIN(start_time) as start_time, MAX(end_time) as end_time')
                ->first();
        } else {
            $schedule = Schedule::where('address_id', $addressId)
                ->where('day', $diaMigracion)
                ->first();
        }

        if (!$schedule || !$schedule->start_time) return null;

        // 3. Obtener citas que colisionen (Usa el índice compuesto)
        $citasOcupadas = Appointment::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->when($excludeId, function($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->get(['start_time', 'end_time']);

        // 4. Mapeo de slots generados
        $slots = [];
        $inicio = Carbon::parse($schedule->start_time);
        $fin = Carbon::parse($schedule->end_time);

        while ($inicio->copy()->addMinutes($duration) <= $fin) {
            $horaSlotInicio = $inicio->format('H:i:s');
            $horaSlotFin = $inicio->copy()->addMinutes($duration)->format('H:i:s');
            $objHoraSlot = Carbon::parse($date . ' ' . $horaSlotInicio);

            // Validar si el rango interfiere con otra cita
            $estaOcupado = $citasOcupadas->contains(function($cita) use ($horaSlotInicio, $horaSlotFin) {
                return ($horaSlotInicio >= $cita->start_time && $horaSlotInicio < $cita->end_time) ||
                       ($horaSlotFin > $cita->start_time && $horaSlotFin <= $cita->end_time);
            });

            $esPasado = $fechaConsultada->isToday() && $objHoraSlot->lt($ahora);

            $slots[] = [
                "time" => $inicio->format('H:i'),
                "available" => !$estaOcupado && !$esPasado
            ];

            $inicio->addMinutes($duration);
        }

        return $slots;
    }

        /**
     * PUT /api/appointments/{id}/cancel
     * Cancela una cita desde una aplicación externa validando las reglas de tiempo.
     */
    public function cancel($id)
    {
        // 1. Ejecutar la validación matemática del servicio centralizado
        $checkStatus = $this->appointmentService->checkIfCanModify($id);

        if (!$checkStatus['allowed']) {
            return response()->json([
                'error' => $checkStatus['message']
            ], 422); // Error de regla de negocio
        }

        // 2. Buscar la cita y actualizar su estado a cancelado
        $appointment = Appointment::findOrFail($id);
        $appointment->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Appointment cancelled successfully.',
            'data'    => $appointment
        ], 200);
    }

    /**
     * Devuelve el estado actual de la cita (Invocado cada 5 segundos por el Polling de Alpine)
     */
    public function getStatus(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'status' => $appointment->status, // pending, completed, etc.
        ]);
    }
}
