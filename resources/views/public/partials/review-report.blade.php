@php
    $rrUser = auth()->user();
    $rrMine = $rrUser && (int) $rrUser->id === (int) $review->user_id;
    $rrIsAdmin = $rrUser && $rrUser->role === 'admin';
    $rrDone = $rrUser && ! $rrMine && ! $rrIsAdmin
        && \App\Models\ReviewReport::query()->where('review_id', $review->id)->where('user_id', $rrUser->id)->exists();
@endphp

@once
    <style>
        .rrp-wrap { margin-top: .75rem; font-size: .75rem; }
        .rrp-link { background: none; border: 0; padding: 0; color: #94a3b8; cursor: pointer; text-decoration: underline; text-underline-offset: 2px; font-size: .75rem; }
        .rrp-link:hover { color: #475569; }
        .rrp-done { color: #94a3b8; }
        .rrp-form { margin-top: .5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .75rem; padding: .75rem; display: grid; gap: .5rem; max-width: 28rem; }
        .rrp-form label { font-weight: 600; color: #334155; }
        .rrp-form select, .rrp-form textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .4rem .5rem; font-size: .8rem; background: #fff; }
        .rrp-actions { display: flex; gap: .5rem; align-items: center; }
        .rrp-send { background: #e11d48; color: #fff; border: 0; border-radius: .5rem; padding: .4rem .8rem; font-weight: 700; cursor: pointer; font-size: .75rem; }
        .rrp-send:hover { background: #be123c; }
        .rrp-note { color: #64748b; line-height: 1.4; }
    </style>
@endonce

@if (! $rrMine && ! $rrIsAdmin)
    <div class="rrp-wrap" x-data="{ open: false }">
        @guest
            <a class="rrp-link" href="{{ route('login') }}" title="Inicia sesión para reportar esta reseña">Reportar</a>
        @else
            @if ($rrDone)
                <span class="rrp-done">Reporte enviado. Lo revisaremos pronto.</span>
            @else
                <button type="button" class="rrp-link" x-on:click="open = !open" x-bind:aria-expanded="open.toString()">Reportar esta reseña</button>
                <form method="POST" action="{{ route('reviews.report', $review) }}" class="rrp-form" x-show="open" x-cloak style="display:none">
                    @csrf
                    <label for="rrp-reason-{{ $review->id }}">¿Qué problema tiene?</label>
                    <select id="rrp-reason-{{ $review->id }}" name="reason" required>
                        <option value="">Elige un motivo…</option>
                        @foreach (\App\Models\ReviewReport::REASONS as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="rrp-details-{{ $review->id }}">Cuéntanos más (opcional)</label>
                    <textarea id="rrp-details-{{ $review->id }}" name="details" rows="2" maxlength="500"></textarea>
                    <p class="rrp-note">El equipo de OpenDoctor revisará la reseña. No se oculta automáticamente.</p>
                    <div class="rrp-actions">
                        <button type="submit" class="rrp-send">Enviar reporte</button>
                        <button type="button" class="rrp-link" x-on:click="open = false">Cancelar</button>
                    </div>
                </form>
            @endif
        @endguest
    </div>
@endif
