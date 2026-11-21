@component('mail::message')
@php
    $a = $appointment;
    $providerName = ($a->clinic_id && $a->clinic) ? ($a->clinic->user->name ?? 'equipo de la clínica') : ('Dr(a). ' . ($a->doctor->user->name ?? ''));
    $medico = $a->doctor->user->name ?? 'Especialista';
    $paciente = $a->patient->user->name ?? 'Paciente';
    $esVirtual = ($a->service->type ?? null) === 'virtual';
@endphp
# Hola, {{ $providerName }}

Una cita de tu agenda fue **reprogramada**. El horario anterior quedó libre y el nuevo ya está ocupado.

## Detalles de la cita:
*   **Referencia:** {{ $a->reference }}
*   **Paciente:** {{ $paciente }}
*   **Especialista:** {{ $medico }}
*   **Servicio:** {{ $a->service->name ?? '' }}
*   **Horario anterior:** {{ $previousDateTime }}
*   **Nuevo horario:** {{ \Carbon\Carbon::parse($a->date)->translatedFormat('l d \d\e F, Y') }} a las {{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }}
*   **Modalidad:** {{ $esVirtual ? '💻 Virtual (la sala se actualizó al nuevo horario)' : '🏥 Presencial' . ($a->address ? ' · ' . $a->address->name : '') }}

@if($a->clinic_id)
@component('mail::button', ['url' => url('/clinic/appointments')])
Ver agenda de la clínica
@endcomponent
@else
@component('mail::button', ['url' => url('/dashboard')])
Ver mi agenda
@endcomponent
@endif

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
