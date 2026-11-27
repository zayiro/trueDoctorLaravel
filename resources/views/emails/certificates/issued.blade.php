@component('mail::message')
# Hola, {{ $recipientName }}

@if($certificate->type === 'attendance')
Tu médico{{ $doctorName !== '' ? ', ' . $doctorName . ',' : '' }} emitió tu **{{ $typeLabel }}**. Va adjunta a este correo en PDF.
@else
Tu médico{{ $doctorName !== '' ? ', ' . $doctorName . ',' : '' }} emitió tu **{{ $typeLabel }}**.

Por tu privacidad no lo enviamos adjunto: inicia sesión en tu cuenta y descárgalo cuando quieras.

@component('mail::button', ['url' => $downloadUrl])
Descargar mi certificado
@endcomponent
@endif

## Datos del documento
*   **Tipo:** {{ $typeLabel }}
*   **Código:** {{ $code }}

Quien reciba el documento puede comprobarlo en línea con el código o el QR del PDF, aquí: [{{ $verifyUrl }}]({{ $verifyUrl }}).

Si tú no pediste este documento o ves algún dato incorrecto, responde a este correo y lo revisamos.

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent
