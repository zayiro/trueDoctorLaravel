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
        .header { background-color: #28a745; padding: 30px; text-align: center; color: #ffffff; }
        .header h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
        .header h3 { margin: 5px 0 0 0; font-size: 18px; font-weight: 400; opacity: 0.9; }
        .content { padding: 35px 30px; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .steps-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .steps-box h4 { margin: 0 0 15px 0; color: #28a745; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .step-item { margin-bottom: 12px; font-size: 15px; color: #475569; }
        .step-number { font-weight: bold; color: #28a745; margin-right: 5px; }
        .benefits-box { background-color: #f0fdf4; border: 1px dashed #bbf7d0; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .benefits-box h4 { margin: 0 0 10px 0; color: #166534; font-size: 16px; }
        .benefits-list { margin: 0; padding-left: 20px; font-size: 14px; color: #14532d; }
        .benefits-list li { margin-bottom: 6px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #28a745; color: #ffffff !important; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px; text-align: center; }
        .footer { margin-top: 30px; font-size: 13px; color: #777777; text-align: center; border-top: 1px solid #e1e5e8; padding-bottom: 20px; }
        .footer a { color: #28a745; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            
            <div class="header">
                <h2>OpenDoctor</h2>
                <h3>¡Hola, {{ ucfirst($user->name) }}! Te damos la bienvenida</h3>
            </div>

            <div class="content">
                <p>Tu cuenta ha sido creada con éxito en <strong>opendoctor.online</strong>. Nos alegra acompañarte en el cuidado de tu salud y la de tu familia de una forma más ágil, segura y moderna.</p>
                
                <p>A partir de este momento, tienes acceso a un ecosistema de salud digital completo donde podrás gestionar tus consultas médicas con total libertad.</p>

                <!-- GUÍA DE INICIO RÁPIDO -->
                <div class="steps-box">
                    <h4>¿Cómo agendar tu primera cita?</h4>
                    <div class="step-item">
                        <span class="step-number">1.</span> Ingresa a la plataforma usando tu cuenta con el botón de abajo.
                    </div>
                    <div class="step-item">
                        <span class="step-number">2.</span> Explora el directorio de especialistas filtrando por especialidad médica, ciudad o ingresa los sintomas directamente.
                    </div>
                    <div class="step-item">
                        <span class="step-number">3.</span> Selecciona el día y la hora de tu preferencia en la agenda en tiempo real del doctor.
                    </div>
                    <div class="step-item" style="margin-bottom: 0;">
                        <span class="step-number">4.</span> Elige la modalidad de tu atención (Presencial o Telemedicina) y confirma tu solicitud de forma instantánea.
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 25px;">
                    <a href="https://opendoctor.online" target="_blank" class="btn">Buscar Médicos Especialistas</a>
                </div>

                <!-- BENEFICIOS DE LA PLATAFORMA -->
                <div class="benefits-box">
                    <h4>Herramientas activas en tu perfil:</h4>
                    <ul class="benefits-list">
                        <li><strong>Telemedicina integrada:</strong> Conéctate a tus videoconsultas directo desde la app, sin instalar nada externo.</li>
                        <li><strong>Historial de consultas:</strong> Accede a tus recetas digitales, órdenes médicas y recomendaciones desde cualquier dispositivo.</li>
                        <li><strong>Recordatorios automáticos:</strong> Recibe alertas de tus próximas citas médicas para que nunca olvides un control.</li>
                        <li><strong>Pagos seguros en línea:</strong> Cancela el valor de tus consultas con tu método de pago preferido (si el doctor lo tiene habilitado).</li>
                    </ul>
                </div>

                <p style="font-size: 14px; color: #888888; margin-top: 25px; margin-bottom: 0;">
                    Recuerda que registrarte y buscar especialistas siempre será completamente gratuito para ti. Solo pagas el valor de las consultas que decidas agendar.
                </p>
            </div>

            <!-- PIE DE PÁGINA REQUERIDO -->
            <div class="footer">
                <br>
                Atentamente,<br>
                <strong>Equipo de Atención al Paciente</strong> — opendoctor.online<br><br>
            </div>

        </div>
    </div>
</body>
</html>
