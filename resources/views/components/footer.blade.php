<footer class="od-footer">
    <style>
        .od-footer { background:#fff; border-top:1px solid #f1f5f9; margin-top:auto; padding:40px 0 24px; }
        .od-footer * { box-sizing:border-box; }
        .od-footer-wrap { max-width:80rem; margin:0 auto; padding:0 16px; }
        .od-footer-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:32px 24px; }
        .od-footer-brand { grid-column:1 / -1; }
        .od-footer-logo { font-size:1.25rem; font-weight:900; letter-spacing:-0.02em; color:#0f172a; }
        .od-footer-logo .d { color:#4f46e5; } .od-footer-logo .o { color:#10b981; }
        .od-footer-tag { margin:8px 0 14px; font-size:.8125rem; line-height:1.5; color:#94a3b8; max-width:22rem; }
        .od-footer-status { display:inline-flex; align-items:center; gap:6px; padding:2px 10px; border-radius:999px; font-size:.75rem; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #d1fae5; }
        .od-footer-dot { width:6px; height:6px; border-radius:999px; background:#10b981; animation:od-pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
        @keyframes od-pulse { 50% { opacity:.4; } }
        .od-footer-social { display:flex; gap:10px; margin-top:14px; }
        .od-footer-social a { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:999px; background:#f1f5f9; color:#64748b; transition:background .15s, color .15s, transform .15s; }
        .od-footer-social a:hover { background:#4f46e5; color:#fff; transform:translateY(-1px); }
        .od-footer-social a:focus-visible { outline:2px solid #4f46e5; outline-offset:2px; }
        .od-footer-title { margin:0 0 12px; font-size:.6875rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#0f172a; }
        .od-footer-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
        .od-footer-list a { font-size:.875rem; font-weight:500; color:#64748b; text-decoration:none; transition:color .15s; }
        .od-footer-list a:hover { color:#4f46e5; }
        .od-footer-new { margin-left:6px; padding:1px 7px; border-radius:999px; font-size:.625rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; background:#eef2ff; color:#4f46e5; vertical-align:middle; }
        .od-footer-bottom { margin-top:32px; padding-top:20px; border-top:1px solid #f1f5f9; display:flex; flex-direction:column; align-items:center; gap:4px; text-align:center; font-size:.75rem; color:#94a3b8; }
        .od-footer-bottom a { font-weight:600; color:#94a3b8; text-decoration:none; } .od-footer-bottom a:hover { color:#4f46e5; }
        @media (min-width:768px) {
            .od-footer-grid { grid-template-columns:minmax(0,1.6fr) repeat(3, minmax(0,1fr)); gap:32px; }
            .od-footer-brand { grid-column:auto; }
            .od-footer-bottom { flex-direction:row; justify-content:space-between; }
        }
        @media (min-width:1024px) { .od-footer-wrap { padding:0 32px; } }
    </style>

    <div class="od-footer-wrap">
        <div class="od-footer-grid">

            <!-- Marca -->
            <div class="od-footer-brand">
                <div class="od-footer-logo">Open<span class="d">Doctor</span><span class="o">Online</span></div>
                <p class="od-footer-tag">Consulta médica en línea y agenda tus citas con doctores verificados.</p>
                <span class="od-footer-status"><span class="od-footer-dot"></span>SaaS Activo</span>
                @if (collect(config('social.links', []))->contains(fn ($l) => !empty($l['url'])))
                    <div class="od-footer-social">
                        @foreach (config('social.links', []) as $name => $link)
                            @if (!empty($link['url']))
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $link['label'] ?? ucfirst($name) }}" title="{{ $link['label'] ?? ucfirst($name) }}"><x-social-icon :name="$name" /></a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Plataforma -->
            <nav aria-label="Plataforma">
                <h3 class="od-footer-title">Plataforma</h3>
                <ul class="od-footer-list">
                    <li><a href="{{ route('doctors.landing') }}">Doctores cerca de mí</a></li>
                    <li><a href="{{ route('ai-chat.landing') }}">Chat de Salud IA<span class="od-footer-new">Nuevo</span></a></li>
                    <li><a href="{{ route('plans.index') }}">Planes</a></li>
                </ul>
            </nav>

            <!-- Ayuda -->
            <nav aria-label="Ayuda">
                <h3 class="od-footer-title">Ayuda</h3>
                <ul class="od-footer-list">
                    <li><a href="{{ route('contact.show') }}">Contáctenos</a></li>
                    <li><a href="{{ route('support.show') }}">Soporte Técnico</a></li>
                    <li><a href="{{ route('about.show') }}">¿Quiénes somos?</a></li>
                </ul>
            </nav>

            <!-- Legal -->
            <nav aria-label="Legal">
                <h3 class="od-footer-title">Legal</h3>
                <ul class="od-footer-list">
                    <li><a href="{{ route('terms.show') }}">Términos de Servicio</a></li>
                    <li><a href="{{ route('privacy.show') }}">Privacidad y Tratamiento de Datos</a></li>
                    <li><a href="#" onclick="if (window.odCookiePrefs) { window.odCookiePrefs(); } return false;">Preferencias de cookies</a></li>
                    <li><a href="https://web.sispro.gov.co/THS/Cliente/ConsultasPublicas/ConsultaPublicaDeTHxIdentificacion.aspx" target="_blank" rel="noopener">ReTHUS</a></li>
                </ul>
            </nav>
        </div>

        <div class="od-footer-bottom">
            <span>© {{ date('Y') }} OpenDoctorOnline. Todos los derechos reservados.</span>
            <span>Desarrollado por <a href="https://opendoctor.online">opendoctor.online</a></span>
        </div>
    </div>
</footer>
