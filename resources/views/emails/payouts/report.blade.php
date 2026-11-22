<x-mail::message>
# Soporte de liquidación

Hola {{ $name }},

Te enviamos el soporte de la transferencia que realizamos por tus citas pagadas en línea.

<x-mail::panel>
**N.º {{ $number }}**
Valor transferido: **{{ $total }}**
Citas incluidas: {{ $lines }}
Fecha de pago: {{ $paidAt }}
Referencia: {{ $reference }}
</x-mail::panel>

El detalle de cada cita está en el PDF adjunto. También puedes descargarlo cuando quieras desde **Mis liquidaciones**.

<x-mail::button :url="$url">
Ver mis liquidaciones
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
