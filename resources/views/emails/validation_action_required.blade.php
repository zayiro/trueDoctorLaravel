<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acción requerida en tu cuenta</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#1e293b;padding:20px 28px;color:#ffffff;font-size:18px;font-weight:bold;">
                            OpenDoctor<span style="color:#34d399;">Online</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:16px;">Hola {{ $user->name }},</p>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                                Estamos revisando la documentación de tu cuenta
                                {{ $profileType === 'clinic' ? 'institucional' : 'médica' }}
                                y <strong>necesitamos que corrijas lo siguiente</strong> para poder aprobarla:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="padding:12px 14px;margin-bottom:8px;background-color:#fef2f2;border-left:4px solid #dc2626;border-radius:6px;">
                                            <div style="font-size:15px;font-weight:bold;color:#991b1b;">{{ $item['label'] }}</div>
                                            <div style="font-size:14px;color:#374151;margin-top:2px;">{{ $item['reason'] }}</div>
                                            <div style="font-size:13px;color:#6b7280;margin-top:4px;">{{ $item['hint'] }}</div>
                                        </td>
                                    </tr>
                                    <tr><td style="height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr>
                                @endforeach
                            </table>

                            @if (filled($note))
                                <div style="margin:0 0 20px;padding:12px 14px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                    <div style="font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Nota del equipo de validación</div>
                                    <div style="font-size:14px;line-height:1.5;color:#1f2937;">{!! nl2br(e($note)) !!}</div>
                                </div>
                            @endif

                            <p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#374151;">
                                Ingresa a tu cuenta y sube de nuevo el documento desde el panel de verificación.
                                Aceptamos archivos JPG, PNG o PDF de hasta 4&nbsp;MB.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="background-color:#4f46e5;border-radius:8px;">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">
                                            Corregir mis documentos
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.5;">
                                Si ya enviaste los documentos corregidos, ignora este mensaje: los revisaremos lo antes posible.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background-color:#f8fafc;font-size:12px;color:#94a3b8;text-align:center;">
                            © {{ date('Y') }} OpenDoctorOnline · Este correo se envió porque registraste una cuenta en nuestra plataforma.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
