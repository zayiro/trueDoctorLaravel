@component('mail::message')
# Tu plan terminó

Hola {{ $userName }},

Tu **{{ $planName }}** terminó y tu cuenta pasó al **{{ $freeName }}**.

@if ($addressesOff > 0)
Como el plan gratuito permite menos sedes, **{{ $addressesOff }} {{ $addressesOff === 1 ? 'sede quedó desactivada' : 'sedes quedaron desactivadas' }}**. No se borró nada: al volver a un plan de pago podrás activarlas de nuevo.
@else
No se desactivó ninguna sede ni se borró información.
@endif

@if ($doctorsOff > 0)
**{{ $doctorsOff }} {{ $doctorsOff === 1 ? 'especialista quedó pausado' : 'especialistas quedaron pausados' }}** porque el plan gratuito permite menos. No se eliminó a nadie: al volver a un plan de pago se reactivan solos.

@endif
@component('mail::button', ['url' => $renewUrl])
Volver a un plan de pago
@endcomponent
@endcomponent
