{{-- Tarjeta «Incapacidad médica» del panel de certificados (se dibuja DENTRO de .cr-grid, por eso usa .cr-box).
     Variables: $appointment, $crDoctor, $crIncap (resultado de eligibility). REVISAR CON ABOGADO: los textos. --}}
@php
    $icOk = (bool) ($crIncap['ok'] ?? false);
    $icReason = (string) ($crIncap['reason'] ?? '');
    $icMessage = (string) ($crIncap['message'] ?? '');
    $icLink = null;
    $icLinkText = '';
    if ($icReason === 'rethus' && \Illuminate\Support\Facades\Route::has('partner.rethus.show')) {
        $icLink = route('partner.rethus.show');
        $icLinkText = 'Ir a mi verificación';
    } elseif ($icReason === 'habilitation' && \Illuminate\Support\Facades\Route::has('partner.profile.edit')) {
        $icLink = route('partner.profile.edit');
        $icLinkText = 'Ir a mi perfil';
    }
@endphp
<div class="cr-box" style="grid-column:1 / -1;">
    <h3>Incapacidad médica</h3>
    <p>Reposo para el empleador o la EPS, con fechas, duración y origen (común o laboral), código de verificación y código QR. Lleva el código CIE-10 del diagnóstico, no su descripción.</p>
    @if ($icOk)
        <button type="button" class="cr-btn" onclick="document.getElementById('cr-dlg-incapacity').showModal()">Emitir incapacidad</button>
    @else
        <div class="cr-note">
            {{ $icMessage !== '' ? $icMessage : 'Por ahora no se puede emitir una incapacidad para esta cita.' }}
            @if ($icLink)
                <a href="{{ $icLink }}" style="color:#4f46e5;font-weight:800;text-decoration:underline;">{{ $icLinkText }} →</a>
            @endif
        </div>
    @endif
</div>
