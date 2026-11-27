{{-- Tarjeta «Incapacidad médica» del panel de certificados (se dibuja DENTRO de .cr-grid, por eso usa .cr-box).
     Siempre en gris, como deshabilitada, mientras el módulo de incapacidades no exista. Variable: $doctor (Doctor o null).
     El bloque de variables vive aquí a propósito: así no choca con la línea de una sola instrucción del panel.
     REVISAR CON ABOGADO: los textos. --}}
@php
    $icDoctor = ($doctor ?? null) instanceof \App\Models\Doctor ? $doctor : null;
    $icState = $icDoctor ? $icDoctor->rethusState() : 'none';
    $icUrl = ($icDoctor && \Illuminate\Support\Facades\Route::has('partner.rethus.show')) ? route('partner.rethus.show') : null;
@endphp
<div class="cr-box" style="grid-column:1 / -1;background:#f1f5f9;border-color:#e2e8f0;" aria-disabled="true">
    <h3 style="color:#94a3b8;">Incapacidad médica
        @if ($icState === 'verified')
            <span style="margin-left:6px;padding:2px 9px;border-radius:999px;background:#e2e8f0;color:#64748b;font-size:10px;font-weight:900;letter-spacing:.05em;">Próximamente</span>
        @elseif ($icState === 'requested')
            <span style="margin-left:6px;padding:2px 9px;border-radius:999px;background:#e2e8f0;color:#64748b;font-size:10px;font-weight:900;letter-spacing:.05em;">En revisión</span>
        @endif
    </h3>
    @if ($icState === 'verified')
        <p style="color:#94a3b8;">Tu registro está verificado en ReTHUS. Muy pronto podrás emitir incapacidades médicas desde aquí.</p>
    @elseif ($icState === 'requested')
        <p style="color:#94a3b8;">Tu verificación en ReTHUS está en revisión. Cuando esté lista podrás emitir incapacidades médicas.</p>
    @else
        <p style="color:#94a3b8;">Para emitir incapacidades médicas debes tener tu registro verificado en ReTHUS.
            @if ($icUrl)
                <a href="{{ $icUrl }}" style="color:#4f46e5;font-weight:800;text-decoration:underline;">Solicítalo aquí</a>
            @endif
        </p>
    @endif
    <span style="align-self:flex-start;padding:10px 18px;border-radius:12px;background:#e2e8f0;color:#94a3b8;font-size:11px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;cursor:not-allowed;">Emitir incapacidad</span>
</div>
