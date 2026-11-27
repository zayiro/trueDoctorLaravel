@php
    $breadcrumbs = [
        ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
        ['name' => 'Validación de Documentos', 'href' => route('administrator.validation.index')],
        ['name' => 'Verificación ReTHUS'],
    ];
    $tz = 'America/Bogota';
    $types = (array) config('rethus.document_types', []);
    $tabs = ['pending' => 'Pendientes', 'expiring' => 'Vencen pronto', 'expired' => 'Vencidas', 'mismatch' => 'No coinciden', 'verified' => 'Vigentes', 'all' => 'Todas'];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
<style>
    .ra-wrap { max-width: 1180px; margin: 0 auto; padding: 28px 16px; }
    .ra-h1 { margin: 0; font-size: 22px; font-weight: 900; color: #0f172a; }
    .ra-sub { margin: 6px 0 0; font-size: 13px; color: #64748b; line-height: 1.5; }
    .ra-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin: 20px 0 14px; }
    .ra-tab { padding: 8px 14px; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12px; font-weight: 800; text-decoration: none; }
    .ra-tab.on { background: #4f46e5; border-color: #4f46e5; color: #fff; }
    .ra-n { margin-left: 6px; font-weight: 900; opacity: .8; }
    .ra-search { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
    .ra-search input { flex: 1; min-width: 220px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; }
    .ra-btn { padding: 9px 16px; border: 0; border-radius: 12px; background: #4f46e5; color: #fff; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; cursor: pointer; }
    .ra-btn-ghost { padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff; color: #334155; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; cursor: pointer; text-decoration: none; display: inline-block; }
    .ra-btn-warn { padding: 8px 14px; border: 1px solid #fde68a; border-radius: 12px; background: #fffbeb; color: #92400e; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; cursor: pointer; }
    .ra-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; }
    .ra-row { display: grid; grid-template-columns: 1.4fr 1.2fr 1fr 1.4fr; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; align-items: start; font-size: 13px; color: #334155; }
    .ra-row:last-child { border-bottom: 0; }
    @@media (max-width: 900px) { .ra-row { grid-template-columns: 1fr; } }
    .ra-name { font-weight: 900; color: #0f172a; font-size: 14px; }
    .ra-muted { color: #64748b; font-size: 12px; }
    .ra-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 800; color: #0f172a; }
    .ra-copy { margin-left: 6px; padding: 1px 8px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; font-size: 10px; font-weight: 900; color: #475569; cursor: pointer; }
    .ra-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
    .ra-b-requested { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .ra-b-verified { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .ra-b-mismatch, .ra-b-expired { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .ra-b-none { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .ra-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .ra-empty { padding: 40px 20px; text-align: center; color: #94a3b8; font-size: 13px; font-weight: 700; }
    .ra-ok { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 13px; font-weight: 700; }
    .ra-err { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; font-size: 13px; font-weight: 700; }
    .ra-dlg { border: 0; padding: 0; border-radius: 22px; width: 92%; max-width: 520px; box-shadow: 0 25px 60px rgba(15,23,42,.35); }
    .ra-dlg::backdrop { background: rgba(15,23,42,.55); }
    .ra-form { padding: 24px; display: flex; flex-direction: column; gap: 12px; }
    .ra-form h3 { margin: 0; font-size: 18px; font-weight: 900; color: #0f172a; }
    .ra-form p { margin: 0; font-size: 13px; color: #475569; line-height: 1.55; }
    .ra-form label { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; color: #334155; }
    .ra-form input, .ra-form textarea, .ra-form select { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-size: 14px; font-family: inherit; }
    .ra-form-actions { display: flex; justify-content: flex-end; gap: 10px; }
</style>

<div class="ra-wrap">
    <h1 class="ra-h1">Verificación ReTHUS de doctores</h1>
    <p class="ra-sub">La consulta pública de ReTHUS pide un captcha, así que se verifica a mano: abre la consulta, escribe el tipo y el número de documento del doctor, y confirma lo que ves (que aparece activo y su profesión). Vigencia: {{ (int) config('rethus.validity_months', 12) }} meses.</p>

    @if (session('success'))<div class="ra-ok" style="margin-top:16px;">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="ra-err" style="margin-top:16px;">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="ra-err" style="margin-top:16px;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="ra-tabs">
        @foreach ($tabs as $key => $label)
            <a class="ra-tab {{ $filter === $key ? 'on' : '' }}" href="{{ route('administrator.rethus.index', ['f' => $key, 'q' => $q ?: null]) }}">{{ $label }}@if (isset($counts[$key]))<span class="ra-n">{{ $counts[$key] }}</span>@endif</a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('administrator.rethus.index') }}" class="ra-search">
        <input type="hidden" name="f" value="{{ $filter }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre, correo, documento o licencia">
        <button type="submit" class="ra-btn">Buscar</button>
    </form>

    <div class="ra-card">
        @forelse ($doctors as $d)
            @php
                $st = $d->rethusState();
                $stLabel = ['none' => 'Sin verificar', 'requested' => 'En revisión', 'verified' => 'Verificado', 'mismatch' => 'No coincide', 'expired' => 'Vencida'][$st] ?? $st;
            @endphp
            <div class="ra-row">
                <div>
                    <div class="ra-name">Dr(a). {{ $d->user?->name ?? '—' }}</div>
                    <div class="ra-muted">{{ $d->user?->email }}</div>
                    <div class="ra-muted">{{ $d->specialties->pluck('name')->take(2)->implode(', ') }}</div>
                    <div class="ra-muted">Cuenta: {{ $d->validation_status }}</div>
                </div>
                <div>
                    <div class="ra-muted">Tipo de documento</div>
                    <div class="ra-mono">{{ $d->identification_type ? ($d->identification_type . ' · ' . ($types[$d->identification_type] ?? '')) : 'Sin completar' }}</div>
                    <div class="ra-muted" style="margin-top:6px;">Número</div>
                    <div><span class="ra-mono">{{ $d->identification }}</span><button type="button" class="ra-copy" onclick="raCopy('{{ $d->identification }}', this)">Copiar</button></div>
                    <div class="ra-muted" style="margin-top:6px;">Licencia</div>
                    <div class="ra-mono">{{ $d->medical_license ?: '—' }}</div>
                </div>
                <div>
                    <span class="ra-badge ra-b-{{ $st }}">{{ $stLabel }}</span>
                    @if ($d->rethus_requested_at && $st === 'requested')<div class="ra-muted" style="margin-top:6px;">Solicitada {{ $d->rethus_requested_at->copy()->setTimezone($tz)->format('d/m/Y') }}</div>@endif
                    @if ($d->rethus_verified_at)<div class="ra-muted" style="margin-top:6px;">Verificada {{ $d->rethus_verified_at->copy()->setTimezone($tz)->format('d/m/Y') }}</div>@endif
                    @if ($d->rethus_expires_at)<div class="ra-muted">Vence {{ $d->rethus_expires_at->copy()->setTimezone($tz)->format('d/m/Y') }}</div>@endif
                    @if ($d->rethus_confirmed_text)<div class="ra-muted" style="margin-top:6px;">Confirmado: {{ $d->rethus_confirmed_text }}</div>@endif
                    @if ($d->rethus_mismatch_reason)<div class="ra-muted" style="margin-top:6px;">Motivo: {{ config('rethus.mismatch_reasons.' . $d->rethus_mismatch_reason . '.label') }}</div>@endif
                    @if ($d->rethus_note)<div class="ra-muted" style="margin-top:6px;">Nota: {{ $d->rethus_note }}</div>@endif
                </div>
                <div class="ra-actions">
                    <a class="ra-btn-ghost" target="_blank" rel="noopener" href="{{ config('rethus.lookup_url') }}">Abrir ReTHUS ↗</a>
                    <button type="button" class="ra-btn" onclick="raOpen('verify', {{ $d->id }}, @js($d->user?->name ?? ''))">{{ $st === 'verified' ? 'Reverificar' : 'Verificado' }}</button>
                    <button type="button" class="ra-btn-warn" onclick="raOpen('mismatch', {{ $d->id }}, @js($d->user?->name ?? ''))">No coincide</button>
                </div>
            </div>
        @empty
            <div class="ra-empty">No hay doctores en esta lista.</div>
        @endforelse
    </div>

    <div style="margin-top:18px;">{{ $doctors->links() }}</div>
</div>

<dialog id="ra-dlg-verify" class="ra-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="" id="ra-form-verify" class="ra-form">
        @csrf
        <h3>Marcar como verificado</h3>
        <p>Doctor: <strong id="ra-name-verify"></strong>. Confirma que en ReTHUS aparece con registro activo.</p>
        <label for="ra-confirmed">Qué confirmaste en ReTHUS *</label>
        <input type="text" id="ra-confirmed" name="confirmed_text" required minlength="3" maxlength="255" placeholder="Ej: Médico — Activo">
        <label for="ra-note-v">Nota interna (opcional, el doctor no la ve)</label>
        <textarea id="ra-note-v" name="note" rows="2" maxlength="1000"></textarea>
        <div class="ra-form-actions">
            <button type="button" class="ra-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="ra-btn">Verificar</button>
        </div>
    </form>
</dialog>

<dialog id="ra-dlg-mismatch" class="ra-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="" id="ra-form-mismatch" class="ra-form">
        @csrf
        <h3>Marcar como «no coincide»</h3>
        <p>Doctor: <strong id="ra-name-mismatch"></strong>. Se le envía un correo con el motivo y se oculta su insignia.</p>
        <label for="ra-reason">Motivo *</label>
        <select id="ra-reason" name="reason" required>
            <option value="">Selecciona…</option>
            @foreach ((array) config('rethus.mismatch_reasons', []) as $code => $r)
                <option value="{{ $code }}">{{ $r['label'] }}</option>
            @endforeach
        </select>
        <label for="ra-note-m">Nota interna (opcional, el doctor no la ve)</label>
        <textarea id="ra-note-m" name="note" rows="2" maxlength="1000"></textarea>
        <div class="ra-form-actions">
            <button type="button" class="ra-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="ra-btn-warn">Marcar no coincide</button>
        </div>
    </form>
</dialog>

<script>
    var RA_URLS = {
        verify: '{{ route('administrator.rethus.verify', ['doctor' => '__ID__']) }}',
        mismatch: '{{ route('administrator.rethus.mismatch', ['doctor' => '__ID__']) }}'
    };
    function raOpen(kind, id, name) {
        document.getElementById('ra-form-' + kind).action = RA_URLS[kind].replace('__ID__', id);
        document.getElementById('ra-name-' + kind).textContent = name;
        document.getElementById('ra-dlg-' + kind).showModal();
    }
    function raCopy(text, btn) {
        var done = function () { var o = btn.textContent; btn.textContent = 'Copiado'; setTimeout(function () { btn.textContent = o; }, 1200); };
        if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(done); }
    }
</script>
</x-admin-layout>
