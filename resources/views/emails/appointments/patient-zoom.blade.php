<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace de tu Teleconferencia</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f9; color: #333333; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        .header { background-color: #4f46e5; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .content p { margin: 0 0 20px 0; font-size: 16px; }
        .appointment-info { background-color: #f8fafc; border-left: 4px solid #4f46e5; padding: 15px; margin-bottom: 25px; border-radius: 0 4px 4px 0; }
        .appointment-info div { font-size: 14px; margin-bottom: 5px; }
        .appointment-info strong { color: #1e293b; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 12px 30px; font-size: 16px; font-weight: bold; border-radius: 6px; display: inline-block; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); }
        .password-box { background-color: #f1f5f9; border: 1px dashed #cbd5e1; padding: 12px; text-align: center; border-radius: 6px; margin-bottom: 20px; font-size: 15px; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <div class="email-container">
        <!-- Encabezado -->
        <div class="header">
            <h1>Tu Teleconsulta está Lista</h1>
        </div>

        <!-- Contenido Principal -->
        <div class="content">
            <p>Hola,</p>
            <p>Te informamos que el enlace para tu próxima videollamada médica ha sido generado con éxito en segundo plano. A continuación, encuentras los detalles de tu cita:</p>

            <div class="appointment-info">
                <div><strong>Referencia de Cita:</strong> {{ $appointment->reference }}</div>
                <div><strong>Fecha y Hora:</strong> {{ $appointment->scheduled_at }}</div>
                <div><strong>Duración:</strong> {{ $appointment->duration ?? 45 }} minutos</div>
            </div>

            <p>Para ingresar a la reunión virtual a la hora programada, haz clic en el siguiente botón de acceso directo:</p>

            <!-- Botón de Acceso Directo -->
            <!-- Modifica tu bloque para que quede así: -->
            <div class="btn-container">
                <a href="{{ route('appointments.room', ['appointment' => $appointment->id]) }}" 
                target="_blank" 
                class="btn">
                    Unirse a la Teleconsulta
                </a>
            </div>


            <!-- Datos de Contingencia -->
            <div class="password-box">
                Si la aplicación de Zoom te solicita una contraseña de acceso, utiliza la siguiente:<br>
                <strong>{{ $zoomPassword }}</strong>
            </div>

            <p>Por favor, asegúrate de contar con una buena conexión a internet y tener tu cámara y micrófono listos unos minutos antes de la sesión.</p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            Este es un correo automático generado por el sistema, por favor no respondas a este mensaje.<br>
            &copy; 2026 OpenDoctor.online - Todos los derechos reservados.
        </div>
    </div>

</body>
</html>
