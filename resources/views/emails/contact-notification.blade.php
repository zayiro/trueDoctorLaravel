<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Contacto</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <!-- Encabezado con Identidad del SaaS -->
        <tr>
            <td style="background: linear-gradient(135deg, #0d9488 0%, #0891b2 100%); padding: 32px 24px; text-align: center;">
                <span style="background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-bottom: 8px;">
                    Administración
                </span>
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">
                    Nuevo Mensaje Recibido
                </h1>
                <p style="color: #ccfbf1; margin: 4px 0 0 0; font-size: 13px;">
                    Un usuario ha enviado una solicitud a través del formulario.
                </p>
            </td>
        </tr>

        <!-- Cuerpo del Correo (Detalles) -->
        <tr>
            <td style="padding: 24px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    
                    <!-- Fila: Nombre -->
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; width: 30%; font-size: 14px; font-weight: 600; color: #64748b;">
                            Remitente
                        </td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; font-weight: bold;">
                            {{ $contactMessage->name }}
                        </td>
                    </tr>

                    <!-- Fila: Email -->
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 600; color: #64748b;">
                            Correo
                        </td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #0891b2; font-weight: 500;">
                            <a href="mailto:{{ $contactMessage->email }}" style="color: #0891b2; text-decoration: none;">
                                {{ $contactMessage->email }}
                            </a>
                        </td>
                    </tr>

                    <!-- Fila: Asunto / Tipo -->
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 600; color: #64748b;">
                            Asunto
                        </td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b;">
                            {{ $contactMessage->subject }}
                        </td>
                    </tr>

                    <!-- Fila: Mensaje Completo -->
                    <tr>
                        <td colspan="2" style="padding-top: 20px;">
                            <label style="font-size: 13px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">
                                Mensaje enviado:
                            </label>
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; font-size: 14px; line-height: 1.6; color: #334155; white-space: pre-line;">
                                {{ $contactMessage->message }}
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

        <!-- Pie de Página (Acciones Rápidas) -->
        <tr>
            <td style="padding: 0 24px 24px 24px; text-align: center;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                    <tr>
                        <td align="center" style="border-radius: 30px;" bgcolor="#0d9488">
                            <a href="mailto:{{ $contactMessage->email }}?subject=RE: {{ urlencode($contactMessage->subject) }}" target="_blank" style="font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 30px; display: inline-block;">
                                ✉️ Responder Directamente
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin: 20px 0 0 0; font-size: 11px; color: #94a3b8; text-align: center;">
                    Este es un correo automático enviado por el backend de tu plataforma SaaS.<br>
                    © {{ date('Y') }} OpenDoctor. Todos los derechos reservados.
                </p>
            </td>
        </tr>

    </table>
</body>
</html>
