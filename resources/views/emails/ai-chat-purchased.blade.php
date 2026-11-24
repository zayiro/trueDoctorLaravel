<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#2563eb;color:#ffffff;padding:20px 28px;font-size:14px;font-weight:bold;letter-spacing:.3px;">
                            {{ $brand }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 12px;font-size:22px;color:#0f172a;">{{ $heading }}</h1>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#334155;">{{ $body }}</p>

                            <p style="margin:0 0 24px;">
                                <a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 24px;border-radius:12px;">{{ $buttonLabel }}</a>
                            </p>

                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#334155;">{{ $expires }}</p>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#64748b;">{{ $privacy }}</p>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#0369a1;">{{ \App\Services\AiChat\AiChatReportText::retention($locale) }}</p>
                            <p style="margin:16px 0 0;padding-top:16px;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.6;color:#94a3b8;">{{ $disclaimer }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
