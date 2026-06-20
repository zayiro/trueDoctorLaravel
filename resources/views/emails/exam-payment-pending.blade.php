<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; -webkit-font-smoothing: antialiased;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 550px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0;">
        <!-- Encabezado -->
        <tr>
            <td style="background-color: #0f172a; padding: 28px 24px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.025em;">OpenDoctor</h1>
            </td>
        </tr>
        
        <!-- Cuerpo del Correo -->
        <tr>
            <td style="padding: 40px 32px; text-align: center;">
                <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #1e293b;">Continúa con el proceso de tus exámenes</h2>
                
                <p style="margin: 0 0 12px 0; font-size: 15px; line-height: 24px; color: #475569; text-align: left;">
                    Hola,
                </p>
                <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 24px; color: #475569; text-align: left;">
                    Hemos guardado de forma segura la información de tu análisis médico. Para continuar con el proceso, realizar el pago correspondiente si aún no lo has hecho, o visualizar el informe final de tus resultados, puedes ingresar directamente a través del siguiente enlace:
                </p>
                
                <!-- Botón de Acción -->
                <div style="margin: 32px 0;">
                    <a href="{{ $recoveryUrl }}" target="_blank" style="background-color: #0284c7; color: #ffffff; padding: 14px 28px; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2); transition: background-color 0.2s ease;">
                        Ingresar a mis exámenes
                    </a>
                </div>

                <p style="margin: 0 0 32px 0; font-size: 14px; line-height: 22px; color: #64748b; text-align: left; background-color: #f1f5f9; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #cbd5e1;">
                    <strong>Nota:</strong> Si ya completaste el pago en la pasarela y la ventana se cerró antes de regresar a nuestro sitio, al ingresar al enlace verás el estado actualizado de tu transacción.
                </p>
                
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0 0 20px 0;">
                
                <!-- Pie de página -->
                <p style="margin: 0 0 4px 0; font-size: 12px; color: #94a3b8; font-weight: 600;">OpenDoctor - Plataforma Médica</p>
                <p style="margin: 0; font-size: 11px; color: #94a3b8;">Por tu seguridad, este enlace es de uso exclusivo para tu cuenta.</p>
            </td>
        </tr>
    </table>
</body>
</html>
