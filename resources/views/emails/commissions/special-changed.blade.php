<x-mail::message>
# Cambió tu porcentaje de servicio

Hola {{ $name }}, actualizamos el porcentaje de servicio que paga el paciente cuando reserva y paga en línea {{ $isClinic ? 'en tu clínica' : 'contigo' }}.

<x-mail::panel>
**Citas virtuales:** {{ $virtualText }}

**Citas presenciales:** {{ $presentialText }}

{{ $when }} (hora de Colombia)
</x-mail::panel>

Ese porcentaje lo asume el paciente: tú recibes el 100 % del valor de tu consulta. Aplica a los pagos nuevos; las citas que ya se pagaron conservan el porcentaje con el que se cobraron.

Si tienes dudas, escríbenos a {{ config('mail.from.address') }}.

{{ config('app.name') }}
</x-mail::message>
