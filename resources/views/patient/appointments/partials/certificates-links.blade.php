{{-- Certificados emitidos para una cita (vista del paciente). Se incluye desde patient/appointments/index.blade.php.
     Variables: $appointment, $certificates (colección, puede venir vacía). --}}
@if ($certificates->isNotEmpty())
<style>
    .cp-wrap { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
    .cp-row { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .cp-link { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 12px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 800; text-decoration: none; }
    .cp-link:hover { background: #d1fae5; }
    .cp-off { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; font-size: 12px; font-weight: 800; text-decoration: line-through; }
    .cp-tag { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #9f1239; }
    .cp-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; color: #64748b; }
</style>
<div class="cp-wrap">
    @foreach ($certificates as $cpc)
        <div class="cp-row">
            @if ($cpc->isRevoked())
                <span class="cp-off">📄 {{ $cpc->typeLabel() }}</span><span class="cp-tag">Anulado</span>
            @else
                <a class="cp-link" href="{{ route('patient.certificates.download', ['certificate' => $cpc->id]) }}">📄 {{ $cpc->typeLabel() }} · Descargar PDF</a>
            @endif
            <span class="cp-code">{{ $cpc->code }}</span>
        </div>
    @endforeach
</div>
@endif
