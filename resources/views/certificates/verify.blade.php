<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Verificar certificado · OpenDoctorOnline</title>
<style>
    .cv-body { margin: 0; min-height: 100vh; background: #eff6ff; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1f2937; display: flex; flex-direction: column; }
    .cv-top { padding: 22px 20px 12px 20px; text-align: center; }
    .cv-brandlink { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
    .cv-logo { height: 52px; width: auto; border-radius: 12px; display: block; }
    .cv-brand { font-size: 24px; font-weight: 800; color: #1d4ed8; letter-spacing: -0.3px; }
    .cv-main { flex: 1; display: flex; justify-content: center; padding: 8px 16px 40px 16px; }
    .cv-card { width: 100%; max-width: 560px; background: #ffffff; border: 1px solid #dbeafe; border-radius: 18px; box-shadow: 0 10px 30px rgba(29, 78, 216, 0.08); padding: 28px 26px; box-sizing: border-box; align-self: flex-start; }
    .cv-h1 { margin: 0 0 6px 0; font-size: 22px; line-height: 1.25; font-weight: 800; color: #111827; }
    .cv-lead { margin: 0 0 20px 0; font-size: 15px; line-height: 1.55; color: #4b5563; }
    .cv-form { display: flex; gap: 10px; flex-wrap: wrap; }
    .cv-input { flex: 1 1 220px; min-width: 0; padding: 13px 14px; font-size: 16px; letter-spacing: 1px; text-transform: uppercase; border: 1.5px solid #bfdbfe; border-radius: 12px; outline: none; color: #111827; background: #f8fafc; box-sizing: border-box; }
    .cv-input:focus { border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
    .cv-btn { padding: 13px 22px; font-size: 15px; font-weight: 700; color: #ffffff; background: #1d4ed8; border: 0; border-radius: 12px; cursor: pointer; }
    .cv-btn:hover { background: #1e40af; }
    .cv-badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 800; margin: 0 0 12px 0; }
    .cv-ok { background: #dcfce7; color: #166534; }
    .cv-warn { background: #fef3c7; color: #92400e; }
    .cv-neutral { background: #f1f5f9; color: #334155; }
    .cv-rows { margin: 18px 0 0 0; border-top: 1px solid #e5e7eb; }
    .cv-row { display: flex; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .cv-row .k { color: #6b7280; flex: 0 0 42%; }
    .cv-row .v { color: #111827; font-weight: 700; text-align: right; word-break: break-word; }
    .cv-note { margin: 18px 0 0 0; padding: 12px 14px; background: #f8fafc; border-radius: 12px; font-size: 13px; line-height: 1.5; color: #475569; }
    .cv-back { display: inline-block; margin-top: 18px; font-size: 14px; font-weight: 700; color: #1d4ed8; text-decoration: none; }
    .cv-foot { padding: 0 20px 28px 20px; text-align: center; font-size: 12px; line-height: 1.5; color: #64748b; max-width: 560px; margin: 0 auto; }
    .cv-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: 1px; }
</style>
</head>
<body class="cv-body">

<div class="cv-top">
    <a class="cv-brandlink" href="{{ url('/') }}">
        <img class="cv-logo" src="{{ asset('images/logoAuth.jpg') }}" alt="OpenDoctorOnline" onerror="this.style.display='none'">
        <span class="cv-brand">OpenDoctorOnline</span>
    </a>
</div>

<div class="cv-main">
    <div class="cv-card">

        @if ($state === 'valid')
            <span class="cv-badge cv-ok">✓ Certificado auténtico</span>
            <h1 class="cv-h1">{{ $typeLabel }}</h1>
            <p class="cv-lead">Este documento fue emitido a través de OpenDoctor y no ha sido alterado desde su emisión.</p>

            <div class="cv-rows">
                <div class="cv-row"><span class="k">Código</span><span class="v cv-mono">{{ $code }}</span></div>
                <div class="cv-row"><span class="k">Fecha de la consulta</span><span class="v">{{ $consultationAt }}</span></div>
                <div class="cv-row"><span class="k">Fecha de emisión</span><span class="v">{{ $issuedAt }}</span></div>
                <div class="cv-row"><span class="k">Profesional</span><span class="v">Dr(a). {{ $doctorName }}</span></div>
                <div class="cv-row"><span class="k">Tarjeta profesional</span><span class="v">No. {{ $doctorLicense }}</span></div>
                @if (!empty($specialties))
                    <div class="cv-row"><span class="k">Especialidad</span><span class="v">{{ $specialties }}</span></div>
                @endif
                @if (!empty($patientMasked))
                    <div class="cv-row"><span class="k">Paciente</span><span class="v">{{ $patientMasked }}</span></div>
                @endif
                @if (!empty($patientIdMasked))
                    <div class="cv-row"><span class="k">Documento</span><span class="v">{{ $patientIdMasked }}</span></div>
                @endif
                    @foreach (($incapacityRows ?? []) as $icRow)
                        <div class="cv-row"><span class="k">{{ $icRow[0] }}</span><span class="v">{{ $icRow[1] }}</span></div>
                    @endforeach
            </div>

            <p class="cv-note">Compara estos datos con los del documento que recibiste. Por privacidad no mostramos información clínica ni los datos completos del paciente.</p>
            <a class="cv-back" href="{{ route('certificates.verify.form') }}">Verificar otro certificado</a>

        @elseif ($state === 'revoked')
            <span class="cv-badge cv-warn">Certificado anulado</span>
            <h1 class="cv-h1">{{ $typeLabel }}</h1>
            <p class="cv-lead">Quien emitió este certificado lo anuló{{ !empty($revokedAt) ? ' el ' . $revokedAt : '' }}. No debe tomarse como vigente.</p>
            <div class="cv-rows">
                <div class="cv-row"><span class="k">Código</span><span class="v cv-mono">{{ $code }}</span></div>
                @if (!empty($issuedAt))
                    <div class="cv-row"><span class="k">Fecha de emisión</span><span class="v">{{ $issuedAt }}</span></div>
                @endif
            </div>
            <p class="cv-note">Si necesitas un certificado vigente, pídeselo de nuevo al profesional que te atendió.</p>
            <a class="cv-back" href="{{ route('certificates.verify.form') }}">Verificar otro certificado</a>

        @elseif ($state === 'notfound')
            <span class="cv-badge cv-neutral">Código no encontrado</span>
            <h1 class="cv-h1">No encontramos este código</h1>
            <p class="cv-lead">Revisa que esté completo y bien escrito. Los códigos tienen letras y números, nunca llevan 0, 1, O, I ni L. Si lo copiaste de una imagen, prueba escribiéndolo a mano.</p>
            <form class="cv-form" method="GET" action="{{ route('certificates.verify.form') }}">
                <input class="cv-input" type="text" name="code" value="{{ $code ?? '' }}" placeholder="OD-XXXX-XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" required>
                <button class="cv-btn" type="submit">Verificar</button>
            </form>
            <p class="cv-note">Si sigues sin encontrarlo, escríbele a quien te entregó el documento para que confirme el código.</p>

        @elseif ($state === 'invalid_format')
            <span class="cv-badge cv-neutral">Revisa el código</span>
            <h1 class="cv-h1">El código no tiene el formato esperado</h1>
            <p class="cv-lead">Debe verse así: <span class="cv-mono">OD-XXXX-XXXX-XXXX</span>. Lo encuentras en el pie del documento, junto al código QR.</p>
            <form class="cv-form" method="GET" action="{{ route('certificates.verify.form') }}">
                <input class="cv-input" type="text" name="code" value="{{ $typed ?? '' }}" placeholder="OD-XXXX-XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" required>
                <button class="cv-btn" type="submit">Verificar</button>
            </form>

        @elseif ($state === 'unverifiable')
            <span class="cv-badge cv-neutral">No pudimos confirmarlo ahora</span>
            <h1 class="cv-h1">No pudimos completar la verificación</h1>
            <p class="cv-lead">El código <span class="cv-mono">{{ $code }}</span> existe, pero en este momento no logramos comprobar el documento. Intenta de nuevo en unos minutos. Si el problema continúa, escríbele a quien te entregó el certificado o contacta a OpenDoctor.</p>
            <a class="cv-back" href="{{ route('certificates.verify.show', ['code' => $code]) }}">Intentar de nuevo</a>

        @elseif ($state === 'error')
            <span class="cv-badge cv-neutral">Un momento</span>
            <h1 class="cv-h1">Algo no salió como esperábamos</h1>
            <p class="cv-lead">No pudimos hacer la verificación en este momento. Intenta de nuevo en unos minutos.</p>
            <a class="cv-back" href="{{ route('certificates.verify.form') }}">Volver a intentar</a>

        @else
            <h1 class="cv-h1">Verifica un certificado</h1>
            <p class="cv-lead">Escribe el código que aparece en el pie del documento, o escanea el código QR que lo acompaña.</p>
            <form class="cv-form" method="GET" action="{{ route('certificates.verify.form') }}">
                <input class="cv-input" type="text" name="code" value="" placeholder="OD-XXXX-XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" required autofocus>
                <button class="cv-btn" type="submit">Verificar</button>
            </form>
            <p class="cv-note">La verificación confirma que el documento fue emitido a través de OpenDoctor y que no ha sido alterado. No muestra información clínica.</p>
        @endif

    </div>
</div>

<div class="cv-foot">
    {{ config('certificates.texts.footer_disclaimer') }}
</div>

</body>
</html>
