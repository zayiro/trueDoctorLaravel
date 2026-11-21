@component('mail::message')
@php
    $a = $appointment;
    $paciente = $a->patient->user->name ?? 'Paciente';
    $esVirtual = ($a->service->type ?? null) === 'virtual';
@endphp
# Hola, {{ $role === 'patient' ? $recipientName : 'Dr(a). ' . $recipientName }}

@if($role === 'patient')
Tu cita médica ahora será atendida por **{{ $newDoctorName }}**. La fecha, la hora y el lugar **no cambian**.
@elseif($role === 'previous_doctor')
Esta cita ya **no está asignada a ti**: la clínica la reasignó a **{{ $newDoctorName }}**. Ese horario queda libre en tu agenda.
@else
La clínica te asignó una cita que antes estaba con **{{ $previousDoctorName }}**. Ya aparece en tu agenda.
@endif

## Detalles de la cita:
*   **Referencia:** {{ $a->reference }}
@if($role !== 'patient')
*   **Paciente:** {{ $paciente }}
@endif
*   **Servicio:** {{ $a->service->name ?? '' }}
*   **Fecha:** {{ \Carbon\Carbon::parse($a->date)->translatedFormat('l d \d\e F, Y') }}
*   **Hora:** {{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }}
*   **Modalidad:** {{ $esVirtual ? '💻 Virtual' : '🏥 Presencial' . ($a->address ? ' · ' . $a->address->name : '') }}
@if($role === 'patient')
*   **Antes:** {{ $previousDoctorName }}
*   **Ahora:** {{ $newDoctorName }}
@endif

@if($reasonText)
**Motivo del cambio:**
_{{ $reasonText }}_

@endif
@if($esVirtual)
@if($role === 'patient')
El enlace de tu videoconsulta **sigue siendo el mismo**; no tienes que hacer nada.
@elseif($role === 'new_doctor')
La sala de la videoconsulta ya está disponible para ti desde tu agenda.
@endif
@endif

@if($role === 'new_doctor')
@component('mail::button', ['url' => url('/dashboard')])
Ver mi agenda
@endcomponent
@endif

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
