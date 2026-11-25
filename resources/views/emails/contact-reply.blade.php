<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a tu mensaje</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; border: 1px solid #e1e5e8; overflow: hidden;">

        <tr>
            <td style="background-color: #28a745; padding: 28px 24px; text-align: center;">
                <h2 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px;">OpenDoctor</h2>
                <p style="margin: 6px 0 0 0; color: #ffffff; font-size: 15px; opacity: 0.9;">Respuesta a tu mensaje</p>
            </td>
        </tr>

        <tr>
            <td style="padding: 30px 28px 10px 28px;">
                <p style="margin: 0 0 16px 0; font-size: 16px; color: #333333;">
                    Hola, {{ ucfirst($contactMessage->name) }}:
                </p>
                <div style="font-size: 15px; line-height: 1.7; color: #475569;">
                    {!! nl2br(e($replyBody)) !!}
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding: 10px 28px 24px 28px;">
                <p style="margin: 18px 0 8px 0; font-size: 12px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">
                    Tu mensaje original
                </p>
                <div style="background-color: #f8fafc; border-left: 3px solid #cbd5e1; border-radius: 4px; padding: 12px 14px; font-size: 13px; line-height: 1.6; color: #64748b; white-space: pre-line;">{{ $contactMessage->message }}</div>
                <p style="margin: 18px 0 0 0; font-size: 13px; color: #94a3b8;">
                    Si necesitas algo más, responde directamente a este correo.
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding: 18px 24px; border-top: 1px solid #e1e5e8; text-align: center; font-size: 13px; color: #777777;">
                Atentamente,<br>
                <strong>Equipo de Soporte</strong> — <a href="https://opendoctor.online" style="color: #28a745; text-decoration: none;">opendoctor.online</a>
            </td>
        </tr>

    </table>
</body>
</html>
