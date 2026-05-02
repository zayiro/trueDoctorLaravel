<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
        <h2 style="color: #2563eb; text-align: center;">¡Cita Confirmada!</h2>
        <p>Hola <strong>{{ $appointment->patient->user->name }}</strong>,</p>
        <p>Tu cita con el <strong>Dr. {{ $appointment->doctor->user->name }}</strong> ha sido programada con éxito.</p>
        
        <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Servicio:</strong> {{ $appointment->service->name }}</p>
            <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
            <p><strong>Modalidad:</strong> {{ ucfirst($appointment->service->type) }}</p>
            
            @if($appointment->service->type === 'virtual')
                <p><strong>Link de acceso:</strong> <a href="{{ $appointment->meeting_link }}">{{ $appointment->meeting_link }}</a></p>
            @else
                <p><strong>Dirección:</strong> {{ $appointment->address->address }} ({{ $appointment->address->name }})</p>
            @endif
        </div>

        <p style="font-size: 12px; color: #777; text-align: center;">
            ¡Gracias por confiar en nosotros!.
        </p>
    </div>
</body>
</html>
