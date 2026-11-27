<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Reseñas reportadas'],
]">
    <style>
        .rr-wrap { max-width: 64rem; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
        .rr-wrap * { box-sizing: border-box; }
        .rr-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
        .rr-sub { font-size: .875rem; color: #64748b; margin: .25rem 0 0; }
        .rr-tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
        .rr-tab { padding: .45rem .9rem; border-radius: 999px; border: 1px solid #e2e8f0; background: #f8fafc; color: #334155; font-size: .85rem; font-weight: 700; text-decoration: none; }
        .rr-tab.on { background: #4f46e5; color: #fff; border-color: transparent; }
        .rr-flash { padding: .7rem 1rem; border-radius: .75rem; font-size: .875rem; }
        .rr-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .rr-bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .rr-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem 1.25rem; min-width: 0; }
        .rr-meta { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; font-size: .8rem; color: #64748b; }
        .rr-meta b { color: #0f172a; }
        .rr-stars { color: #f59e0b; letter-spacing: 1px; font-size: 1rem; }
        .rr-text { background: #f8fafc; border-left: 4px solid #cbd5e1; border-radius: .5rem; padding: .75rem 1rem; margin: .75rem 0; color: #1e293b; font-size: .95rem; white-space: pre-line; overflow-wrap: anywhere; }
        .rr-text.empty { color: #94a3b8; font-style: italic; }
        .rr-h { font-size: .7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin: .75rem 0 .35rem; }
        .rr-rep { border: 1px solid #f1f5f9; border-radius: .75rem; padding: .55rem .8rem; margin-bottom: .4rem; font-size: .85rem; color: #334155; overflow-wrap: anywhere; }
        .rr-rep.pro { background: #fffbeb; border-color: #fde68a; }
        .rr-chip { display: inline-block; font-size: .7rem; font-weight: 800; padding: .1rem .5rem; border-radius: 999px; background: #eef2ff; color: #4338ca; margin-left: .35rem; }
        .rr-chip.warn { background: #fef3c7; color: #92400e; }
        .rr-chip.hid { background: #fee2e2; color: #991b1b; }
        .rr-chip.keep { background: #dcfce7; color: #166534; }
        .rr-form { margin-top: .9rem; border-top: 1px dashed #e2e8f0; padding-top: .9rem; display: grid; gap: .6rem; }
        .rr-radio { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .9rem; color: #1e293b; }
        .rr-radio label { display: flex; gap: .4rem; align-items: center; cursor: pointer; }
        .rr-form textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: .6rem; padding: .5rem .7rem; font-size: .85rem; }
        .rr-btn { justify-self: start; background: #4f46e5; color: #fff; border: 0; font-weight: 700; font-size: .85rem; padding: .55rem 1.1rem; border-radius: .6rem; cursor: pointer; }
        .rr-btn:hover { background: #4338ca; }
        .rr-btn.alt { background: #0f766e; }
        .rr-empty { text-align: center; color: #64748b; padding: 2rem 1rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 1rem; }
    </style>

    @php
        $stars = fn ($n) => str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n);
        $roleLabels = ['doctor' => 'Doctor', 'clinic' => 'Clínica', 'patient' => 'Paciente'];
    @endphp

    <div class="rr-wrap">
        <div>
            <h1 class="rr-title">🚩 Reseñas reportadas</h1>
            <p class="rr-sub">Nada se oculta solo: tú decides si una reseña reportada se mantiene o se oculta del sitio. Ocultarla la saca del perfil, de la calificación y de la portada.</p>
        </div>

        <div class="rr-tabs">
            <a class="rr-tab {{ $tab === 'pending' ? 'on' : '' }}" href="{{ route('administrator.review-reports.index') }}">Por revisar ({{ $pending->count() }})</a>
            <a class="rr-tab {{ $tab === 'resolved' ? 'on' : '' }}" href="{{ route('administrator.review-reports.index', ['tab' => 'resolved']) }}">Resueltos</a>
        </div>

        @if (session('success'))
            <div class="rr-flash rr-ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rr-flash rr-bad">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rr-flash rr-bad">{{ $errors->first() }}</div>
        @endif

        @if ($tab === 'pending')
            @forelse ($pending as $reviewId => $reports)
                @php
                    $rv = $reports->first()->review;
                    $target = $rv?->reviewable;
                    $targetName = $target?->user?->name ?? 'perfil';
                    $isClinic = $target instanceof \App\Models\Clinic;
                @endphp
                @if ($rv)
                    <div class="rr-card">
                        <div class="rr-meta">
                            <span>Reseña sobre {{ $isClinic ? 'la clínica' : 'el profesional' }} <b>{{ $targetName }}</b></span>
                            <span>Escrita por <b>{{ $rv->user?->name ?? 'Paciente' }}</b> · {{ $rv->created_at?->timezone('America/Bogota')->format('d/m/Y') }}</span>
                        </div>
                        <div class="rr-stars" aria-label="{{ $rv->rating }} de 5 estrellas">{{ $stars($rv->rating) }}</div>
                        @if ($rv->comment)
                            <div class="rr-text">{{ $rv->comment }}</div>
                        @else
                            <div class="rr-text empty">Solo calificación, sin comentario.</div>
                        @endif

                        <div class="rr-h">{{ $reports->count() }} {{ $reports->count() === 1 ? 'reporte' : 'reportes' }}</div>
                        @foreach ($reports as $rep)
                            @php $byPro = $target && (int) $rep->user_id === (int) $target->user_id; @endphp
                            <div class="rr-rep {{ $byPro ? 'pro' : '' }}">
                                <b>{{ $rep->reasonLabel() }}</b>
                                @if ($byPro)
                                    <span class="rr-chip warn">Lo reporta {{ $isClinic ? 'la clínica reseñada' : 'el profesional reseñado' }}</span>
                                @endif
                                <br>
                                Por {{ $rep->reporter?->name ?? 'cuenta eliminada' }}
                                @if ($rep->reporter)<span class="rr-chip">{{ $roleLabels[$rep->reporter->role] ?? $rep->reporter->role }}</span>@endif
                                · {{ $rep->created_at?->timezone('America/Bogota')->format('d/m/Y H:i') }}
                                @if ($rep->details)<br><em>«{{ $rep->details }}»</em>@endif
                            </div>
                        @endforeach

                        <form class="rr-form" method="POST" action="{{ route('administrator.review-reports.resolve', $rv) }}"
                              onsubmit="return this.decision.value !== 'hide' || confirm('¿Ocultar esta reseña del sitio? Dejará de contar en la calificación y de verse en el perfil y la portada.')">
                            @csrf
                            <div class="rr-radio">
                                <label><input type="radio" name="decision" value="keep" required> Mantener la reseña (los reportes se cierran)</label>
                                <label><input type="radio" name="decision" value="hide" required> Ocultarla del sitio</label>
                            </div>
                            <textarea name="note" rows="2" maxlength="500" placeholder="Nota interna (opcional): por qué tomaste esta decisión"></textarea>
                            <button type="submit" class="rr-btn">Guardar decisión</button>
                        </form>
                    </div>
                @endif
            @empty
                <div class="rr-empty">No hay reseñas reportadas por revisar. 🎉</div>
            @endforelse
        @else
            @forelse ($resolved as $reviewId => $reports)
                @php
                    $first = $reports->first();
                    $rv = $first->review;
                    $target = $rv?->reviewable;
                    $targetName = $target?->user?->name ?? 'perfil';
                    $labels = ['dismissed' => ['Se mantuvo', 'keep'], 'upheld' => ['Se ocultó', 'hid'], 'reversed' => ['Se ocultó y se restauró', 'keep']];
                    [$decLabel, $decClass] = $labels[$first->status] ?? ['Resuelto', ''];
                @endphp
                @if ($rv)
                    <div class="rr-card">
                        <div class="rr-meta">
                            <span>Sobre <b>{{ $targetName }}</b> · escrita por <b>{{ $rv->user?->name ?? 'Paciente' }}</b></span>
                            <span><span class="rr-chip {{ $decClass }}">{{ $decLabel }}</span> {{ $first->resolved_at?->timezone('America/Bogota')->format('d/m/Y H:i') }} · {{ $first->resolver?->name ?? 'administrador' }}</span>
                        </div>
                        <div class="rr-stars">{{ $stars($rv->rating) }}</div>
                        <div class="rr-text {{ $rv->comment ? '' : 'empty' }}">{{ $rv->comment ?: 'Solo calificación, sin comentario.' }}</div>
                        <div class="rr-h">Motivos reportados</div>
                        @foreach ($reports as $rep)
                            <div class="rr-rep">{{ $rep->reasonLabel() }} · {{ $rep->reporter?->name ?? 'cuenta eliminada' }}@if ($rep->details) — <em>«{{ $rep->details }}»</em>@endif</div>
                        @endforeach
                        @if ($first->resolution_note)
                            <div class="rr-h">Tu nota</div>
                            <div class="rr-rep">{{ $first->resolution_note }}</div>
                        @endif
                        @if ($rv->status === \App\Models\Review::STATUS_HIDDEN)
                            <form class="rr-form" method="POST" action="{{ route('administrator.review-reports.restore', $rv) }}" onsubmit="return confirm('¿Volver a publicar esta reseña?')">
                                @csrf
                                <button type="submit" class="rr-btn alt">Restaurar reseña</button>
                            </form>
                        @endif
                    </div>
                @endif
            @empty
                <div class="rr-empty">Todavía no hay reportes resueltos.</div>
            @endforelse
        @endif
    </div>
</x-admin-layout>
