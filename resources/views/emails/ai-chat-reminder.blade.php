<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 12px;">
<tr><td align="center">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden;">
        <tr>
            <td style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); background-color: #4f46e5; padding: 30px 32px; text-align: center;">
                <p style="margin: 0 0 6px 0; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #c7d2fe;">{{ $brand }}</p>
                <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff;">{{ $heading }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 28px 32px 8px 32px;">
                <p style="margin: 0 0 22px 0; font-size: 15px; line-height: 23px; color: #334155;">{{ $body }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 0 32px 28px 32px; text-align: center;">
                <a href="{{ $url }}" target="_blank" style="background-color: #4f46e5; color: #ffffff; padding: 13px 28px; font-size: 14px; font-weight: 700; border-radius: 10px; text-decoration: none; display: inline-block;">{{ $buttonLabel }}</a>
            </td>
        </tr>
        <tr>
            <td style="background-color: #fffbeb; padding: 18px 32px; border-top: 1px solid #fef3c7; border-bottom: 1px solid #fef3c7; text-align: center;">
                <p style="margin: 0; font-size: 11px; line-height: 16px; color: #b45309; font-weight: 500;">{{ $disclaimer }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 18px 24px; background-color: #f8fafc; text-align: center;">
                <p style="margin: 0 0 4px 0; font-size: 11px; color: #64748b;">{{ $privacy }}</p>
                <p style="margin: 0; font-size: 11px; color: #94a3b8;">&copy; {{ date('Y') }} {{ $brand }}</p>
            </td>
        </tr>
    </table>
</td></tr>
</table>
</body>
</html>
