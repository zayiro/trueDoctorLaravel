@component('mail::message')
# Tu plan venció, pero aún estás a tiempo

Hola {{ $userName }},

Tu **{{ $planName }}** venció el **{{ $endedAt }}**. Para que no pierdas nada de un día para otro, conservas todos tus beneficios hasta el **{{ $graceUntil }}**.

Si renuevas antes de esa fecha, tus sedes y tu configuración quedan exactamente como están. Después de esa fecha pasarás al plan gratuito y algunas sedes se desactivarán (no se borra nada).

@component('mail::button', ['url' => $renewUrl])
Renovar mi plan
@endcomponent
@endcomponent
