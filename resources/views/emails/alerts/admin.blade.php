@component('mail::message')
# Avisos de OpenDoctor

@if ($isTest)
Este es un correo de prueba: si lo estás leyendo, los avisos del administrador llegan bien a esta dirección.
@else
Estos puntos del sistema necesitan tu atención:
@endif

@foreach ($items as $item)
**{{ $item['level'] === 'crit' ? '🔴' : '🟠' }} {{ $item['title'] }}**
{{ $item['detail'] }}

@endforeach
@component('mail::button', ['url' => $url])
Abrir el dashboard
@endcomponent

No recibirás este mismo aviso otra vez en las próximas 24 horas, salvo que aparezca uno nuevo.
@endcomponent
