<x-mail::message>
@if($forAdmin)
# Cambio de cuenta bancaria

**{{ $name }}** {{ $created ? 'registró' : 'cambió' }} su cuenta para recibir liquidaciones.

<x-mail::panel>
{{ $bank }} · {{ $type }} · {{ $masked }}
{{ $when }} (hora de Colombia)
</x-mail::panel>

Antes de transferir a esta cuenta, confirma con el beneficiario por otro medio. Es el punto donde más se intentan fraudes.
@else
# {{ $created ? 'Registraste tu cuenta' : 'Cambió tu cuenta' }}

Hola {{ $name }}, {{ $created ? 'registraste' : 'se actualizó' }} la cuenta donde recibirás tus liquidaciones.

<x-mail::panel>
{{ $bank }} · {{ $type }} · {{ $masked }}
{{ $when }} (hora de Colombia)
</x-mail::panel>

Si **no fuiste tú**, escríbenos de inmediato a {{ config('mail.from.address') }} y cambia tu contraseña. No haremos transferencias a una cuenta que no reconozcas.
@endif

{{ config('app.name') }}
</x-mail::message>
