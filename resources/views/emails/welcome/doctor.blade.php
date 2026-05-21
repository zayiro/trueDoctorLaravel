<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te damos la bienvenida a OpenDoctor</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f6f8; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6f8; padding-top: 40px; padding-bottom: 40px; }
        .card { max-width: 600px; background: #ffffff; margin: 0 auto; border-radius: 8px; border: 1px solid #e1e5e8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #0056b3; padding: 30px; text-align: center; color: #ffffff; }
        .header h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
        .header h3 { margin: 5px 0 0 0; font-size: 18px; font-weight: 400; opacity: 0.9; }
        .content { padding: 35px 30px; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .steps-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .steps-box h4 { margin: 0 0 15px 0; color: #0056b3; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .step-item { margin-bottom: 12px; font-size: 15px; color: #475569; }
        .step-number { font-weight: bold; color: #0056b3; margin-right: 5px; }
        .plan-box { background-color: #eff6ff; border: 1px dashed #bfdbfe; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .plan-box h4 { margin: 0 0 10px 0; color: #1e40af; font-size: 16px; }
        .plan-list { margin: 0; padding-left: 20px; font-size: 14px; color: #1e3a8a; }
        .plan-list li { margin-bottom: 6px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #0056b3; color: #ffffff !important; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px; text-align: center; }
        .footer { margin-top: 30px; font-size: 13px; color: #777777; text-align: center; border-top: 1px solid #e1e5e8; padding-top: 20px; }
        .footer a { color: #0056b3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            
            <div class="header">
                <h2>OpenDoctor</h2>
                <h3>¡Te damos la bienvenida, Dr(a). {{ ucfirst($user->name) }}!</h3>
            </div>

            <div class="content">
                <p>Le agradecemos haber elegido <strong>opendoctor.online</strong> para expandir el alcance de sus consultas y optimizar la gestión de sus pacientes.</p>
                
                <p>Su perfil profesional ha sido registrado con éxito. Para garantizar la seguridad de la plataforma y la confianza de los pacientes, iniciamos un proceso de verificación.</p>

                <!-- PASOS REQUERIDOS -->
                <div class="steps-box">
                    <h4>Próximos pasos para activar su perfil:</h4>
                    <div class="step-item">
                        <span class="step-number">1.</span> Ingrese a su panel de administración con el botón de abajo.
                    </div>
                    <div class="step-item">
                        <span class="step-number">2.</span> Diríjase a la sección de perfil y suba la <strong>documentación requerida</strong> (Identificación oficial y Tarjeta Profesional).
                    </div>
                    <div class="step-item">
                        <span class="step-number">3.</span> Nuestro equipo validará sus credenciales en un lapso menor a 24 horas.
                    </div>
                    <div class="step-item" style="margin-bottom: 0;">
                        <span class="step-number">4.</span> Una vez aprobado(a), podrá comenzar a registrar los servicios médicos que ofrece, configurar sus horarios y activar su agenda.
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 25px;">
                    <a href="{{ route('login') }}" class="btn">Ingresar al Administrador</a>
                </div>

                <!-- INFORMACIÓN DEL PLAN POR DEFECTO -->
                <div class="plan-box">
                    <h4>Su Plan Actual: <strong>Free (Gratuito)</strong></h4>
                    <p style="font-size: 14px; margin-bottom: 10px; color: #1e3a8a;">Usted ha iniciado con nuestra suscripción base sin costo, que incluye:</p>
                    <ul class="plan-list">
                        <li>Visibilidad completa en nuestro directorio de especialistas.</li>
                        <li>Gestión básica de agenda médica y citas online.</li>
                        <li>Historial clínico digital para sus pacientes atendidos.</li>
                        <li>Notificaciones de recordatorio automáticas por correo electrónico.</li>
                    </ul>
                </div>

                <p style="font-size: 14px; color: #888888; margin-top: 25px; margin-bottom: 0;">
                    Si en el futuro requiere herramientas avanzadas como recordatorios masivos por WhatsApp, pasarela para cobros online de citas o sincronización con Google Calendar, podrá escalar su cuenta en cualquier momento.
                </p>
            </div>

            <!-- PIE DE PÁGINA REQUERIDO -->
            <div class="footer">
                Conozca más sobre nuestras coberturas y características en: <br>
                <a href="https://opendoctor.online" target="_blank">https://opendoctor.online</a>
                <br><br>
                Atentamente,<br>
                <strong>Equipo de Soporte Médico</strong> — opendoctor.online
            </div>

        </div>
    </div>
</body>
</html>
