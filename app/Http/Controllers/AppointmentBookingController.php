<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Services\Appointments\PaymentLinkService;
use App\Services\Appointments\StaffBookingService;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotNotAvailableException;
use App\Services\Availability\SlotPresenter;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "Crear cita" del doctor / clínica.
 *
 * La autorización NO está en el constructor (allí la sesión todavía no está iniciada): cada acción pasa por staff().
 */
class AppointmentBookingController extends Controller
{
    public function __construct(
        private readonly StaffBookingService $booking,
        private readonly PaymentLinkService $links,
    ) {
    }

    /** 🔒 Solo doctores y clínicas. */
    private function staff(): \App\Models\User
    {
        $user = Auth::user();

        abort_unless($user && in_array($user->role, ['doctor', 'clinic'], true), 403, 'No tienes permisos para acceder al módulo de agendamiento.');

        return $user;
    }

    public function create(): \Illuminate\Contracts\View\View
    {
        $this->staff();

        return view('partner.booking.create');
    }

    /** Doctores y sedes disponibles para el usuario. */
    public function options(): JsonResponse
    {
        $user = $this->staff();

        try {
            return response()->json($this->booking->options($user));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function services(Request $request): JsonResponse
    {
        $user = $this->staff();
        $data = $request->validate(['address_id' => 'required|integer', 'doctor_id' => 'nullable|integer']);

        try {
            [, $address] = $this->booking->authorizeScope($user, (int) $data['address_id'], isset($data['doctor_id']) ? (int) $data['doctor_id'] : null);

            return response()->json($this->booking->servicesFor($address));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Días del mes con al menos un turno (modo staff: sin anticipación mínima ni horizonte). */
    public function calendar(Request $request, AvailabilityResolver $availability): JsonResponse
    {
        $user = $this->staff();
        $data = $request->validate([
            'address_id' => 'required|integer',
            'doctor_id'  => 'nullable|integer',
            'service_id' => 'required|integer',
            'month'      => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        try {
            [$doctor, $address] = $this->booking->authorizeScope($user, (int) $data['address_id'], isset($data['doctor_id']) ? (int) $data['doctor_id'] : null);

            $tz = config('booking.timezone', 'America/Bogota');
            $monthStart = Carbon::createFromFormat('Y-m-d', $data['month'] . '-01', $tz)->startOfDay();
            $from = $monthStart->copy()->max(now($tz)->startOfDay());
            $to = $monthStart->copy()->endOfMonth()->startOfDay();

            if ($from->gt($to)) {
                return response()->json(['dates' => []]);
            }

            $dates = $availability->enabledDates($this->booking->query($doctor, $address, (int) $data['service_id']), $from, $to);

            return response()->json(['dates' => array_values($dates)]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('booking.calendar_failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo consultar la disponibilidad.'], 500);
        }
    }

    public function slots(Request $request, AvailabilityResolver $availability): JsonResponse
    {
        $user = $this->staff();
        $data = $request->validate([
            'address_id' => 'required|integer',
            'doctor_id'  => 'nullable|integer',
            'service_id' => 'required|integer',
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        try {
            [$doctor, $address] = $this->booking->authorizeScope($user, (int) $data['address_id'], isset($data['doctor_id']) ? (int) $data['doctor_id'] : null);

            $slots = $availability->slotsForDay(
                $this->booking->query($doctor, $address, (int) $data['service_id']),
                Carbon::parse($data['date'], config('booking.timezone', 'America/Bogota'))
            );

            return response()->json(SlotPresenter::legacyWeb($slots));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('booking.slots_failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo consultar la disponibilidad.'], 500);
        }
    }

    /** Crea la cita. Responde JSON para la pantalla de "Crear cita". */
    public function store(Request $request): JsonResponse
    {
        $user = $this->staff();

        $data = $request->validate([
            'identification' => 'required|string|max:20',
            'name'           => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'country_code'   => 'nullable|in:+57,+54,+591,+55,+56,+593,+595,+51,+598,+58',
            'phone'          => 'nullable|string|max:15',
            'doctor_id'      => 'nullable|integer',
            'address_id'     => 'required|integer',
            'service_id'     => 'required|integer',
            'date'           => 'required|date_format:Y-m-d',
            'start_time'     => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'payment_method' => 'required|in:online,onsite',
            'notes'          => 'nullable|string|max:1000',
        ]);

        try {
            [$doctor, $address] = $this->booking->authorizeScope($user, (int) $data['address_id'], isset($data['doctor_id']) ? (int) $data['doctor_id'] : null);
            $patient = $this->booking->resolvePatient($data);

            $result = $this->booking->create($user, $patient, $doctor, $address, $data);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (SlotNotAvailableException) {
            return response()->json(['message' => 'Ese horario acaba de ser ocupado. Elige otro turno.'], 409);
        } catch (Throwable $e) {
            Log::error('booking.store_failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo crear la cita. Inténtalo de nuevo.'], 500);
        }

        /** @var Appointment $appointment */
        $appointment = $result['appointment'];

        $message = match (true) {
            $result['mode'] === 'link' && $result['mail_sent']  => 'Cita reservada. Enviamos el enlace de pago al correo del paciente.',
            $result['mode'] === 'link'                          => 'Cita reservada, pero el correo no pudo enviarse. Copia el enlace y compártelo con el paciente.',
            default                                             => 'Cita agendada y confirmada.',
        };

        return response()->json([
            'success'    => true,
            'message'    => $message,
            'reference'  => $appointment->reference,
            'mode'       => $result['mode'],
            'link'       => $result['link'],
            'mail_sent'  => $result['mail_sent'],
            'expires_at' => $appointment->payment_expires_at?->timezone('America/Bogota')->format('d/m/Y g:i A'),
        ], 201);
    }

    /** Reenvía el correo con el enlace de pago. */
    public function resendPaymentLink(Appointment $appointment): JsonResponse
    {
        $user = $this->staff();
        $this->authorizeAppointment($user, $appointment);

        if (!$this->links->isOpen($appointment)) {
            return response()->json(['message' => 'Esta cita ya no espera pago o el enlace venció.'], 422);
        }

        if (!Cache::add('payment-link-resend:' . $appointment->id, 1, 60)) {
            return response()->json(['message' => 'Ya se envió hace un momento. Espera un minuto para reenviarlo.'], 429);
        }

        if (!$this->links->send($appointment)) {
            Cache::forget('payment-link-resend:' . $appointment->id);

            return response()->json(['message' => 'No se pudo enviar el correo. Copia el enlace y compártelo por otro medio.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Correo reenviado al paciente.']);
    }

    private function authorizeAppointment(\App\Models\User $user, Appointment $appointment): void
    {
        $ok = $user->role === 'clinic'
            ? $user->clinic && (int) $appointment->clinic_id === (int) $user->clinic->id
            : $user->doctor && (int) $appointment->doctor_id === (int) $user->doctor->id;

        abort_unless($ok, 403, 'No puedes gestionar esta cita.');
    }

    // ---------------------------------------------------------------- heredados (la pantalla nueva ya no usa la sesión)

    public function updateSession(Request $request): JsonResponse
    {
        $this->staff();

        $validated = $request->validate([
            'group' => ['required', 'string', 'in:root,patient,appointment'],
            'key'   => ['required', 'string', 'max:50'],
            'value' => ['nullable'],
        ]);

        $allowedKeys = [
            'root'        => ['doctor_id', 'address_id', 'service_id', 'date', 'start_time'],
            'patient'     => ['all', 'id', 'name', 'email', 'phone', 'identification'],
            'appointment' => ['notes', 'reason', 'duration'],
        ];

        if ($validated['group'] === 'patient' && $validated['key'] === 'all') {
            if (!is_array($validated['value'])) {
                return response()->json(['status' => 'error', 'message' => 'El valor debe ser un array.'], 400);
            }
            session(['appointment_flow.patient' => $validated['value']]);
        } elseif (in_array($validated['key'], $allowedKeys[$validated['group']] ?? [], true)) {
            session(["appointment_flow.{$validated['group']}.{$validated['key']}" => $validated['value']]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Clave no permitida.'], 403);
        }

        return response()->json(['status' => 'success']);
    }

    public function clearSession(): JsonResponse
    {
        $this->staff();
        session()->forget('appointment_flow');

        return response()->json(['status' => 'success']);
    }

    /** Busca un paciente por documento (índice ciego, el documento está cifrado). */
    public function searchPatient(Request $request): JsonResponse
    {
        $this->staff();

        $validated = $request->validate(['identification' => ['required', 'string', 'max:20']]);
        $identification = trim($validated['identification']);

        try {
            $blindIndex = Patient::generateBlindIndex($identification);

            $patient = Patient::with('user')->where('identification_blind_index', $blindIndex)->where('active', true)->first();

            if ($patient) {
                return response()->json([
                    'found'   => true,
                    'patient' => [
                        'id'             => $patient->id,
                        'name'           => $patient->user->name ?? 'Paciente sin nombre',
                        'email'          => $patient->user->email ?? '',
                        'phone'          => $patient->phone ?? '',
                        'identification' => $identification,
                    ],
                ]);
            }

            if (Patient::where('identification_blind_index', $blindIndex)->where('active', false)->exists()) {
                return response()->json(['found' => false, 'message' => 'El paciente existe pero su cuenta está inactiva. Contacta al administrador.']);
            }

            return response()->json(['found' => false]);
        } catch (Throwable $e) {
            Log::error('Error al buscar paciente por identificación', ['error' => $e->getMessage()]);

            return response()->json(['found' => false, 'message' => 'Ocurrió un error al buscar el paciente. Intenta nuevamente.'], 500);
        }
    }
}
