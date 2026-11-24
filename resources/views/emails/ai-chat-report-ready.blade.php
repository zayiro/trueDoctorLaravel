@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatReportText::t($key, $r, $locale);
    $urgency = $report['urgency'] ?? 'media';
    $urgencyColors = ['baja' => ['#ecfdf5', '#047857'], 'media' => ['#fffbeb', '#b45309'], 'alta' => ['#fff7ed', '#c2410c'], 'urgente' => ['#fef2f2', '#b91c1c']];
    [$uBg, $uFg] = $urgencyColors[$urgency] ?? $urgencyColors['media'];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $t('mail_subject') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 12px;">
<tr><td align="center">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden;">

        <tr>
            <td style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); background-color: #4f46e5; padding: 32px; text-align: center;">
                <p style="margin: 0 0 6px 0; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #c7d2fe;">{{ $t('brand') }}</p>
                <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #ffffff;">{{ $t('mail_heading') }}</h1>
                @if ($date)
                    <p style="margin: 8px 0 0 0; font-size: 13px; color: #e0e7ff;">{{ $t('subtitle', ['date' => $date]) }}</p>
                @endif
            </td>
        </tr>

        <tr>
            <td style="padding: 28px 32px 8px 32px;">
                <p style="margin: 0 0 18px 0; font-size: 14px; line-height: 21px; color: #475569;">{{ $t('mail_intro') }}</p>

                <p style="margin: 0 0 18px 0; padding: 10px 14px; border-radius: 10px; background-color: {{ $uBg }}; color: {{ $uFg }}; font-size: 13px; font-weight: 700;">
                    {{ $t('urgency') }}: {{ $t('urgency_'.$urgency) }}
                </p>

                @if ($urgency === 'urgente')
                    <p style="margin: 0 0 18px 0; padding: 12px 14px; border-radius: 10px; background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13px; line-height: 19px;">{{ $t('urgent_banner') }}</p>
                @endif

                @if (!empty($report['reason']))
                    <h3 style="margin: 0 0 4px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('reason') }}</h3>
                    <p style="margin: 0 0 18px 0; font-size: 14px; line-height: 21px; color: #1e293b; font-weight: 600;">{{ $report['reason'] }}</p>
                @endif

                <h3 style="margin: 0 0 4px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('summary') }}</h3>
                <p style="margin: 0 0 18px 0; font-size: 14px; line-height: 22px; color: #334155; white-space: pre-line;">{{ $report['summary'] ?? '' }}</p>

                @if (!empty($report['impressions']))
                    <h3 style="margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('impressions') }}</h3>
                    <ul style="margin: 0 0 18px 0; padding-left: 20px; font-size: 14px; line-height: 21px; color: #334155;">
                        @foreach ($report['impressions'] as $i)<li style="margin-bottom: 4px;">{{ $i }}</li>@endforeach
                    </ul>
                @endif

                <h3 style="margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('action_plan') }}</h3>
                <ol style="margin: 0 0 18px 0; padding-left: 20px; font-size: 14px; line-height: 21px; color: #334155;">
                    @foreach (($report['action_plan'] ?? []) as $i)<li style="margin-bottom: 6px;">{{ $i }}</li>@endforeach
                </ol>

                @if (!empty($report['warning_signs']))
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 18px; background-color: #fef2f2; border-radius: 10px;">
                        <tr><td style="padding: 14px 16px;">
                            <h3 style="margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #b91c1c;">{{ $t('warning_signs') }}</h3>
                            <ul style="margin: 0; padding-left: 18px; font-size: 13px; line-height: 19px; color: #7f1d1d;">
                                @foreach ($report['warning_signs'] as $i)<li style="margin-bottom: 3px;">{{ $i }}</li>@endforeach
                            </ul>
                        </td></tr>
                    </table>
                @endif

                @if (!empty($report['questions']))
                    <h3 style="margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('questions') }}</h3>
                    <ul style="margin: 0 0 8px 0; padding-left: 20px; font-size: 14px; line-height: 21px; color: #334155;">
                        @foreach ($report['questions'] as $i)<li style="margin-bottom: 4px;">{{ $i }}</li>@endforeach
                    </ul>
                @endif
            </td>
        </tr>

        {{-- Especialista: solo si hay a dónde agendar (misma lógica que los exámenes) --}}
        @if ($targetSlug)
        <tr>
            <td style="padding: 8px 32px 28px 32px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #1e293b;">
                    <tr>
                        <td style="padding: 24px; text-align: center;">
                            <span style="background-color: #1e1b4b; color: #a5b4fc; padding: 4px 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px;">{{ $t('spec_badge') }}</span>
                            <h4 style="margin: 12px 0 6px 0; font-size: 16px; font-weight: 800; color: #ffffff;">{{ $t('spec_title') }}</h4>
                            <p style="margin: 0 0 20px 0; font-size: 13px; line-height: 19px; color: #94a3b8;">
                                @if ($hasSuggested && $available)
                                    {{ $t('spec_available', ['spec' => $suggestedName]) }}
                                @elseif ($hasSuggested)
                                    {{ $t('spec_fallback', ['spec' => $suggestedName, 'target' => $targetName]) }}
                                @else
                                    {{ $t('spec_generic', ['target' => $targetName]) }}
                                @endif
                            </p>
                            <a href="{{ route('search', ['specialty' => $targetSlug]) }}" target="_blank" style="background-color: #ffffff; color: #0f172a; padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 8px; text-decoration: none; display: inline-block;">{{ $t('spec_button', ['target' => $targetName]) }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        <tr>
            <td style="padding: 0 32px 28px 32px; text-align: center;">
                <a href="{{ $url }}" target="_blank" style="background-color: #4f46e5; color: #ffffff; padding: 13px 28px; font-size: 14px; font-weight: 700; border-radius: 10px; text-decoration: none; display: inline-block;">{{ $t('mail_button') }}</a>
            </td>
        </tr>

        @if (!empty($transcript))
        <tr>
            <td style="padding: 0 32px 28px 32px;">
                <h3 style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">{{ $t('mail_conversation') }}</h3>
                @foreach ($transcript as $line)
                    <p style="margin: 0 0 8px 0; padding: 10px 12px; border-radius: 10px; font-size: 13px; line-height: 19px; white-space: pre-line; {{ $line['you'] ? 'background-color: #eef2ff; color: #312e81;' : 'background-color: #f8fafc; color: #334155;' }}">
                        <strong>{{ $line['you'] ? $t('mail_you') : $t('mail_ai') }}:</strong> {{ $line['text'] }}
                    </p>
                @endforeach
            </td>
        </tr>
        @endif

        <tr>
            <td style="background-color: #fffbeb; padding: 20px 32px; border-top: 1px solid #fef3c7; border-bottom: 1px solid #fef3c7; text-align: center;">
                <p style="margin: 0; font-size: 11px; line-height: 16px; color: #b45309; font-weight: 500;">{{ $t('disclaimer') }}</p>
            </td>
        </tr>

        <tr>
            <td style="padding: 20px 24px; background-color: #f8fafc; text-align: center;">
                <p style="margin: 0 0 8px 0; font-size: 11px; line-height: 16px; color: #0369a1;">{{ \App\Services\AiChat\AiChatReportText::retention($locale) }} @if ($purgeDate)<strong>{{ $t('retention_until', ['date' => $purgeDate]) }}</strong>@endif</p>
                <p style="margin: 0 0 6px 0; font-size: 11px; color: #64748b;">{{ $t('mail_footer') }}</p>
                <p style="margin: 0; font-size: 11px; color: #94a3b8;">&copy; {{ date('Y') }} {{ $t('brand') }}</p>
            </td>
        </tr>
    </table>
</td></tr>
</table>
</body>
</html>
