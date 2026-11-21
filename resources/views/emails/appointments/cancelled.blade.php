@component('mail::message')
@if($userAction == 'partner')
# Hola, {{ $recipientName }}

@if($actor === 'patient')
Te informamos que una cita médica agendada en tu plataforma ha sido **cancelada por el paciente**. El espacio horario ha quedado libre en tu agenda nuevamente.
@elseif($cancelledBy)
Te informamos que una cita médica agendada en tu plataforma ha sido **cancelada {{ $cancelledBy }}**. El espacio horario ha quedado libre en la agenda nuevamente.
@else
Te informamos que una cita médica agendada en tu plataforma ha sido **cancelada**. El espacio horario ha quedado libre en la agenda nuevamente.
@endif
@else
# Hola, {{ $recipientName }}

@if($actor === 'patient' || !$cancelledBy)
Te informamos que la cita médica ha sido **cancelada** correctamente.
@else
Lamentamos informarte que tu cita médica ha sido **cancelada {{ $cancelledBy }}**. Puedes agendar una nueva cita cuando quieras en [opendoctor.online]({{ url('/') }}).
@endif
@endif

## Detalles de la Cita Cancelada:
*   **Referencia:** {{ $appointment->reference }}
*   **Servicio:** {{ $appointment->service->name }}
*   **Fecha:** {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('l d \d\e F, Y') }}
*   **Hora:** {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
*   **Modalidad:** {{ $appointment->service->type === 'virtual' ? '💻 Virtual (Reunión de Zoom Eliminada)' : '🏥 Presencial' }}
@if($userAction == 'partner')
*   **Paciente:** {{ $appointment->patient->user->name ?? '' }}
*   **Especialista:** {{ $appointment->doctor->user->name ?? '' }}
@endif

@if($reasonText)
**Motivo:**
_{{ $reasonText }}_
@endif

@if($userAction == 'patient' && $refundAmount)
## Tu reembolso

Como ya habías pagado esta cita en línea, te devolveremos **{{ $refundAmount }}**, el valor total que pagaste. Estamos gestionando la devolución y te escribiremos apenas esté lista. Cuánto tarda en verse reflejada depende de tu banco o medio de pago.

@endif

Agradecemos tu atención al sistema de gestión.

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
