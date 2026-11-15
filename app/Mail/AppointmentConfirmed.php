<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Carbon\Carbon;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;
    public $appointment;

    public $userAction;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, $userAction)
    {
        $this->appointment = $appointment;
        $this->userAction  = $userAction;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de Cita Médica - Ref: ' . $this->appointment->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.confirmed',
        );
    }

        /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $tz = config('app.timezone', 'America/Bogota');
        $fechaBase = $this->appointment->date->format('Y-m-d');
        
        $start_time  = Carbon::parse("{$fechaBase} {$this->appointment->start_time}", $tz);
        $end_time    = Carbon::parse("{$fechaBase} {$this->appointment->end_time}", $tz);
        $isVirtual   = ($this->appointment->service?->type === 'virtual') || ($this->appointment->address?->type === 'virtual');
        $addressText = $isVirtual ? 'Consulta Médica por Telemedicina' : ($this->appointment->address?->address ?? 'Consultorio Médico Presencial');
        $doctorName  = $this->appointment->doctor?->user?->name;
        $partner     = $doctorName ? 'Cita Médica con ' . $doctorName : 'Cita Médica';

        // Datos del organizador: deben coincidir con el remitente (From) del correo para evitar "Unknown Organizer"
        $organizerEmail = config('mail.from.address', 'noreply@opendoctor.online');
        $organizerName  = config('mail.from.name', 'OpenDoctor Online');

        // Determinamos quién recibe este correo (paciente o doctor) para registrarlo como invitado (Attendee)
        $attendeeEmail = $this->userAction === 'patient'
            ? $this->appointment->patient?->user?->email
            : $this->appointment->doctor?->user?->email;

        $attendeeName = $this->userAction === 'patient'
            ? $this->appointment->patient?->user?->name
            : $doctorName;

        // 2. Construcción del evento iCalendar con Spatie
        $event = Event::create($partner)
            ->uniqueIdentifier("appointment-{$this->appointment->reference}@opendoctor.online")
            ->startsAt($start_time)
            ->endsAt($end_time)
            ->organizer($organizerEmail, $organizerName)
            ->description("Referencia: {$this->appointment->reference}\nEstado de la cita: {$this->appointment->status->value}.\nPor favor, conéctese o asista 10 minutos antes.")
            ->address($addressText);

        if ($attendeeEmail) {
            $event->attendee($attendeeEmail, $attendeeName ?? 'Participante');
        }

        $icsContent = Calendar::create('Calendario de Citas en OpenDoctor Online')
            ->event($event)
            ->get();

        // 3. Adjunto en memoria con los headers correctos para clientes de correo
        return [
            Attachment::fromData(fn () => $icsContent, 'cita-medica.ics')
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }
}
