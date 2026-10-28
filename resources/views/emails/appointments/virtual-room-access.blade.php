<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://w3.org">
<html xmlns="http://w3.org" lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Acceso a la Sala Virtual - opendoctor.online</title>    
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="padding: 32px 32px 24px 32px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-size: 22px; font-weight: 800; color: #1e40af; margin-bottom: 16px;">
                                Open<span style="color: #2563eb;">Doctor</span><span style="color: #64748b; font-size: 14px; font-weight: 400;">.online</span>
                            </div>
                            <div style="display: inline-block; background-color: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase;">
                                ✓ Cita Actualizada
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style='font-family: Arial, sans-serif; padding: 20px; line-height: 1.6;'>
                                <h2 style='color: #1e3a8a;'>¡Tu videollamada está lista!</h2>
                                <h1 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; color: #0f172a;">¡Hola, {{ $patientName }}!</h1>
                                <p>El doctor ha permitido tu acceso a la sala virtual. Puedes ingresar haciendo clic en el siguiente botón:</p>
                                <p style='margin-top: 25px;'>                                    
                                    <a href="{{ route('appointments.room', ['appointment' => $appointmentId]) }}" 
                                    style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 8px;">
                                        Ingresar a la videoconsulta
                                    </a>
                                </p>
                             
                                <p style="margin-top: 25px; font-size: 0.9em; color: #666;">
                                    Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                    <a href="https://opendoctor.online/appointments/{{ $appointmentId }}/room" style="color: #007bff; text-decoration: none;">https://opendoctor.online/appointments/{{ $appointmentId }}/room</a>
                                </p>

                                <!-- Línea Divisoria -->
                                <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 40px 0 20px 0;'>                                
                            </div>
                        </td>
                    </tr>                   
                </table>
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; text-align: center; margin-top: 24px;">
                    <tr>
                        <td style="padding: 0 20px; color: #94a3b8; font-size: 12px; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;">Mensaje automático generado por la suite de <strong>opendoctorOnline</strong>. Por favor, no respondas directamente a este correo.</p>
                            <p style="margin: 0 0 16px 0;">Soporte técnico: <a href="mailto:support@opendoctor.online" style="color: #6366f1; text-decoration: underline;">support@opendoctor.online</a>.</p>
                            <p style="margin: 0; font-size: 11px; color: #cbd5e1;">© 2026 opendoctor.online. <br><a href="https://opendoctor.online/privacy" style="color: #94a3b8; text-decoration: none;">Privacidad</a> | <a href="https://opendoctor.online/terms" style="color: #94a3b8; text-decoration: none;">Términos</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
