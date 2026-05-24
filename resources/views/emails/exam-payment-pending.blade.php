<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: sans-serif; color: #334155;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 550px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <tr>
            <td style="background-color: #0f172a; padding: 24px; text-align: center;">
                <h2 style="margin: 0; color: #ffffff; font-size: 20px;">Enlace de recuperación de tu orden</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 32px; text-align: center;">
                <p style="margin: 0 0 16px 0; font-size: 15px; font-weight: bold; color: #1e293b;">¿Se cerró tu ventana de pago?</p>
                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 22px; color: #64748b;">
                    Detectamos que subiste con éxito tu examen médico, pero no logramos confirmar el pago. No te preocupes, guardamos tu progreso de forma segura.
                </p>
                
                <!-- Botón hacia la URL Firmada -->
                <a href="{{ $recoveryUrl }}" target="_blank" style="background-color: #4f46e5; color: #ffffff; padding: 12px 24px; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block;">
                    Continuar con mi lectura ahora
                </a>

                <p style="margin: 24px 0 0 0; font-size: 11px; color: #94a3b8;"> Este enlace es privado, seguro y vencerá automáticamente en 24 horas.</p>
            </td>
        </tr>
    </table>
</body>
</html>
