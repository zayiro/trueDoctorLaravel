@component('mail::message')
@if($userAction == 'partner')
# Hola, Dr. {{ $appointment->doctor->user->name }}

Le informamos que una cita médica agendada en su plataforma ha sido **cancelada por el paciente**. El espacio horario ha quedado libre en su agenda nuevamente.
@else
# Hola, {{ $appointment->patient->user->name }}

Le informamos que la cita médica ha sido **cancelada** correctamente.
@endif

## Detalles de la Cita Cancelada:
*   **Referencia:** {{ $appointment->reference }}
*   **Servicio:** {{ $appointment->service->name }}
*   **Fecha:** {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('l d \d\e F, Y') }}
*   **Hora:** {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
*   **Modalidad:** {{ $appointment->service->type === 'virtual' ? '💻 Virtual (Reunión de Zoom Eliminada)' : '🏥 Presencial' }}

@if($appointment->notes)
**Notas/Motivo adjunto:**
_{{ $appointment->notes }}_
@endif

Agradecemos su atención al sistema de gestión.

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
