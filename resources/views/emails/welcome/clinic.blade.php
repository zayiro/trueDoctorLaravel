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
        .header { background-color: #6f42c1; padding: 30px; text-align: center; color: #ffffff; }
        .header h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
        .header h3 { margin: 5px 0 0 0; font-size: 18px; font-weight: 400; opacity: 0.9; }
        .content { padding: 35px 30px; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .steps-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .steps-box h4 { margin: 0 0 15px 0; color: #6f42c1; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
        .step-item { margin-bottom: 12px; font-size: 15px; color: #475569; }
        .step-number { font-weight: bold; color: #6f42c1; margin-right: 5px; }
        .benefits-box { background-color: #faf5ff; border: 1px dashed #e9d5ff; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .benefits-box h4 { margin: 0 0 10px 0; color: #5b21b6; font-size: 16px; }
        .benefits-list { margin: 0; padding-left: 20px; font-size: 14px; color: #4c1d95; }
        .benefits-list li { margin-bottom: 6px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #6f42c1; color: #ffffff !important; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px; text-align: center; }
        .footer { margin-top: 30px; font-size: 13px; color: #777777; text-align: center; border-top: 1px solid #e1e5e8; padding-top: 20px; }
        .footer a { color: #6f42c1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            
            <div class="header">
                <h2>OpenDoctor</h2>
                <h3>Te damos la bienvenida al entorno de gestión, {{ ucfirst($user->name) }}</h3>
            </div>

            <div class="content">
                <p>Agradecemos el registro de su institución en <strong>opendoctor.online</strong>. Hemos diseñado un panel centralizado con el fin de potenciar la infraestructura digital de su centro de salud y optimizar la administración de sus agendas medicas.</p>
                
                <p>Su perfil corporativo ya se encuentra activo. A continuación, le presentamos la ruta recomendada para comenzar la transición digital de su centro médico:</p>

                <!-- GUÍA INSTITUCIONAL -->
                <div class="steps-box">
                    <h4>Pauta de configuración inicial:</h4>
                    <div class="step-item">
                        <span class="step-number">1.</span> Ingrese a su panel de administración institucional utilizando las credenciales corporativas registradas.
                    </div>
                    <div class="step-item">
                        <span class="step-number">2.</span> Diríjase a la sección de perfil y suba la <strong>documentación requerida</strong> (Identificación oficial (RUT) y el Registro REPS).
                    </div>
                    <div class="step-item">
                        <span class="step-number">3.</span> Nuestro equipo validará sus credenciales en un lapso menor a 24 horas.
                    </div>
                    <div class="step-item" style="margin-bottom: 0;">
                        <span class="step-number">4.</span> Una vez aprobado(a), podrá comenzar a registrar las diferentes funcionalidades.
                    </div>
                    <div class="step-item">
                        <span class="step-number">5.</span> Configure la infraestructura básica de su organización (sedes físicas, consultorios disponibles y áreas de especialidad).
                    </div>
                    <div class="step-item">
                        <span class="step-number">6.</span> Registre a su personal de salud y asigne las agendas correspondientes a cada consultorio o canal de telemedicina.
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 25px;">
                    <a href="{{ route('login') }}" class="btn">Configurar Centro Médico</a>
                </div>

                <!-- CARACTERÍSTICAS CORPORATIVAS -->
                <div class="benefits-box">
                    <h4>Capacidades del Panel Institucional:</h4>
                    <ul class="benefits-list">
                        <li><strong>Gestor Multi-Agenda:</strong> Controle los horarios de múltiples profesionales de la salud desde un único entorno administrativo.</li>
                        <li><strong>Módulos de Telemedicina Avanzados:</strong> Habilite salas virtuales corporativas encriptadas para sus consultas a distancia.</li>
                        <li><strong>Reportería y Analítica:</strong> Acceda a estadísticas avanzadas sobre la tasa de asistencia de pacientes y productividad de su personal.</li>
                        <li><strong>Seguridad de Datos Clínicos:</strong> Almacenamiento seguro bajo estrictas normativas internacionales de protección de historias clínicas.</li>
                    </ul>
                </div>

                <p style="font-size: 14px; color: #888888; margin-top: 25px; margin-bottom: 0;">
                    Este registro le otorga acceso a las herramientas iniciales de control. Para activar módulos integrados de facturación electrónica o integraciones con sistemas ERP locales, puede solicitar una consultoría técnica.
                </p>
            </div>

            <!-- PIE DE PÁGINA REQUERIDO -->
            <div class="footer">
                Revise los términos de cobertura y la tabla completa de soluciones institucionales en: <br>
                <a href="https://opendoctor.online" target="_blank">https://opendoctor.online</a>
                <br><br>
                Atentamente,<br>
                <strong>Dirección de Gestión Institucional</strong> — opendoctor.online
            </div>

        </div>
    </div>
</body>
</html>
