<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#7c3aed;">OpenDoctorOnline</p>
        <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;">{{ $heading }}</h1>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#334155;">{{ $body }}</p>
        <p style="margin:0 0 24px;">
            <a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 24px;border-radius:12px;">{{ $buttonLabel }}</a>
        </p>
        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
            Por tu privacidad no incluimos resultados en este correo. Guarda este enlace: solo tú lo tienes.
        </p>
    </div>
</body>
</html>