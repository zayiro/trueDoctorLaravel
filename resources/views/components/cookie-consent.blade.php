{{-- cookiesaviso: aviso de cookies. La decisión se guarda en la cookie od_consent (all | necessary, 12 meses).
     Google Analytics solo se carga con «all» (ver layouts/guest: window.odLoadGA). --}}
<style>
    .od-cc { position:fixed; left:16px; right:16px; bottom:16px; z-index:70; max-width:44rem; margin:0 auto; padding:16px 18px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 10px 30px rgba(15,23,42,.18); color:#334155; font-size:.875rem; line-height:1.5; }
    .od-cc[hidden] { display:none; }
    .od-cc p { margin:0 0 12px; }
    .od-cc a { color:#4f46e5; font-weight:600; text-decoration:underline; }
    .od-cc-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
    .od-cc-btn { border:0; border-radius:12px; padding:10px 16px; font-size:.875rem; font-weight:700; cursor:pointer; line-height:1.2; }
    .od-cc-btn-primary { background:#4f46e5; color:#fff; } .od-cc-btn-primary:hover { background:#4338ca; }
    .od-cc-btn-ghost { background:#f1f5f9; color:#334155; } .od-cc-btn-ghost:hover { background:#e2e8f0; }
    @media (max-width:480px) { .od-cc-actions { flex-direction:column-reverse; } .od-cc-btn { width:100%; } }
</style>

<div id="od-cc" class="od-cc" role="dialog" aria-live="polite" aria-label="Aviso de cookies" hidden>
    <p>
        Usamos cookies necesarias para que el sitio funcione y, si lo aceptas, cookies de analítica y de preferencias
        (por ejemplo, recordar la última especialidad que buscaste). Puedes cambiar tu decisión cuando quieras.
        <a href="{{ route('privacy.show') }}#cookies">Más información</a>.
    </p>
    <div class="od-cc-actions">
        <button type="button" class="od-cc-btn od-cc-btn-ghost" data-od-cc="necessary">Solo necesarias</button>
        <button type="button" class="od-cc-btn od-cc-btn-primary" data-od-cc="all">Aceptar</button>
    </div>
</div>

<script>
(function () {
    var CONSENT = 'od_consent', LAST = 'od_last_specialty';
    var box = document.getElementById('od-cc');
    if (!box) { return; }

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    function setCookie(name, value, days) {
        var s = name + '=' + encodeURIComponent(value) + '; Max-Age=' + (days * 86400) + '; Path=/; SameSite=Lax';
        if (location.protocol === 'https:') { s += '; Secure'; }
        document.cookie = s;
    }
    function delCookie(name, domain) {
        document.cookie = name + '=; Max-Age=0; Path=/; SameSite=Lax' + (domain ? '; Domain=' + domain : '');
    }
    // Borra las cookies de Google Analytics (_ga, _ga_XXXX) en el dominio y en su dominio padre.
    function deleteGaCookies() {
        var host = location.hostname, parts = host.split('.'), domains = [null, host, '.' + host];
        if (parts.length > 2) { domains.push('.' + parts.slice(-2).join('.')); }
        document.cookie.split('; ').forEach(function (c) {
            var n = c.split('=')[0];
            if (n === '_ga' || n.indexOf('_ga_') === 0 || n === '_gid') { domains.forEach(function (d) { delCookie(n, d); }); }
        });
        @if(env('GOOGLE_ANALYTICS_ID'))
        window['ga-disable-{{ env('GOOGLE_ANALYTICS_ID') }}'] = true;
        @endif
    }
    // Recuerda la última especialidad buscada (solo con consentimiento y solo el slug, validado).
    function rememberSpecialty() {
        if (getCookie(CONSENT) !== 'all' || location.pathname !== '/search') { return; }
        var slug = new URLSearchParams(location.search).get('specialty');
        if (slug && /^[a-z0-9-]{1,100}$/.test(slug)) { setCookie(LAST, slug, 30); }
    }
    function apply(choice) {
        setCookie(CONSENT, choice, 365);
        if (choice === 'all') {
            if (window.odLoadGA) { window.odLoadGA(); }
            rememberSpecialty();
        } else {
            delCookie(LAST);
            deleteGaCookies();
        }
        box.hidden = true;
    }

    box.querySelectorAll('[data-od-cc]').forEach(function (b) {
        b.addEventListener('click', function () { apply(b.getAttribute('data-od-cc')); });
    });
    // Enlace «Preferencias de cookies» del pie de página.
    window.odCookiePrefs = function () { box.hidden = false; };

    var current = getCookie(CONSENT);
    if (current === null) { box.hidden = false; }
    else if (current === 'all') { rememberSpecialty(); }
})();
</script>
