<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — OpenDoctor</title>
    <style>
        body { margin: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f6f8; color: #333333; }
        .kc-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
        .kc-card { max-width: 480px; width: 100%; background: #ffffff; border: 1px solid #e1e5e8; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 32px 28px; text-align: center; }
        .kc-icon { font-size: 40px; margin-bottom: 8px; }
        .kc-card h1 { font-size: 22px; margin: 0 0 12px 0; color: #111827; }
        .kc-card p { font-size: 15px; line-height: 1.6; color: #555555; margin: 0 0 22px 0; }
        .kc-btn { display: inline-block; padding: 12px 24px; background: #28a745; color: #ffffff; border-radius: 8px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="kc-wrap">
        <div class="kc-card">
            <div class="kc-icon">{{ $ok ? '✅' : 'ℹ️' }}</div>
            <h1>{{ $title }}</h1>
            <p>{{ $text }}</p>
            <a class="kc-btn" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        </div>
    </div>
</body>
</html>
