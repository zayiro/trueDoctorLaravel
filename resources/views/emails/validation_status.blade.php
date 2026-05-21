<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Cuenta - OpenDoctor</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f6f8; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f6f8; padding-bottom: 40px; padding-top: 40px; }
        .main-card { max-width: 600px; background-color: #ffffff; margin: 0 auto; border-radius: 8px; border: 1px solid #e1e5e8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #0056b3; padding: 25px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px; }
        .content { padding: 35px 30px; background-color: #ffffff; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .content strong { color: #222222; }
        .status-box { padding: 20px; border-radius: 6px; margin: 25px 0; text-align: center; }
        .status-approved { background-color: #e6f4ea; border-left: 4px solid #137333; color: #137333; }
        .status-rejected { background-color: #fce8e6; border-left: 4px solid #c5221f; color: #c5221f; }
        .status-box h3 { margin: 0 0 8px 0; font-size: 18px; }
        .btn { display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: bold; text-decoration: none; border-radius: 5px; margin: 15px 0; text-align: center; }
        .btn-success { background-color: #137333; color: #ffffff !important; }
        .btn-whatsapp { background-color: #25d366; color: #ffffff !important; }
        .footer { text-align: center; padding: 20px; font-size: 13px; color: #777777; line-height: 1.5; }
        .divider { height: 1px; background-color: #e1e5e8; margin: 25px 0; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="main-card">
        
        <!-- Encabezado con la marca -->
        <div class="header">
            <h1>OpenDoctor</h1>
        </div>

        <!-- Contenido Principal -->
        <div class="content">
            <p>Estimado(a) **Dr(a). {{ $user->name }}**,</p>
            <p>Le escribimos para notificarle el estado actual de su solicitud de validación de perfil médico en nuestra plataforma.</p>

            @if($status === 'approved')
                <!-- CASO 1: DOCTOR APROBADO -->
                <div class="status-box status-approved">
                    <h3>¡Su cuenta ha sido aprobada con éxito!</h3>
                    <p style="margin: 0; font-size: 15px;">Nuestro equipo ha verificado sus credenciales profesionales correctamente.</p>
                </div>
                <p>A partir de este momento, su perfil es visible para los pacientes. Ya tiene acceso completo para configurar sus horarios de consulta, historias clínicas y canales de atención médica.</p>
                <div style="text-align: center;">
                    <a href="{{ url('/dashboard') }}" class="btn btn-success">Ingresar al Panel de Control</a>
                </div>
            @else
                <!-- CASO 2: DOCTOR RECHAZADO -->
                <div class="status-box status-rejected">
                    <h3>Su perfil requiere verificaciones adicionales</h3>
                    <p style="margin: 0; font-size: 15px;">Lamentablemente, no pudimos validar sus datos con la información suministrada.</p>
                </div>
                <p>Esto suele ocurrir por inconsistencias menores en la documentación, datos de registro ilegibles o falta de soportes obligatorios oficiales.</p>
                <p>Para solucionar este inconveniente de forma inmediata y ayudarle a activar su cuenta personalmente, hemos habilitado un canal directo y prioritario de atención humana:</p>
                <div style="text-align: center;">
                    <a href="{{ $whatsappLink }}" class="btn btn-whatsapp" target="_blank">Contactar Soporte por WhatsApp</a>
                </div>
            @endif

            <div class="divider"></div>

            <p style="font-size: 14px; color: #888888; margin-bottom: 0;">
                Si tiene alguna duda sobre esta notificación o no solicitó un registro en nuestra web, ignore este correo o responda directamente para reportarlo.
            </p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            Atentamente,<br>
            <strong>Equipo de Validación y Control</strong><br>
            <a href="https://opendoctor.online" style="color: #0056b3; text-decoration: none;">opendoctor.online</a>
        </div>

    </div>
</div>

</body>
</html>
