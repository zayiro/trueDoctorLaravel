<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('account_cleanup.texts.subject') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f6f8; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6f8; padding-top: 40px; padding-bottom: 40px; }
        .card { max-width: 600px; background: #ffffff; margin: 0 auto; border-radius: 8px; border: 1px solid #e1e5e8; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #28a745; padding: 26px; text-align: center; color: #ffffff; }
        .header h2 { margin: 0; font-size: 26px; font-weight: 700; }
        .content { padding: 32px 30px; }
        .content p { font-size: 16px; color: #555555; margin: 0 0 18px 0; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #28a745; color: #ffffff !important; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .small { font-size: 13px !important; color: #888888 !important; }
        .footer { font-size: 13px; color: #777777; text-align: center; border-top: 1px solid #e1e5e8; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header"><h2>OpenDoctor</h2></div>
            <div class="content">
                <p>Hola, {{ ucfirst($user->name) }}:</p>
                <p>{{ config('account_cleanup.texts.intro') }}</p>
                <p>{{ config('account_cleanup.texts.cta_intro') }}</p>
                <div style="text-align: center; margin: 26px 0;">
                    <a href="{{ $keepUrl }}" target="_blank" class="btn">{{ config('account_cleanup.texts.cta') }}</a>
                </div>
                <p class="small">{{ str_replace(':days', (string) config('account_cleanup.keep_link_days', 3), config('account_cleanup.texts.link_note')) }}</p>
                <p>{{ config('account_cleanup.texts.ignore') }}</p>
            </div>
            <div class="footer">
                Atentamente,<br>
                <strong>Equipo de Atención al Paciente</strong> — opendoctor.online
            </div>
        </div>
    </div>
</body>
</html>
