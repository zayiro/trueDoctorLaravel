@php
    $breadcrumbs = [
        ['name' => 'Doctor', 'href' => route('partner.addresses.index')],
        ['name' => 'Verificación ReTHUS'],
    ];
    $state = $doctor->rethusState();
    $tz = 'America/Bogota';
    $types = (array) config('rethus.document_types', []);
    $reason = (array) (config('rethus.mismatch_reasons')[(string) $doctor->rethus_mismatch_reason] ?? []);
    $missing = $doctor->rethusMissingForRequest();
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
<style>
    .rt-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 1px 2px rgba(15,23,42,.05); padding: 28px; }
    .rt-h1 { margin: 0; font-size: 22px; font-weight: 900; color: #0f172a; }
    .rt-p { margin: 8px 0 0; font-size: 14px; line-height: 1.6; color: #475569; }
    .rt-status { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
    .rt-st-none { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .rt-st-requested { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .rt-st-verified { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .rt-st-mismatch, .rt-st-expired { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .rt-box { margin-top: 18px; padding: 14px 16px; border-radius: 16px; font-size: 13px; line-height: 1.6; }
    .rt-box-info { background: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; }
    .rt-box-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .rt-box-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .rt-dl { margin: 18px 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; font-size: 13px; }
    @@media (max-width: 600px) { .rt-dl { grid-template-columns: 1fr; } }
    .rt-dl dt { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
    .rt-dl dd { margin: 2px 0 0; color: #1e293b; font-weight: 700; word-break: break-word; }
    .rt-btn { display: inline-block; margin-top: 18px; padding: 12px 22px; border: 0; border-radius: 14px; background: #4f46e5; color: #fff; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; cursor: pointer; text-decoration: none; }
    .rt-btn:hover { background: #4338ca; }
    .rt-link { color: #4f46e5; font-weight: 800; text-decoration: underline; }
    .rt-ok { margin-bottom: 16px; padding: 12px 16px; border-radius: 14px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 13px; font-weight: 700; }
    .rt-err { margin-bottom: 16px; padding: 12px 16px; border-radius: 14px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; font-size: 13px; font-weight: 700; }
    .rt-small { margin-top: 20px; font-size: 11px; line-height: 1.5; color: #94a3b8; }
</style>

<div>
    @if (session('success'))<div class="rt-ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="rt-err">{{ session('error') }}</div>@endif

    <div class="rt-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <h1 class="rt-h1">Verificación en ReTHUS</h1>
            <span class="rt-status rt-st-{{ $state }}">
                @if ($state === 'none') Sin verificar
                @elseif ($state === 'requested') En revisión
                @elseif ($state === 'verified') Verificado
                @elseif ($state === 'mismatch') No coincide
                @else Vencida @endif
            </span>
        </div>

        @if ($state === 'none')
            <p class="rt-p">{{ config('rethus.texts.incentive') }}</p>
        @elseif ($state === 'requested')
            <p class="rt-p">Recibimos tu solicitud{{ $doctor->rethus_requested_at ? ' el ' . $doctor->rethus_requested_at->copy()->setTimezone($tz)->format('d/m/Y') : '' }}. Verificamos tu registro en ReTHUS de forma manual y te avisaremos por correo cuando esté listo.</p>
        @elseif ($state === 'verified')
            <div class="rt-box rt-box-ok">Tu registro fue verificado{{ $doctor->rethus_verified_at ? ' el ' . $doctor->rethus_verified_at->copy()->setTimezone($tz)->format('d/m/Y') : '' }} y está vigente{{ $doctor->rethus_expires_at ? ' hasta el ' . $doctor->rethus_expires_at->copy()->setTimezone($tz)->format('d/m/Y') : '' }}. Tu perfil público muestra la insignia «{{ config('rethus.texts.badge') }}».</div>
            @if ($doctor->rethusExpiresSoon())
                <div class="rt-box rt-box-warn">Tu verificación vence pronto. Podrás solicitarla de nuevo cuando venza; te avisamos por correo.</div>
            @endif
        @elseif ($state === 'mismatch')
            <div class="rt-box rt-box-warn">
                <strong>{{ $reason['label'] ?? 'No pudimos verificar tu registro.' }}</strong><br>
                {{ $reason['hint'] ?? 'Revisa tus datos y vuelve a solicitar la verificación.' }}
            </div>
            <p class="rt-p">Mientras tanto sigues atendiendo con normalidad; solo que tu perfil no muestra la insignia.</p>
        @else
            <div class="rt-box rt-box-warn">Tu verificación venció{{ $doctor->rethus_expires_at ? ' el ' . $doctor->rethus_expires_at->copy()->setTimezone($tz)->format('d/m/Y') : '' }} y tu perfil dejó de mostrar la insignia. Puedes solicitarla de nuevo cuando quieras; sigues atendiendo con normalidad.</div>
        @endif

        <dl class="rt-dl">
            <div><dt>Tipo de documento</dt><dd>{{ $types[$doctor->identification_type] ?? ($doctor->identification_type ?: 'Sin completar') }}</dd></div>
            <div><dt>Número de documento</dt><dd>{{ $doctor->identification ?: 'Sin completar' }}</dd></div>
            <div><dt>Licencia médica</dt><dd>{{ $doctor->medical_license ?: 'Sin completar' }}</dd></div>
        </dl>

        @if ($doctor->canRequestRethus())
            <form method="POST" action="{{ route('partner.rethus.request') }}">
                @csrf
                <button type="submit" class="rt-btn">{{ $state === 'none' ? 'Solicitar verificación' : 'Volver a solicitar la verificación' }}</button>
            </form>
        @elseif ($missing)
            <div class="rt-box rt-box-info">{{ $missing }} <a class="rt-link" href="{{ route('partner.profile.edit') }}">Ir a mi perfil</a></div>
        @endif

        <p class="rt-small">{{ config('rethus.texts.disclaimer') }}</p>
    </div>
</div>
</x-admin-layout>
