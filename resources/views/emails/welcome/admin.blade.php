<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Administración - OpenDoctor</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f6f8; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6f8; padding-top: 40px; padding-bottom: 40px; }
        .card { max-width: 600px; background: #ffffff; margin: 0 auto; border-radius: 8px; border: 1px solid #e1e5e8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #212529; padding: 30px; text-align: center; color: #ffffff; }
        .header h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
        .header h3 { margin: 5px 0 0 0; font-size: 18px; font-weight: 400; opacity: 0.9; }
        .content { padding: 35px 30px; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .steps-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .steps-box h4 { margin: 0 0 15px 0; color: #212529; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .step-item { margin-bottom: 12px; font-size: 15px; color: #475569; }
        .step-number { font-weight: bold; color: #212529; margin-right: 5px; }
        .benefits-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .benefits-box h4 { margin: 0 0 10px 0; color: #1e293b; font-size: 16px; }
        .benefits-list { margin: 0; padding-left: 20px; font-size: 14px; color: #334155; }
        .benefits-list li { margin-bottom: 6px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #212529; color: #ffffff !important; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px; text-align: center; }
        .footer { margin-top: 30px; font-size: 13px; color: #777777; text-align: center; border-top: 1px solid #e1e5e8; padding-top: 20px; }
        .footer a { color: #212529; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            
            <div class="header">
                <h2>OpenDoctor</h2>
                <h3>Control de Infraestructura — {{ ucfirst($user->name) }}</h3>
            </div>

            <div class="content">
                <p>Se ha generado y activado con éxito una cuenta con privilegios de **Administrador Global** en el sistema <strong>opendoctor.online</strong>.</p>
                
                <p>Debido al nivel de acceso de esta cuenta, todas las acciones y modificaciones realizadas dentro del panel de control quedarán registradas de forma permanente en el historial de auditoría de seguridad de la plataforma [🔍].</p>

                <!-- GUÍA DE ACCESO -->
                <div class="steps-box">
                    <h4>Protocolo de inicio y verificación:</h4>
                    <div class="step-item">
                        <span class="step-number">1.</span> Acceda al panel técnico administrativo mediante el enlace seguro inferior.
                    </div>
                    <div class="step-item">
                        <span class="step-number">2.</span> Complete la configuración de sus credenciales de seguridad y active el doble factor de autenticación (2FA) si está disponible.
                    </div>
                    <div class="step-item" style="margin-bottom: 0;">
                        <span class="step-number">3.</span> Revise la cola de doctores en estado `pending_validation` para comenzar con las auditorías de credenciales médicas.
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 25px;">
                    <a href="{{ route('login') }}" class="btn">Ingresar al Panel Técnico</a>
                </div>

                <!-- FACULTADES DE CONTROL -->
                <div class="benefits-box">
                    <h4>Módulos de Control Global Asignados:</h4>
                    <ul class="benefits-list">
                        <li><strong>Validación de Personal de Salud:</strong> Potestad total para aprobar, rechazar o suspender perfiles de doctores y clínicas [🔍].</li>
                        <li><strong>Auditoría de Transacciones:</strong> Supervisión de flujos financieros, pasarelas de pago y planes de suscripción activos [🔍].</li>
                        <li><strong>Mantenimiento del Sistema:</strong> Monitoreo técnico de logs, gestión de especialidades médicas y reportería general [🔍].</li>
                    </ul>
                </div>

                <p style="font-size: 13px; color: #ef4444; margin-top: 25px; margin-bottom: 0; font-weight: 500;">
                    Aviso de seguridad: Mantenga sus credenciales a salvo. El equipo de ingeniería de OpenDoctor jamás le solicitará su clave de acceso por correo o canales de mensajería externa.
                </p>
            </div>

            <!-- PIE DE PÁGINA -->
            <div class="footer">
                Consulte la documentación interna del sistema y políticas corporativas en: <br>
                <a href="https://opendoctor.online" target="_blank">https://opendoctor.online</a>
                <br><br>
                Atentamente,<br>
                <strong>División de Seguridad de la Información</strong> — opendoctor.online
            </div>

        </div>
    </div>
</body>
</html>
