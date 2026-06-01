<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://w3.org">
<html xmlns="http://w3.org" lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Confirmación de Cita - opendoctor.online</title>    
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="padding: 32px 32px 24px 32px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-size: 22px; font-weight: 800; color: #1e40af; margin-bottom: 16px;">
                                open<span style="color: #2563eb;">doctor</span><span style="color: #64748b; font-size: 14px; font-weight: 400;">.online</span>
                            </div>
                            <div style="display: inline-block; background-color: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase;">
                                ✓ Cita Confirmada
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 32px 24px 32px;">
                            <h1 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 700; color: #0f172a;">¡Hola, {{ $appointment->patient->user->name }}!</h1>
                            <p style="margin: 0; font-size: 15px; color: #475569; line-height: 1.6;">Tu cita médica ha sido agendada correctamente con los siguientes detalles:</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 20px;">
                                <tr>
                                    <td style="padding-bottom: 14px; border-bottom: 1px dashed #e2e8f0;">
                                        <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Profesional</span>
                                        <strong style="font-size: 16px; color: #1e293b;">Dr. {{ $appointment->doctor->user->name }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 0; border-bottom: 1px dashed #e2e8f0;">
                                        <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Servicio</span>
                                        <span style="font-size: 15px; color: #334155; font-weight: 500;">{{ $appointment->service->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 0;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td width="50%" style="vertical-align: top;">
                                                    <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Fecha</span>
                                                    <span style="font-size: 15px; color: #334155; font-weight: 600;">{{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('d \d\e F, Y') }}</span>
                                                </td>
                                                <td width="50%" style="vertical-align: top; padding-left: 10px;">
                                                    <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Hora</span>
                                                    <span style="font-size: 15px; color: #334155; font-weight: 600;">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 14px; border-top: 1px solid #e2e8f0;">
                                        <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Modalidad de atención</span>
                                        <div style="font-size: 14px; color: #334155;">
                                            @if($appointment->service->type === 'virtual')
                                                <span style="display: inline-block; background-color: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">Telemedicina (Virtual)</span>
                                                <div style="margin-top: 6px;"><a href="{{ $appointment->meeting_link }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 8px;">Ingresar a la videoconsulta</a></div>
                                            @else
                                                <span style="display: inline-block; background-color: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px; margin-bottom: 4px;">Consulta Presencial</span>
                                                <p style="margin: 4px 0 0 0; font-size: 14px; color: #334155; font-weight: 500;">{{ $appointment->address->address }} <span style="color: #64748b; font-weight: 400;">({{ $appointment->address->name }})</span></p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 32px 32px; text-align: center;">
                            <p style="margin: 0 0 16px 0; font-size: 14px; color: #64748b;">¿Necesitas realizar cambios en tu cita médica?</p>
                            <a href="https://opendoctor.online" style="display: inline-block; background-color: #ffffff; color: #2563eb; border: 1px solid #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 10px;">Gestionar mi cita</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 32px; background-color: #fffbeb; border-top: 1px solid #fef3c7; text-align: left;">
                            <p style="margin: 0; font-size: 13px; color: #b45309; line-height: 1.5;"><strong>Recomendación importante:</strong> Por favor asiste o conéctate al menos 5 minutos antes de la hora acordada. Si la cita requiere estudios previos, tenlos a la mano.</p>
                        </td>
                    </tr>
                </table>
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; text-align: center; margin-top: 24px;">
                    <tr>
                        <td style="padding: 0 20px; color: #94a3b8; font-size: 12px; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;">Mensaje automático generado por la suite de <strong>opendoctor.online</strong>. Por favor, no respondas directamente a este correo.</p>
                            <p style="margin: 0 0 16px 0;">Soporte técnico: <a href="mailto:soporte@opendoctor.online" style="color: #6366f1; text-decoration: underline;">soporte@opendoctor.online</a>.</p>
                            <p style="margin: 0; font-size: 11px; color: #cbd5e1;">© 2026 opendoctor.online. <br><a href="https://opendoctor.online/privacy" style="color: #94a3b8; text-decoration: none;">Privacidad</a> | <a href="https://opendoctor.online/terms" style="color: #94a3b8; text-decoration: none;">Términos</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
