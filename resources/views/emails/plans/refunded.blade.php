@component('mail::message')
# Registramos tu reembolso

Hola {{ $userName }},

Registramos la devolución de **{{ $amount }}** correspondiente a tu **{{ $planName }}**. Según tu banco, puede tardar unos días en reflejarse en tu medio de pago.

@if ($keptUntil)
Tu plan sigue vigente hasta el **{{ $keptUntil }}**, por el periodo anterior que ya habías pagado.
@elseif ($freeName)
Tu cuenta pasó al **{{ $freeName }}**.

@if ($addressesOff > 0)
**{{ $addressesOff }} {{ $addressesOff === 1 ? 'sede quedó desactivada' : 'sedes quedaron desactivadas' }}** porque el plan gratuito permite menos. No se borró nada: al volver a un plan de pago podrás activarlas de nuevo.

@endif
@if ($doctorsOff > 0)
**{{ $doctorsOff }} {{ $doctorsOff === 1 ? 'especialista quedó pausado' : 'especialistas quedaron pausados' }}** porque el plan gratuito permite menos. No se eliminó a nadie: al volver a un plan de pago se reactivan solos.

@endif
@endif
@component('mail::button', ['url' => $plansUrl])
Ver planes
@endcomponent
@endcomponent
