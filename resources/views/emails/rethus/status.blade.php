@component('mail::message')
# Hola, Dr(a). {{ $doctorName }}

@if($kind === 'verified')
Verificamos tu registro en ReTHUS. Desde ahora tu perfil público muestra la insignia **«{{ config('rethus.texts.badge') }}»**@if($expiresAt) y la verificación está vigente hasta el **{{ $expiresAt }}**@endif.
@elseif($kind === 'mismatch')
No pudimos verificar tu registro en ReTHUS.

**{{ $reasonLabel }}**

{{ $reasonHint }}

Mientras tanto sigues atendiendo con normalidad; solo que tu perfil no muestra la insignia.
@elseif($kind === 'expiring')
Tu verificación en ReTHUS vence el **{{ $expiresAt }}**. Para que tu perfil siga mostrando la insignia, solicita la verificación de nuevo desde tu panel antes de esa fecha.
@else
Tu verificación en ReTHUS venció y tu perfil dejó de mostrar la insignia. Puedes solicitarla de nuevo desde tu panel cuando quieras; sigues atendiendo con normalidad.
@endif

@if($kind !== 'verified')
@component('mail::button', ['url' => $url])
Ir a mi verificación
@endcomponent
@endif

{{ config('rethus.texts.disclaimer') }}

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
