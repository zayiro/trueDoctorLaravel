{{-- Aviso en el dashboard del DOCTOR. Sale siempre que NO tenga verificación vigente en ReTHUS (sin solicitar, en revisión,
     no coincide o vencida) y, si está verificada, solo cuando está por vencer. Variable: $doctor.
     REVISAR CON ABOGADO: el texto de la incapacidad. --}}
@php
    $rdState = $doctor ? $doctor->rethusState() : 'none';
    $rdValid = $doctor ? $doctor->hasValidRethus() : false;
    $rdReason = (array) ($doctor ? (config('rethus.mismatch_reasons')[(string) $doctor->rethus_mismatch_reason] ?? []) : []);
    $rdIncap = ($doctor && !$rdValid) ? 'La incapacidad solo se podrá emitir si el doctor está «verificado en ReTHUS».' : null;
    $rdText = null;
    $rdCta = 'Ir a mi verificación';
    if ($doctor && $rdState === 'mismatch') {
        $rdText = ($rdReason['label'] ?? 'No pudimos verificar tu registro en ReTHUS.') . ' ' . ($rdReason['hint'] ?? '');
    } elseif ($doctor && $rdState === 'expired') {
        $rdText = 'Tu verificación en ReTHUS venció y tu perfil dejó de mostrar la insignia. Puedes solicitarla de nuevo.';
    } elseif ($doctor && $rdState === 'requested') {
        $rdText = 'Tu verificación en ReTHUS está en revisión. Te avisaremos por correo.';
        $rdCta = 'Ver estado';
    } elseif ($doctor && $rdState === 'none') {
        $rdText = 'Aún no has solicitado tu verificación en ReTHUS.';
    } elseif ($doctor && $doctor->rethusExpiresSoon()) {
        $rdText = 'Tu verificación en ReTHUS vence el ' . $doctor->rethus_expires_at->copy()->setTimezone('America/Bogota')->format('d/m/Y') . '. Renuévala para conservar la insignia.';
    }
@endphp
@if ($rdText || $rdIncap)
<style>
    .rtd { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 12px 12px 20px; padding: 14px 18px; border-radius: 18px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; font-weight: 700; line-height: 1.5; }
    .rtd a { color: #92400e; text-decoration: underline; font-weight: 900; white-space: nowrap; }
    .rtd strong { font-weight: 900; }
</style>
<div class="rtd" role="alert">
    <span>
        @if ($rdIncap)<strong>{{ $rdIncap }}</strong>@endif
        @if ($rdIncap && $rdText)<br>@endif
        @if ($rdText){{ $rdText }}@endif
    </span>
    <a href="{{ route('partner.rethus.show') }}">{{ $rdCta }} →</a>
</div>
@endif
