@component('mail::message')
# Hola, {{ $recipientName }}

Ya procesamos el **reembolso** de tu cita cancelada. Te devolvimos el valor total que pagaste.

## Detalles del reembolso:
*   **Cita:** {{ $reference }}
*   **Servicio:** {{ $serviceName }}
@if($dateLabel)
*   **Fecha de la cita:** {{ $dateLabel }}
@endif
*   **Valor devuelto:** {{ $amount }}
@if($providerReference)
*   **Referencia del reembolso:** {{ $providerReference }}
@endif

Cuánto tarda en verse reflejado depende de tu banco o medio de pago. Si pasados unos días hábiles no lo ves, escríbenos con la referencia de tu cita y lo revisamos contigo.

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
