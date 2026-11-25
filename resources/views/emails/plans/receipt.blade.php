@component('mail::message')
# Pago recibido

Hola {{ $userName }},

Recibimos tu pago y tu **{{ $planName }}** ya está activo. ¡Gracias por confiar en OpenDoctor!

@component('mail::table')
| Detalle | |
|:--|--:|
| Plan | {{ $planName }} |
| Valor pagado | {{ $amount }} |
| Fecha del pago | {{ $paidAt }} |
| Activo hasta | {{ $endsAt }} |
| Referencia | {{ $reference }} |
@endcomponent

Si renuevas en los últimos {{ $renewDays }} días de tu plan, los {{ $period }} nuevos se suman al final del tiempo que te queda.

@component('mail::button', ['url' => $profileUrl])
Ver mi plan
@endcomponent

Guarda este correo como comprobante de tu pago.
@endcomponent
