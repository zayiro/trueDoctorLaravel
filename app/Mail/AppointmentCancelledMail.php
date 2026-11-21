<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AppointmentCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;

    /** 'partner' (médico o clínica) o 'patient'. */
    public $userAction;

    /** 'patient', 'doctor', 'system' o null si no se sabe. */
    public ?string $actor;

    /** A quién se saluda en el correo. */
    public string $recipientName;

    /** Frase lista para el texto: "por el paciente", "por la clínica"… (null = no se sabe). */
    public ?string $cancelledBy;

    /** Motivo escrito por quien canceló (null si no hay). */
    public ?string $reasonText;

    /** Monto a devolver (texto listo) si la cita estaba pagada en línea; null si no hay reembolso. */
    public ?string $refundAmount = null;

    public function __construct(Appointment $appointment, $userAction, ?string $actor = null, ?string $recipientName = null)
    {
        $this->appointment = $appointment;
        $this->userAction  = $userAction;
        $this->actor       = $actor;

        $this->recipientName = $recipientName ?: ($userAction === 'partner'
            ? 'Dr(a). ' . ($appointment->doctor->user->name ?? '')
            : ($appointment->patient->user->name ?? ''));

        $this->cancelledBy = match ($actor) {
            'patient' => 'por el paciente',
            'doctor'  => $appointment->clinic_id ? 'por la clínica' : 'por el especialista',
            'system'  => 'automáticamente por el sistema',
            default   => null,
        };

        // El motivo queda en las notas como "[Auditoría: Cancelado por … Motivo: xxx]".
        // Si la cita estaba pagada en línea se abrió un reembolso: el paciente debe saberlo.
        if ($userAction === 'patient') {
            try {
                $refund = \App\Models\PaymentRefund::where('appointment_id', $appointment->id)->first();
                if ($refund) {
                    $this->refundAmount = '$' . number_format($refund->amount_in_cents / 100, 0, ',', '.') . ' COP';
                }
            } catch (\Throwable $e) {
                $this->refundAmount = null;
            }
        }

        $this->reasonText = null;
        if ($appointment->notes && preg_match('/Motivo:\s*(.+?)\s*\]?\s*$/mu', (string) $appointment->notes, $m)) {
            $this->reasonText = trim($m[1]);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cita Cancelada - Ref: ' . $this->appointment->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.cancelled',
        );
    }
}
