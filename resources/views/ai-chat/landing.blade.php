@php
    $money = fn (int $v) => '$' . number_format($v, 0, ',', '.');
    $minutesText = collect($plans)->pluck('minutes')->implode(' o ');
    $first = $plans[0] ?? null;

    $faq = [
        ['¿El Chat de Salud con IA es un médico?',
         'No. Es un asistente de inteligencia artificial que te escucha, te hace preguntas y te orienta. No diagnostica, no receta, no indica dosis ni interpreta exámenes, y no reemplaza la consulta con un médico. Si hay señales de urgencia, te lo dice y te indica buscar atención.'],
        ['¿Cómo funciona el tiempo de la sesión?',
         "Eliges una sesión de {$minutesText} minutos. El reloj solo empieza cuando pulsas «Empezar», no cuando pagas. Tienes {$expiryDays} días desde el pago para empezar; pasado ese plazo la sesión vence y no hay devolución."],
        ['¿Qué recibo al terminar?',
         'Un informe en pantalla y en tu correo con el resumen de lo conversado, posibles orientaciones (no es un diagnóstico), un plan de acción, señales de alarma para consultar de urgencia, preguntas para llevar a tu médico y la especialidad sugerida con un enlace para agendar una cita en OpenDoctor.'],
        ['¿Qué datos de mi salud usa la IA?',
         'Solo lo que tú escribes en el chat y, si lo autorizas al comprar, un perfil mínimo: edad, sexo, alergias, condiciones y medicamentos. Nunca se comparte tu número de documento, tu teléfono ni tu correo con la IA.'],
        ['¿Qué pasa si tengo una emergencia?',
         'El chat no es un servicio de emergencias. Si describes señales de urgencia o de riesgo, el asistente te pide acudir a urgencias o llamar a la línea 123 en Colombia, y tu informe lo marcará como urgente. En una emergencia, no esperes la respuesta de un chat.'],
        ['¿Cuánto tiempo se guarda mi conversación?',
         "Tu conversación y tu informe se conservan {$retentionDays} días después de terminar la sesión y luego se eliminan de forma definitiva. Puedes descargar tu conversación y guardar o imprimir tu informe antes de esa fecha."],
        ['¿En qué idiomas funciona?',
         'En español e inglés. Puedes cambiar de idioma durante la sesión y el asistente responde en el idioma que elijas.'],
        ['¿Puedo usarlo para un menor de edad?',
         'Sí, con un adulto responsable. El asistente es más prudente con los menores y siempre recomienda una valoración pediátrica presencial cuando corresponde.'],
        ['¿Cuánto cuesta y cómo se paga?',
         collect($plans)->map(fn ($p) => "{$p['minutes']} minutos: " . $money($p['total']) . ' COP')->implode('; ') . '. El precio ya incluye la comisión de la pasarela de pagos. El pago es seguro, a través de Wompi.'],
    ];

    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faq)->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ])->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

    $serviceSchema = json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => 'Chat de Salud con IA de OpenDoctor',
        'serviceType' => 'Orientación en salud con inteligencia artificial',
        'description' => 'Conversa con un asistente de IA sobre lo que sientes y recibe un informe con plan de acción y la especialidad médica sugerida. No reemplaza la consulta médica.',
        'provider' => ['@type' => 'Organization', 'name' => 'OpenDoctor', 'url' => 'https://opendoctor.online'],
        'areaServed' => ['@type' => 'Country', 'name' => 'Colombia'],
        'inLanguage' => ['es', 'en'],
        'url' => 'https://opendoctor.online/chat-salud-ia',
        'offers' => $paused ? null : collect($plans)->map(fn ($p) => [
            '@type' => 'Offer',
            'name' => "Sesión de {$p['minutes']} minutos",
            'price' => (string) $p['total'],
            'priceCurrency' => 'COP',
            'url' => route('ai-chat.buy'),
        ])->all(),
    ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

    $demo = [
        ['u', 'Hola, llevo dos días con dolor de cabeza y me cuesta concentrarme en el trabajo.'],
        ['a', 'Gracias por contármelo, y siento que estés incómodo. Para entenderlo mejor: ¿el dolor es en toda la cabeza o en una zona, y ha cambiado con el sueño o el estrés de estos días?'],
        ['u', 'Es como una presión en la frente y las sienes. He dormido poco por una entrega.'],
        ['a', 'Eso encaja con lo que suele llamarse cefalea tensional, aunque solo una valoración presencial puede confirmarlo. ¿Has notado náuseas, fiebre, cambios en la visión o un dolor súbito e intensísimo?'],
        ['u', 'No, nada de eso.'],
        ['a', 'Me alegra. En tu informe te dejaré pasos de autocuidado y señales de alarma para consultar de urgencia. ¿Hay algo más que quieras contarme antes de cerrar?'],
    ];
@endphp

<x-guest-layout
    meta_title="Chat de Salud con IA: orientación médica en minutos | OpenDoctor"
    :meta_description="'Conversa con un asistente de IA sobre lo que sientes y recibe un informe con plan de acción y la especialidad sugerida. Sesiones de '.$minutesText.' minutos.'"
    keywords="chat de salud con ia, asistente médico con inteligencia artificial, orientación médica en línea, consulta de salud con ia, chat médico ia, qué especialista necesito"
    meta_canonical="https://opendoctor.online/chat-salud-ia"
    :meta_skip_faq="true"
>
    <script type="application/ld+json">{!! $faqSchema !!}</script>
    <script type="application/ld+json">{!! $serviceSchema !!}</script>

    <style>
        [x-cloak] { display:none !important; }
        .cs-page { background:linear-gradient(180deg,#fff 0,#eff6ff 38%,#f8fafc 100%); color:#0f172a; }
        .cs-wrap { max-width:68rem; margin:0 auto; padding:0 18px; }
        .cs-section { padding:44px 0; }
        .cs-hero { padding:96px 0 40px; text-align:center; }
        .cs-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:.75rem; font-weight:700; }
        .cs-h1 { margin:16px auto 0; max-width:46rem; font-size:2rem; line-height:1.15; font-weight:900; letter-spacing:-.02em; }
        .cs-lead { margin:16px auto 0; max-width:40rem; font-size:1.0625rem; line-height:1.65; color:#475569; }
        .cs-h2 { margin:0 0 6px; font-size:1.5rem; font-weight:900; letter-spacing:-.01em; text-align:center; }
        .cs-sub { margin:0 auto 28px; max-width:36rem; text-align:center; color:#64748b; font-size:.9375rem; line-height:1.6; }
        .cs-btn { display:inline-block; padding:14px 30px; border-radius:14px; background:#2563eb; color:#fff !important; font-weight:800; font-size:1rem; text-decoration:none; box-shadow:0 8px 20px rgba(37,99,235,.25); border:0; cursor:pointer; }
        .cs-btn:hover { background:#1d4ed8; }
        .cs-btn-soft { background:#fff; color:#1d4ed8 !important; border:1px solid #bfdbfe; box-shadow:none; }
        .cs-note { margin-top:12px; font-size:.8125rem; color:#64748b; }
        .cs-grid3 { display:grid; grid-template-columns:1fr; gap:16px; }
        .cs-card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:22px; }
        .cs-step-n { display:inline-flex; width:34px; height:34px; align-items:center; justify-content:center; border-radius:999px; background:#2563eb; color:#fff; font-weight:900; margin-bottom:10px; }
        .cs-card h3 { margin:0 0 6px; font-size:1.0625rem; font-weight:800; }
        .cs-card p { margin:0; font-size:.9375rem; line-height:1.6; color:#475569; }
        .cs-chat { max-width:40rem; margin:0 auto; background:#fff; border:1px solid #e2e8f0; border-radius:24px; box-shadow:0 20px 40px rgba(15,23,42,.08); overflow:hidden; }
        .cs-chat-head { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.8125rem; font-weight:700; color:#334155; }
        .cs-chat-body { padding:18px; display:flex; flex-direction:column; gap:10px; min-height:20rem; }
        .cs-msg { max-width:86%; padding:10px 14px; border-radius:16px; font-size:.9375rem; line-height:1.55; }
        .cs-msg-u { align-self:flex-end; background:#2563eb; color:#fff; border-bottom-right-radius:4px; }
        .cs-msg-a { align-self:flex-start; background:#f1f5f9; color:#1e293b; border-bottom-left-radius:4px; }
        .cs-report { margin:6px 18px 18px; padding:16px; border-radius:16px; background:#eef2ff; border:1px solid #c7d2fe; }
        .cs-report h4 { margin:0 0 8px; font-size:.875rem; font-weight:800; color:#3730a3; }
        .cs-report ul { margin:0; padding-left:18px; font-size:.875rem; line-height:1.6; color:#312e81; }
        .cs-tag { display:inline-block; margin-bottom:8px; padding:2px 10px; border-radius:999px; background:#fef3c7; color:#b45309; font-size:.75rem; font-weight:800; }
        .cs-example { text-align:center; margin-top:10px; font-size:.75rem; color:#94a3b8; }
        .cs-plans { display:grid; grid-template-columns:1fr; gap:16px; max-width:44rem; margin:0 auto; }
        .cs-plan { text-align:center; }
        .cs-price { margin:8px 0 2px; font-size:2rem; font-weight:900; color:#0f172a; }
        .cs-plan small { color:#64748b; }
        .cs-two { display:grid; grid-template-columns:1fr; gap:16px; max-width:52rem; margin:0 auto; }
        .cs-list { margin:8px 0 0; padding-left:18px; font-size:.9375rem; line-height:1.7; color:#475569; }
        .cs-faq { max-width:46rem; margin:0 auto; display:flex; flex-direction:column; gap:10px; }
        .cs-faq details { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:14px 18px; }
        .cs-faq summary { cursor:pointer; font-weight:800; font-size:1rem; }
        .cs-faq p { margin:10px 0 0; font-size:.9375rem; line-height:1.65; color:#475569; }
        .cs-cta { max-width:40rem; margin:0 auto; text-align:center; background:#fff; border:1px solid #e2e8f0; border-radius:28px; padding:30px 22px; box-shadow:0 20px 40px rgba(15,23,42,.06); }
        .cs-form { display:flex; flex-direction:column; gap:10px; margin-top:16px; }
        .cs-input { width:100%; padding:13px 16px; border:1px solid #cbd5e1; border-radius:12px; font-size:1rem; }
        .cs-ok { margin-top:14px; padding:12px 14px; border-radius:12px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:.9375rem; }
        .cs-err { margin-top:14px; padding:12px 14px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; color:#b45309; font-size:.9375rem; }
        .cs-hp { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
        .cs-disclaimer { max-width:46rem; margin:0 auto; padding:16px 18px; border-radius:16px; background:#fffbeb; border:1px solid #fef3c7; color:#92400e; font-size:.8125rem; line-height:1.6; }
        .cs-links { text-align:center; font-size:.9375rem; color:#64748b; padding-bottom:48px; }
        .cs-links a { color:#2563eb; font-weight:700; }
        @media (min-width:768px) {
            .cs-h1 { font-size:3rem; }
            .cs-grid3 { grid-template-columns:repeat(3,1fr); }
            .cs-plans, .cs-two { grid-template-columns:repeat(2,1fr); }
            .cs-form { flex-direction:row; }
            .cs-form .cs-btn { white-space:nowrap; }
        }
    </style>

    <div class="cs-page">

        {{-- Presentación --}}
        <section class="cs-hero">
            <div class="cs-wrap">
                <span class="cs-badge">💬 Chat de Salud con IA de OpenDoctor</span>
                <h1 class="cs-h1">Cuéntale a un asistente Médico IA lo que sientes y sal con un plan claro</h1>
                <p class="cs-lead">Conversa de forma privada sobre tus síntomas o inquietudes de salud. Al terminar recibes un informe con tu plan de acción, las señales de alarma a vigilar y la especialidad que conviene consultar.</p>
                <div style="margin-top:26px;">
                    @if ($paused)
                        <a href="#aviso" class="cs-btn">Avísame cuando esté disponible</a>
                        <p class="cs-note">Muy pronto abrimos las primeras sesiones.</p>
                    @else
                        <a href="{{ route('ai-chat.buy') }}" class="cs-btn">Comprar mi sesión</a>
                        @if ($first) <p class="cs-note">Desde {{ $money(collect($plans)->min('total')) }} COP · el tiempo empieza cuando tú pulsas «Empezar»</p> @endif
                    @endif
                </div>
            </div>
        </section>

        {{-- Cómo funciona --}}
        <section class="cs-section">
            <div class="cs-wrap">
                <h2 class="cs-h2">Así funciona</h2>
                <p class="cs-sub">Tres pasos, sin filas y sin esperar una cita para empezar a entender lo que te pasa.</p>
                <div class="cs-grid3">
                    <div class="cs-card"><span class="cs-step-n">1</span><h3>Elige tu sesión</h3><p>De {{ $minutesText }} minutos. Pagas de forma segura y recibes por correo el enlace personal de tu sesión.</p></div>
                    <div class="cs-card"><span class="cs-step-n">2</span><h3>Conversa cuando quieras</h3><p>Tienes {{ $expiryDays }} días para empezar. El reloj solo corre cuando pulsas «Empezar», así que eliges un momento tranquilo.</p></div>
                    <div class="cs-card"><span class="cs-step-n">3</span><h3>Recibe tu informe</h3><p>Resumen, plan de acción, señales de alarma y la especialidad sugerida, con un enlace para agendar con un médico.</p></div>
                </div>
            </div>
        </section>

        {{-- Demo --}}
        <section class="cs-section" id="ejemplo">
            <div class="cs-wrap">
                <h2 class="cs-h2">Mira cómo se siente una conversación</h2>
                <p class="cs-sub">El asistente escucha, hace una o dos preguntas por turno y cuida el tono. Esto es un ejemplo ilustrativo.</p>

                <div class="cs-chat" x-data="{ n: {{ count($demo) + 1 }}, total: {{ count($demo) }}, run() {
                        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        this.n = 0;
                        const go = () => { if (this.n <= this.total) { this.n++; setTimeout(go, 1700); } };
                        if (!('IntersectionObserver' in window)) { go(); return; }
                        const io = new IntersectionObserver((e) => { if (e[0].isIntersecting) { io.disconnect(); go(); } }, { threshold: 0.35 });
                        io.observe(this.$el);
                    } }" x-init="run()">
                    <div class="cs-chat-head"><span>🩺 Chat de Salud con IA</span><span style="color:#94a3b8;">Sesión de ejemplo</span></div>
                    <div class="cs-chat-body">
                        @foreach ($demo as $i => $m)
                            <div class="cs-msg {{ $m[0] === 'u' ? 'cs-msg-u' : 'cs-msg-a' }}" x-show="n > {{ $i }}" x-transition.opacity.duration.400ms>{{ $m[1] }}</div>
                        @endforeach
                    </div>
                    <div class="cs-report" x-show="n > total" x-transition.opacity.duration.500ms>
                        <span class="cs-tag">Ejemplo de informe</span>
                        <h4>Tu informe de salud</h4>
                        <ul>
                            <li><strong>Nivel de atención sugerido:</strong> medio, conviene una valoración próximamente.</li>
                            <li>Prioriza dormir y haz pausas durante el día; anota cuándo aparece el dolor.</li>
                            <li>Consulta con un médico general si dura más de 3 días o aparece alguna señal de alarma.</li>
                            <li><strong>Especialidad sugerida:</strong> Medicina general, con enlace para agendar.</li>
                        </ul>
                    </div>
                </div>
                <p class="cs-example">Conversación y resultado de ejemplo escritos por nosotros. Tu sesión será distinta, según lo que cuentes.</p>
            </div>
        </section>

        {{-- Qué es y qué no es + privacidad --}}
        <section class="cs-section">
            <div class="cs-wrap">
                <h2 class="cs-h2">Claro desde el principio</h2>
                <p class="cs-sub">Una orientación útil, con límites honestos.</p>
                <div class="cs-two">
                    <div class="cs-card">
                        <h3>Lo que sí hace</h3>
                        <ul class="cs-list">
                            <li>Te escucha y te ayuda a ordenar lo que sientes.</li>
                            <li>Te orienta sobre posibles causas, sin dar diagnósticos.</li>
                            <li>Te deja un plan de acción y señales de alarma.</li>
                            <li>Te sugiere con qué especialista consultar.</li>
                        </ul>
                    </div>
                    <div class="cs-card">
                        <h3>Lo que no hace</h3>
                        <ul class="cs-list">
                            <li>No es un médico ni reemplaza una consulta.</li>
                            <li>No receta, no indica dosis ni interpreta exámenes.</li>
                            <li>No atiende emergencias: si hay urgencia te pide acudir a urgencias o llamar al 123.</li>
                        </ul>
                    </div>
                    <div class="cs-card">
                        <h3>Tu privacidad</h3>
                        <ul class="cs-list">
                            <li>Tu enlace es personal y pide tu identificación para entrar.</li>
                            <li>Usar tu perfil médico es opcional y tú lo autorizas.</li>
                            <li>Tu conversación y tu informe se eliminan {{ $retentionDays }} días después de terminar.</li>
                        </ul>
                    </div>
                    <div class="cs-card">
                        <h3>Pensado para ti</h3>
                        <ul class="cs-list">
                            <li>En español e inglés.</li>
                            <li>Con un adulto responsable, también para menores.</li>
                            <li>Informe por correo y en pantalla, listo para llevar a tu médico.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Precios --}}
        <section class="cs-section" id="precios">
            <div class="cs-wrap">
                <h2 class="cs-h2">Elige el tiempo que necesitas</h2>
                <p class="cs-sub">Precio final en pesos colombianos, con la comisión de la pasarela de pagos incluida.</p>
                <div class="cs-plans">
                    @foreach ($plans as $p)
                        <div class="cs-card cs-plan">
                            <h3>Sesión de {{ $p['minutes'] }} minutos</h3>
                            <div class="cs-price">{{ $money($p['total']) }}</div>
                            <small>COP · hasta {{ $p['messages'] }} mensajes tuyos</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Preguntas frecuentes --}}
        <section class="cs-section" id="preguntas">
            <div class="cs-wrap">
                <h2 class="cs-h2">Preguntas frecuentes</h2>
                <p class="cs-sub">Lo que más nos preguntan antes de empezar.</p>
                <div class="cs-faq">
                    @foreach ($faq as $f)
                        <details><summary>{{ $f[0] }}</summary><p>{{ $f[1] }}</p></details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Llamado final: compra o lista de espera --}}
        <section class="cs-section" id="aviso">
            <div class="cs-wrap">
                <div class="cs-cta">
                    @if ($paused)
                        <h2 class="cs-h2" style="margin-bottom:8px;">Muy pronto disponible</h2>
                        <p class="cs-sub" style="margin-bottom:0;">Déjanos tu correo y te avisamos una sola vez cuando abramos las primeras sesiones.</p>

                        @if (session('waitlist_ok'))
                            <div class="cs-ok">¡Listo! Te avisaremos a tu correo cuando esté disponible. Gracias por tu interés.</div>
                        @else
                            @if (session('waitlist_error'))<div class="cs-err">{{ session('waitlist_error') }}</div>@endif
                            <form method="POST" action="{{ route('ai-chat.waitlist') }}" class="cs-form">
                                @csrf
                                <div class="cs-hp" aria-hidden="true"><label>Sitio web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                                <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" placeholder="tucorreo@ejemplo.com" autocomplete="email" class="cs-input" aria-label="Tu correo electrónico">
                                <button type="submit" class="cs-btn">Avísame</button>
                            </form>
                            <p class="cs-note">Solo usamos tu correo para este aviso.</p>
                        @endif
                    @else
                        <h2 class="cs-h2" style="margin-bottom:8px;">¿Listo para empezar?</h2>
                        <p class="cs-sub" style="margin-bottom:18px;">Elige tu sesión, paga de forma segura y empieza cuando quieras.</p>
                        <a href="{{ route('ai-chat.buy') }}" class="cs-btn">Comprar mi sesión</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="cs-section" style="padding-top:0;">
            <div class="cs-wrap">
                <p class="cs-disclaimer"><strong>Importante:</strong> el Chat de Salud con IA es un asistente de inteligencia artificial de carácter informativo y educativo. No es un médico, no establece diagnósticos, no formula medicamentos y no reemplaza la valoración de un profesional de la salud. En una emergencia acude a urgencias o llama a la línea 123 (Colombia).</p>
            </div>
        </section>

        <p class="cs-links">¿Prefieres hablar con un médico? <a href="{{ route('doctors.landing') }}">Encuentra doctores cerca de ti</a> y agenda tu cita en línea.</p>
    </div>
</x-guest-layout>
