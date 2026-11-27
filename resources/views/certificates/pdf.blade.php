<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $title }} {{ $code }}</title>
<style>
    @@page { margin: 34px 46px 38px 46px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.55; margin: 0; }
    table { border-collapse: collapse; }
    .cert-head { width: 100%; border-bottom: 2px solid #1d4ed8; }
    .cert-head td { vertical-align: bottom; padding: 0 0 10px 0; }
    .cert-logo { height: 46px; }
    .cert-brand { font-size: 22px; font-weight: bold; color: #1d4ed8; }
    .cert-brand-sub { font-size: 9px; color: #6b7280; }
    .cert-code-label { font-size: 8px; color: #6b7280; text-transform: uppercase; text-align: right; }
    .cert-code { font-size: 14px; font-weight: bold; color: #111827; text-align: right; letter-spacing: 1px; }
    .cert-title { text-align: center; font-size: 15px; font-weight: bold; color: #111827; text-transform: uppercase; margin: 26px 0 4px 0; letter-spacing: 0.5px; }
    .cert-issued { text-align: center; font-size: 9.5px; color: #6b7280; margin: 0 0 22px 0; }
    .cert-intro { font-size: 12px; text-align: justify; margin: 0 0 14px 0; line-height: 1.7; }
    .cert-box { border: 1px solid #d1d5db; background: #f9fafb; padding: 10px 14px; margin: 0 0 14px 0; text-align: justify; }
    .cert-box-label { font-size: 9px; color: #6b7280; text-transform: uppercase; margin: 0 0 3px 0; }
    .cert-note { font-size: 10px; color: #4b5563; margin: 0 0 14px 0; }
    .cert-pro { width: 100%; margin: 6px 0 16px 0; }
    .cert-pro td { padding: 3px 0; vertical-align: top; font-size: 10.5px; }
    .cert-pro td.k { width: 150px; color: #6b7280; }
    .cert-pro td.v { color: #111827; font-weight: bold; }
    .cert-section { font-size: 9px; color: #1d4ed8; text-transform: uppercase; font-weight: bold; margin: 18px 0 2px 0; border-bottom: 1px solid #dbeafe; padding-bottom: 2px; }
    .cert-sign { border: 1px solid #bfdbfe; background: #eff6ff; padding: 10px 14px; margin: 14px 0 0 0; }
    .cert-sign p { margin: 0 0 4px 0; font-size: 10px; }
    .cert-sign .strong { font-weight: bold; color: #1e3a8a; }
    .cert-foot { width: 100%; margin-top: 26px; border-top: 1px solid #e5e7eb; page-break-inside: avoid; }
    .cert-foot td { vertical-align: top; padding-top: 12px; }
    .cert-qr { width: 104px; height: 104px; }
    .cert-foot-text { font-size: 9px; color: #4b5563; padding-left: 14px; }
    .cert-foot-text p { margin: 0 0 6px 0; }
    .cert-url { font-size: 9px; color: #1d4ed8; word-break: break-all; }
</style>
</head>
<body>

<table class="cert-head">
    <tr>
        @if (!empty($logo))
            <td style="width: 58px;"><img class="cert-logo" src="{{ $logo }}" alt="OpenDoctorOnline"></td>
        @endif
        <td>
            <div class="cert-brand">OpenDoctorOnline</div>
            <div class="cert-brand-sub">Telemedicina y gestión de citas médicas</div>
        </td>
        <td style="width: 190px;">
            <div class="cert-code-label">Código de verificación</div>
            <div class="cert-code">{{ $code }}</div>
        </td>
    </tr>
</table>

<div class="cert-title">{{ $title }}</div>
<div class="cert-issued">Emitido el {{ $issuedAt }} (hora de Colombia)</div>

<p class="cert-intro">{{ $intro }}</p>

@if (!empty($incapacity))
    <div class="cert-section">Datos de la incapacidad</div>
    <table class="cert-pro">
        @foreach ($incapacity['rows'] as $icRow)
            <tr><td class="k">{{ $icRow[0] }}</td><td class="v">{{ $icRow[1] }}</td></tr>
        @endforeach
    </table>
    @foreach ($incapacity['notes'] as $icNote)
        <p class="cert-note">{{ $icNote }}</p>
    @endforeach
@endif

@if (!empty($body))
    <div class="cert-box">
        <div class="cert-box-label">Contenido del certificado</div>
        {!! nl2br(e($body)) !!}
    </div>
@endif

@if (!empty($diagnosisLabel))
    <div class="cert-box">
        <div class="cert-box-label">{{ $diagnosisLabel }}</div>
        {!! nl2br(e($diagnosisText)) !!}
        @if (!empty($cie10))
            <br>CIE-10: {{ $cie10 }}
        @endif
    </div>
@endif

@if (!empty($noDiagnosisNote))
    <p class="cert-note">{{ $noDiagnosisNote }}</p>
@endif

<div class="cert-section">Profesional de la salud</div>
<table class="cert-pro">
    <tr><td class="k">Nombre</td><td class="v">Dr(a). {{ $doctor['name'] }}</td></tr>
    <tr><td class="k">Tarjeta profesional</td><td class="v">No. {{ $doctor['license'] }}</td></tr>
    @if (!empty($doctor['identification']))
        <tr><td class="k">Documento de identidad</td><td class="v">{{ $doctor['identification'] }}</td></tr>
    @endif
    @if (!empty($doctor['specialties']))
        <tr><td class="k">Especialidad</td><td class="v">{{ $doctor['specialties'] }}</td></tr>
    @endif
</table>

<div class="cert-sign">
    <p>{{ $declaration }}</p>
    <p class="strong">Firmado electrónicamente por Dr(a). {{ $doctor['name'] }} · Tarjeta profesional No. {{ $doctor['license'] }} · {{ $issuedAt }}</p>
</div>

<table class="cert-foot">
    <tr>
        @if (!empty($qr))
            <td style="width: 108px;"><img class="cert-qr" src="{{ $qr }}" alt="Código QR de verificación"></td>
        @endif
        <td class="cert-foot-text">
            <p>{{ $footerVerify }}</p>
            <p class="cert-url">{{ $verifyUrl }}</p>
            <p>{{ $footerDisclaimer }}</p>
        </td>
    </tr>
</table>

</body>
</html>
