{{-- Franja corta del estado ReTHUS, arriba del perfil del doctor. Variable: $doctor. --}}
@php
    $rtState = $doctor->rethusState();
    $rtTz = 'America/Bogota';
    $rtMap = [
        'none'      => ['cls' => 'rtb-info',  'text' => 'Verifica tu registro en ReTHUS y muestra a tus pacientes la insignia «' . config('rethus.texts.badge') . '».', 'cta' => 'Ver cómo'],
        'requested' => ['cls' => 'rtb-info',  'text' => 'Tu verificación en ReTHUS está en revisión. Te avisaremos por correo.', 'cta' => 'Ver estado'],
        'verified'  => ['cls' => 'rtb-ok',    'text' => 'Registro verificado en ReTHUS' . ($doctor->rethus_expires_at ? ' · vigente hasta el ' . $doctor->rethus_expires_at->copy()->setTimezone($rtTz)->format('d/m/Y') : '') . '.', 'cta' => 'Ver detalle'],
        'mismatch'  => ['cls' => 'rtb-warn',  'text' => 'No pudimos verificar tu registro en ReTHUS. Revisa el motivo y vuelve a solicitarla.', 'cta' => 'Ver motivo'],
        'expired'   => ['cls' => 'rtb-warn',  'text' => 'Tu verificación en ReTHUS venció. Puedes solicitarla de nuevo.', 'cta' => 'Renovar'],
    ];
    $rt = $rtMap[$rtState] ?? $rtMap['none'];
@endphp
<style>
    .rtb { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; padding: 12px 18px; border-radius: 16px; font-size: 13px; font-weight: 700; }
    .rtb-info { background: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; }
    .rtb-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .rtb-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .rtb a { color: inherit; text-decoration: underline; font-weight: 900; white-space: nowrap; }
</style>
<div class="rtb {{ $rt['cls'] }}">
    <span>{{ $rt['text'] }}</span>
    <a href="{{ route('partner.rethus.show') }}">{{ $rt['cta'] }} →</a>
</div>
