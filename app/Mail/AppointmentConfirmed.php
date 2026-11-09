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
        // 1. Fusionamos el objeto Date de Eloquent con los strings de horas
        // Como 'date' ya viene casteado como objeto Carbon, usamos format('Y-m-d')
        $fechaBase = $this->appointment->date->format('Y-m-d');
        
        $start_time  = Carbon::parse("{$fechaBase} {$this->appointment->start_time}");
        $end_time    = Carbon::parse("{$fechaBase} {$this->appointment->end_time}");
        $isVirtual   = ($this->appointment->service?->type === 'virtual') || ($this->appointment->address?->type === 'virtual');
        $addressText = $isVirtual ? 'Consulta Médica por Telemedicina' : 'Consultorio Médico Presencial';
        $partner     = $this->appointment->doctor->user->name ? 'Cita Médica con ' . $this->appointment->doctor->user->name : 'Cita Médica';

        // 2. Construcción del evento iCalendar con Spatie
        $icsContent = Calendar::create('Calendario de Citas en OpenDoctorOnline')
            ->event(
                Event::create($partner)
                    ->startsAt($start_time)
                    ->endsAt($end_time)
                    ->description("Estado de la cita: {$this->appointment->status->value}.\nPor favor, asista 10 minutos antes.")
                    // Ajusta el nombre de la relación según tengas configurado tu modelo (ej. $this->appointment->doctor->office_address)
                    ->address($addressText) 
            )
            ->get();

        // 3. Adjunto en memoria con los headers correctos para clientes de correo
        return [
            Attachment::fromData(fn () => $icsContent, 'cita-medica.ics')
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }
}
