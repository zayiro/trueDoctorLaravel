<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Jobs\SendSmsReminder;
use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\MailLimitExceededNotification;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lo que ocurre cuando una cita queda confirmada: sala de Zoom, correos y SMS.
 *
 * Antes vivía en AppointmentController::processPostConfirmation y solo corría cuando el paciente volvía
 * al sitio. Ahora lo llaman tanto el webhook de pagos (en cola) como la página de resultado: es idempotente,
 * así que da igual cuál llegue primero o que lleguen los dos.
 */
class AppointmentFinalizer
{
    use \App\Traits\SendsReminderSms;

    public function __construct(private readonly ZoomService $zoomService)
    {
    }

    /**
     * @return Appointment la cita recargada con las relaciones que usan las vistas.
     */
    public function finalize(Appointment $appointment, ?User $activeUser = null): Appointment
    {
        $appointment->refresh();

        $status = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;

        // Confirmada, o pendiente de aprobación del doctor en una cita que no se paga en línea
        // (comportamiento de siempre: aviso al paciente y al doctor).
        $awaitsApproval = $status === 'pending'
            && $appointment->payment_expires_at === null
            && !\App\Services\Payments\AppointmentCheckout::requiresOnlinePayment($appointment->loadMissing(['service', 'address', 'doctor.settings', 'clinic.settings']));

        if ($status === 'confirmed' || $awaitsApproval) {
            $this->ensureZoomMeeting($appointment);
            $this->sendNotifications($appointment, $activeUser);
        }

        return $appointment->fresh(['doctor.user', 'clinic', 'service', 'address.city', 'patient.user']);
    }

    private function ensureZoomMeeting(Appointment $appointment): void
    {
        $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');
        $hasCreationFailure = DB::table('zoom_creation_failures')->where('appointment_id', $appointment->id)->exists();

        if (!$isVirtual || $appointment->zoom_meeting_id || $hasCreationFailure) {
            return;
        }

        try {
            DB::transaction(function () use ($appointment) {
                $fresh = $appointment->newQuery()->lockForUpdate()->find($appointment->id);

                if (!$fresh || $fresh->zoom_meeting_id) {
                    return;
                }

                $startDateTime = Carbon::parse($fresh->date->format('Y-m-d') . ' ' . $fresh->start_time)->toIso8601String();
                $zoomMeeting = $this->zoomService->createMeeting("Telemedicina_Ref: {$fresh->reference}", $startDateTime, (int) $fresh->duration);

                if ($zoomMeeting) {
                    $fresh->update([
                        'zoom_meeting_id'       => $zoomMeeting['meeting_id'],
                        'meeting_link'          => $zoomMeeting['url_patient'],
                        'zoom_start_url'        => $zoomMeeting['url_partner'],
                        'meeting_link_password' => ($zoomMeeting['password'] ?? '') ?: null,
                    ]);

                    DB::table('zoom_creation_failures')->where('appointment_id', $fresh->id)->delete();
                }
            });
        } catch (Throwable $e) {
            Log::error('Error crítico Zoom en post-confirmación: ' . $e->getMessage(), ['appointment_id' => $appointment->id]);
        }

        $appointment->refresh();
    }

    private function sendNotifications(Appointment $appointment, ?User $activeUser): void
    {
        // Se "reclama" el envío con una actualización atómica: solo un proceso gana, no hay correos duplicados.
        $claimed = Appointment::whereKey($appointment->id)->where('email_sent', false)->update(['email_sent' => true]);

        if (!$claimed) {
            return;
        }

        try {
            $patientEmail = $appointment->patient?->user?->email;
            $doctorEmail = $appointment->doctor?->user?->email;

            if ($patientEmail) {
                Mail::to($patientEmail)->send(new AppointmentConfirmed($appointment, 'patient'));
            }

            if ($doctorEmail) {
                Mail::to($doctorEmail)->send(new AppointmentConfirmed($appointment, 'partner'));
            }
        } catch (Throwable $e) {
            Log::error('Error en notificaciones post-confirmación: ' . $e->getMessage(), ['appointment_id' => $appointment->id]);

            try {
                $admins = User::where('role', 'admin')->get();
                Notification::send($admins, new MailLimitExceededNotification($e->getMessage(), $activeUser?->email));
            } catch (Throwable) {
                // silencioso
            }

            // El envío falló: se libera la reserva para que el siguiente intento (página de resultado o cola) lo reintente.
            Appointment::whereKey($appointment->id)->update(['email_sent' => false]);

            return;
        }

        try {
            $appointmentDateTime = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . (string) $appointment->start_time);

            $name = Str::of($appointment->patient?->user?->name ?? 'Paciente')
                ->trim()->before(' ')->title()->ascii()->toString();

            $date = $appointmentDateTime->format('d/m/Y');
            $time = $appointmentDateTime->format('h:i A');

            $phone = $appointment->patient?->phone;

            if (!$phone) {
                return; // sin teléfono no hay SMS (el trait exige un string)
            }

            SendSmsReminder::dispatch(
                $phone,
                "Hola {$name}, tu cita medica en OpenDoctor quedo agendada para el {$date} a las {$time}. Nos vemos pronto.",
                "appointment_{$appointment->reference}_confirmation"
            );

            $this->scheduleReminderBeforeEvent(
                $phone,
                "Recordatorio: Tu cita medica en OpenDoctor {$appointment->reference} inicia en 20 min.",
                $appointmentDateTime,
                20,
                "appointment_{$appointment->reference}_reminder"
            );
        } catch (Throwable $e) {
            Log::error('ERROR al programar recordatorios SMS', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
        }
    }
}
