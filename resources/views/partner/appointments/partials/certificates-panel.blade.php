{{-- Panel «Certificados de esta cita» (historia clínica del doctor). Se incluye desde clinical-record.blade.php.
     REVISAR CON ABOGADO: los textos de este panel y de las ventanas de emisión. --}}
@php
    $crOk = true;
    $crDoctor = null;
    $crAttendance = ['ok' => false, 'message' => null];
    $crMedical = ['ok' => false, 'message' => null];
    $crIncap = ['ok' => false, 'reason' => null, 'message' => null];
    $crCertificates = collect();
    $crTz = 'America/Bogota';
    $crHistoryCie = null;

    try {
        $crTz = (string) config('certificates.display_timezone', 'America/Bogota');
        $crDoctor = auth()->user()?->doctor;
        $crIssuer = app(\App\Services\Certificates\CertificateIssuer::class);
        $crAttendance = $crIssuer->eligibility($appointment, $crDoctor, 'attendance');
        $crMedical = $crIssuer->eligibility($appointment, $crDoctor, 'medical');
        $crIncap = $crIssuer->eligibility($appointment, $crDoctor, 'incapacity');
        $crCertificates = \App\Models\Certificate::query()->where('appointment_id', $appointment->id)->orderByDesc('id')->get();
        $crHistoryCie = $appointment->patientHistory?->cie10_code;
    } catch (\Throwable $crE) {
        // El panel nunca debe romper la historia clínica: si algo falla, simplemente no se muestra.
        $crOk = false;
        report($crE);
    }

    $crPatientName = $appointment->patient->user->name ?? 'el paciente';
    $crWhen = $appointment->starts_at ? $appointment->starts_at->copy()->setTimezone($crTz) : null;
    $crStoreUrl = route('partner.appointments.clinical-record.certificates.store', ['appointment' => $appointment->id]);
@endphp

@if ($crOk)
<style>
    .cr-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 22px 24px; margin-bottom: 32px; }
    .cr-title { margin: 0; font-size: 17px; font-weight: 900; color: #0f172a; }
    .cr-sub { margin: 4px 0 0 0; font-size: 12px; line-height: 1.5; color: #64748b; }
    .cr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
    @@media (max-width: 760px) { .cr-grid { grid-template-columns: 1fr; } }
    .cr-box { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 18px; padding: 16px 18px; display: flex; flex-direction: column; gap: 10px; }
    .cr-box h3 { margin: 0; font-size: 13px; font-weight: 900; color: #1e293b; text-transform: uppercase; letter-spacing: 0.04em; }
    .cr-box p { margin: 0; font-size: 12px; line-height: 1.5; color: #475569; }
    .cr-note { font-size: 12px; line-height: 1.5; font-weight: 700; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 9px 12px; }
    .cr-btn { display: inline-block; align-self: flex-start; padding: 10px 18px; border: 0; border-radius: 12px; background: #4f46e5; color: #ffffff; font-size: 11px; font-weight: 900; letter-spacing: 0.06em; text-transform: uppercase; cursor: pointer; }
    .cr-btn:hover { background: #4338ca; }
    .cr-btn[disabled] { opacity: 0.6; cursor: wait; }
    .cr-btn-ghost { display: inline-block; padding: 9px 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #334155; font-size: 11px; font-weight: 900; letter-spacing: 0.06em; text-transform: uppercase; cursor: pointer; }
    .cr-btn-ghost:hover { background: #f1f5f9; }
    .cr-list { margin-top: 18px; border-top: 1px solid #f1f5f9; padding-top: 14px; display: flex; flex-direction: column; gap: 10px; }
    .cr-list-title { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
    .cr-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; border: 1px solid #e2e8f0; background: #ffffff; border-radius: 16px; padding: 12px 16px; }
    .cr-row-title { font-size: 13px; font-weight: 800; color: #0f172a; }
    .cr-row-meta { margin-top: 2px; font-size: 12px; color: #64748b; }
    .cr-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: 0.04em; }
    .cr-badge { display: inline-block; margin-left: 6px; padding: 2px 9px; border-radius: 999px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; vertical-align: middle; }
    .cr-badge-on { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .cr-badge-off { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
    .cr-actions { display: flex; align-items: center; gap: 10px; }
    .cr-link { font-size: 12px; font-weight: 800; color: #4f46e5; text-decoration: underline; }
    .cr-dlg { border: 0; padding: 0; border-radius: 22px; width: 92%; max-width: 560px; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35); }
    .cr-dlg::backdrop { background: rgba(15, 23, 42, 0.55); }
    .cr-form { padding: 24px; display: flex; flex-direction: column; gap: 12px; }
    .cr-form h3 { margin: 0; font-size: 18px; font-weight: 900; color: #0f172a; }
    .cr-form p { margin: 0; font-size: 13px; line-height: 1.55; color: #475569; }
    .cr-form label.cr-lab { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #334155; }
    .cr-form textarea, .cr-form input[type=text] { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 14px; padding: 10px 12px; font-size: 14px; color: #1e293b; font-family: inherit; }
    .cr-form textarea:focus, .cr-form input[type=text]:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
    .cr-check { display: flex; align-items: flex-start; gap: 10px; font-size: 12px; line-height: 1.5; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 10px 12px; }
    .cr-check input { margin-top: 3px; flex-shrink: 0; }
    .cr-diag { display: none; border: 1px dashed #c7d2fe; background: #eef2ff; border-radius: 14px; padding: 12px; flex-direction: column; gap: 10px; }
    .cr-warn { font-size: 12px; line-height: 1.5; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 9px 12px; }
    .cr-ok { font-size: 12px; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 9px 12px; }
    .cr-err { font-size: 12px; font-weight: 700; color: #9f1239; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 9px 12px; }
    .cr-form-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; padding-top: 4px; }
</style>

<div class="cr-card">
    <div>
        <h2 class="cr-title">Certificados de esta cita</h2>
        <p class="cr-sub">Emite una constancia de asistencia o un certificado médico con código de verificación y código QR. Quien lo reciba puede comprobarlo en {{ url('/verify-certificate') }}.</p>
    </div>

    @php($crNotice = session('cr_notice'))
    @if (is_array($crNotice) && !old('cr_form') && !empty($crNotice['text']))
        <div class="{{ ($crNotice['type'] ?? '') === 'success' ? 'cr-ok' : 'cr-err' }}" style="margin-top:14px;">{{ $crNotice['text'] }}</div>
    @endif

    <div class="cr-grid">
        <div class="cr-box">
            <h3>Constancia de asistencia</h3>
            <p>Confirma que el paciente fue atendido en esta cita (fecha y hora). No incluye diagnóstico ni información de la historia clínica.</p>
            @if ($crAttendance['ok'])
                <button type="button" class="cr-btn" onclick="document.getElementById('cr-dlg-attendance').showModal()">Emitir constancia</button>
            @else
                <div class="cr-note">{{ $crAttendance['message'] }}</div>
            @endif
        </div>

        <div class="cr-box">
            <h3>Certificado médico</h3>
            <p>Texto redactado por ti sobre la atención. El diagnóstico solo se incluye si el paciente lo autorizó. No es una incapacidad médica.</p>
            @if ($crMedical['ok'])
                <button type="button" class="cr-btn" onclick="document.getElementById('cr-dlg-medical').showModal()">Emitir certificado médico</button>
            @else
                <div class="cr-note">{{ $crMedical['message'] }}</div>
            @endif
        </div>
        
        @include('partner.incapacity.card', ['appointment' => $appointment, 'crDoctor' => $crDoctor, 'crIncap' => $crIncap])
    </div>

    @if ($crCertificates->isNotEmpty())
        <div class="cr-list">
            <div class="cr-list-title">Emitidos para esta cita ({{ $crCertificates->count() }})</div>
            @foreach ($crCertificates as $crc)
                <div class="cr-row">
                    <div>
                        <div class="cr-row-title">{{ $crc->typeLabel() }}<span class="cr-badge {{ $crc->isRevoked() ? 'cr-badge-off' : 'cr-badge-on' }}">{{ $crc->isRevoked() ? 'Anulado' : 'Vigente' }}</span></div>
                        <div class="cr-row-meta"><span class="cr-mono">{{ $crc->code }}</span> · Emitido {{ $crc->issued_at ? $crc->issued_at->copy()->setTimezone($crTz)->format('d/m/Y g:i A') : '' }}{{ $crc->isRevoked() && $crc->revoked_at ? ' · Anulado ' . $crc->revoked_at->copy()->setTimezone($crTz)->format('d/m/Y') : '' }}{{ !$crc->isRevoked() ? ($crc->emailed_at ? ' · Correo enviado al paciente' : ' · Correo aún no enviado') : '' }}</div>
                    </div>
                    <div class="cr-actions">
                        <a class="cr-link" target="_blank" rel="noopener" href="{{ route('partner.appointments.clinical-record.certificates.download', ['appointment' => $appointment->id, 'certificate' => $crc->id]) }}">Ver PDF</a>
                        @if (!$crc->isRevoked())
                            <form method="POST" action="{{ route('partner.appointments.clinical-record.certificates.resend', ['appointment' => $appointment->id, 'certificate' => $crc->id]) }}" style="margin:0;" onsubmit="crLock(this)">
                                @csrf
                                <button type="submit" class="cr-btn-ghost">{{ $crc->emailed_at ? 'Reenviar correo' : 'Enviar correo' }}</button>
                            </form>
                            <button type="button" class="cr-btn-ghost"
                                    data-url="{{ route('partner.appointments.clinical-record.certificates.revoke', ['appointment' => $appointment->id, 'certificate' => $crc->id]) }}"
                                    data-code="{{ $crc->code }}"
                                    onclick="crRevoke(this)">Anular</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if ($crAttendance['ok'])
<dialog id="cr-dlg-attendance" class="cr-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="{{ $crStoreUrl }}" class="cr-form" onsubmit="crLock(this)">
        @csrf
        <input type="hidden" name="type" value="attendance">
        <input type="hidden" name="cr_form" value="attendance">
        <h3>Emitir constancia de asistencia</h3>
        <p>La constancia dirá que <strong>{{ $crPatientName }}</strong> fue atendido(a) en consulta médica{{ $crWhen ? ' el ' . $crWhen->format('d/m/Y') . ' a las ' . $crWhen->format('H:i') : '' }}. No incluye diagnóstico.</p>
        @if (old('cr_form') === 'attendance')
            @if ($errors->any())
                <div class="cr-err">@foreach ($errors->all() as $crErr)<div>{{ $crErr }}</div>@endforeach</div>
            @elseif (session('error'))
                <div class="cr-err">{{ session('error') }}</div>
            @endif
        @endif
        <label class="cr-check"><input type="checkbox" name="declaration" value="1" required><span>{{ config('certificates.texts.declaration') }}</span></label>
        <div class="cr-form-actions">
            <button type="button" class="cr-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="cr-btn">Emitir constancia</button>
        </div>
    </form>
</dialog>
@endif

@if ($crMedical['ok'])
<dialog id="cr-dlg-medical" class="cr-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="{{ $crStoreUrl }}" class="cr-form" onsubmit="crLock(this)">
        @csrf
        <input type="hidden" name="type" value="medical">
        <input type="hidden" name="cr_form" value="medical">
        <h3>Emitir certificado médico</h3>
        <p>Paciente: <strong>{{ $crPatientName }}</strong>{{ $crWhen ? ' · Consulta del ' . $crWhen->format('d/m/Y') . ' a las ' . $crWhen->format('H:i') : '' }}</p>
        @if (old('cr_form') === 'medical')
            @if ($errors->any())
                <div class="cr-err">@foreach ($errors->all() as $crErr)<div>{{ $crErr }}</div>@endforeach</div>
            @elseif (session('error'))
                <div class="cr-err">{{ session('error') }}</div>
            @endif
        @endif
        <label class="cr-lab" for="cr-body">Contenido del certificado *</label>
        <textarea id="cr-body" name="body" rows="5" required minlength="10" maxlength="2000" placeholder="Describe lo que quieres certificar sobre la atención de esta consulta.">{{ old('body') }}</textarea>
        <div class="cr-warn">Este documento no es una incapacidad médica (reposo para el empleador o la EPS): esa tiene otro trámite.</div>

        <label class="cr-check"><input type="checkbox" name="include_diagnosis" value="1" {{ old('include_diagnosis') ? 'checked' : '' }}
               onchange="var b=document.getElementById('cr-diag'); b.style.display=this.checked?'flex':'none'; document.getElementById('cr-auth').required=this.checked; document.getElementById('cr-diagtext').required=this.checked;">
            <span>Incluir el diagnóstico en el certificado (solo si el paciente lo pidió o lo autorizó)</span></label>
        <div class="cr-diag" id="cr-diag" style="{{ old('include_diagnosis') ? 'display:flex' : '' }}">
            <label class="cr-lab" for="cr-diagtext">Diagnóstico *</label>
            <input type="text" id="cr-diagtext" name="diagnosis_text" maxlength="500" value="{{ old('diagnosis_text') }}" {{ old('include_diagnosis') ? 'required' : '' }}>
            <label class="cr-lab" for="cr-cie">Código CIE-10 (opcional)</label>
            <input type="text" id="cr-cie" name="cie10_code" maxlength="20" value="{{ old('cie10_code', $crHistoryCie) }}">
            <label class="cr-check"><input type="checkbox" id="cr-auth" name="patient_authorized_diagnosis" value="1" {{ old('patient_authorized_diagnosis') ? 'checked' : '' }} {{ old('include_diagnosis') ? 'required' : '' }}>
                <span>El paciente me autorizó expresamente incluir su diagnóstico en este certificado.</span></label>
        </div>

        <label class="cr-check"><input type="checkbox" name="declaration" value="1" required><span>{{ config('certificates.texts.declaration') }}</span></label>
        <div class="cr-form-actions">
            <button type="button" class="cr-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="cr-btn">Emitir certificado</button>
        </div>
    </form>
</dialog>
@endif

@include('partner.incapacity.dialog', ['appointment' => $appointment, 'crDoctor' => $crDoctor, 'crIncap' => $crIncap, 'crPatientName' => $crPatientName, 'crWhen' => $crWhen, 'crTz' => $crTz, 'crHistoryCie' => $crHistoryCie])

<dialog id="cr-dlg-revoke" class="cr-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="" id="cr-revoke-form" class="cr-form" onsubmit="crLock(this)">
        @csrf
        <h3>Anular certificado <span id="cr-revoke-code" class="cr-mono"></span></h3>
        <p>La verificación pública mostrará que fue anulado (sin el motivo). Esta acción no se puede deshacer; si necesitas otro, podrás emitir uno nuevo.</p>
        <label class="cr-lab" for="cr-reason">Motivo de la anulación *</label>
        <textarea id="cr-reason" name="reason" rows="3" required minlength="5" maxlength="255" placeholder="Ej: error en el contenido del certificado."></textarea>
        <div class="cr-form-actions">
            <button type="button" class="cr-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="cr-btn">Anular certificado</button>
        </div>
    </form>
</dialog>

<script>
    function crRevoke(btn) {
        document.getElementById('cr-revoke-form').action = btn.getAttribute('data-url');
        document.getElementById('cr-revoke-code').textContent = btn.getAttribute('data-code');
        document.getElementById('cr-reason').value = '';
        document.getElementById('cr-dlg-revoke').showModal();
    }
    function crLock(form) {
        var b = form.querySelector('button[type=submit]');
        if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    }
</script>
@if (in_array(old('cr_form'), ['attendance', 'medical'], true))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var d = document.getElementById('cr-dlg-{{ old('cr_form') }}');
        if (d && d.showModal) { d.showModal(); }
    });
</script>
@endif
@endif
