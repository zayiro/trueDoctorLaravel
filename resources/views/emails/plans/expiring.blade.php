@component('mail::message')
# Tu plan vence pronto

Hola {{ $userName }},

Tu **{{ $planName }}** vence el **{{ $endsAt }}**{{ $daysLeft <= 1 ? ' (es mañana)' : ' (faltan ' . $daysLeft . ' días)' }}. Puedes renovarlo ahora mismo y los {{ $period }} nuevos se suman al final del tiempo que te queda, sin perder nada.

@component('mail::button', ['url' => $renewUrl])
Renovar mi plan
@endcomponent

Si no lo renuevas, pasarás al plan gratuito y algunas funciones (como el número de sedes) se ajustarán a los límites de ese plan.
@endcomponent
