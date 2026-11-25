{{-- cookiesaviso: acceso rápido a la última especialidad buscada.
     Solo aparece si la persona aceptó las cookies (od_consent=all) y el slug guardado es una especialidad activa. --}}
@php
    // Cookies puestas desde el navegador (no cifradas por Laravel): se leen directo y el slug se valida contra la base.
    $odConsent = (string) ($_COOKIE['od_consent'] ?? '');
    $odSlug = (string) ($_COOKIE['od_last_specialty'] ?? '');
    $odSpecialty = null;
    if ($odConsent === 'all' && preg_match('/^[a-z0-9-]{1,100}$/', $odSlug)) {
        $odSpecialty = \App\Models\Specialty::where('slug', $odSlug)->where('status', true)->first();
    }
@endphp

@if($odSpecialty)
    <style>
        .ls-wrap { position:relative; z-index:40; background:#fff; padding:24px 16px 0; }
        .ls-card { max-width:80rem; margin:0 auto; padding:14px 18px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:16px; color:#312e81; font-size:.9375rem; }
        .ls-card p { margin:0; }
        .ls-actions { display:flex; align-items:center; gap:8px; }
        .ls-btn { display:inline-block; padding:10px 16px; border-radius:12px; background:#4f46e5; color:#fff; font-weight:700; font-size:.875rem; text-decoration:none; }
        .ls-btn:hover { background:#4338ca; color:#fff; }
        .ls-close { border:0; background:transparent; color:#6366f1; font-size:.8125rem; font-weight:600; cursor:pointer; text-decoration:underline; padding:8px; }
    </style>
    <div class="ls-wrap" id="od-last-specialty" role="status">
        <div class="ls-card">
            <p>¿Sigues buscando <strong>{{ $odSpecialty->name }}</strong>?</p>
            <div class="ls-actions">
                <a class="ls-btn" href="{{ url('/search') }}?specialty={{ $odSpecialty->slug }}">Ver doctores de {{ $odSpecialty->name }}</a>
                <button type="button" class="ls-close" id="od-last-specialty-close">No mostrar</button>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('od-last-specialty-close').addEventListener('click', function () {
            document.cookie = 'od_last_specialty=; Max-Age=0; Path=/; SameSite=Lax';
            document.getElementById('od-last-specialty').remove();
        });
    </script>
@endif
