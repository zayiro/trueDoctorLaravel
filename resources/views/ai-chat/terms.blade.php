@php
    // BORRADOR para revisión de abogado. Los plazos salen de los ajustes del admin.
    $e = fn ($v) => e((string) $v);

    $secs = [
        ['id' => 'servicio', 'title' => 'Qué es el Chat de Salud IA', 'p' => [
            'El Chat de Salud IA de OpenDoctor es un servicio de <strong>orientación con inteligencia artificial</strong>. Conversas con un asistente sobre lo que sientes y, al terminar, recibes un informe con un resumen, un plan de acción y la especialidad médica que podría convenirte.',
            'Es una herramienta informativa. <strong>No es una consulta médica, no emite diagnósticos, no receta medicamentos y no reemplaza a un profesional de la salud.</strong>',
        ]],
        ['id' => 'emergencias', 'title' => 'Emergencias', 'p' => [
            'Si tú o alguien cerca presenta una urgencia (dolor intenso en el pecho, dificultad para respirar, pérdida de conciencia, sangrado abundante, pensamientos de hacerte daño u otra situación grave), <strong>no uses este chat</strong>: llama a la línea de emergencias 123 o acude de inmediato al servicio de urgencias más cercano.',
        ]],
        ['id' => 'sesion', 'title' => 'Tu sesión', 'p' => [
            'Al pagar recibes una sesión personal con un tiempo máximo de conversación (20 o 30 minutos, según lo que elijas) y un número máximo de mensajes. <strong>El tiempo empieza a correr cuando pulsas «Empezar».</strong>',
            'Tienes <strong>' . $expiryDays . ' días</strong> desde la compra para empezar la sesión. Pasado ese plazo la sesión vence.',
            'El enlace de tu sesión es personal. Quien lo tenga puede intentar entrar, por eso te pedimos validar tu identificación antes de ver tu conversación y tu informe. No lo compartas.',
        ]],
        ['id' => 'pagos', 'title' => 'Pagos y devoluciones', 'p' => [
            'El pago se procesa con Wompi. El valor que ves antes de pagar incluye el servicio y la comisión de la pasarela de pagos.',
            // REVISAR CON ABOGADO: política de no reembolso, derecho de retracto (Ley 1480 de 2011) y excepciones para servicios ya iniciados.
            'Si la sesión vence sin haberse iniciado, o si la inicias y la terminas, <strong>no hay devolución del dinero</strong>. Si un fallo técnico atribuible a OpenDoctor te impide recibir el servicio, escríbenos y lo resolvemos: repetir la sesión o devolverte el valor, según el caso.',
        ]],
        ['id' => 'datos', 'title' => 'Tus datos y la inteligencia artificial', 'p' => [
            'Para generar las respuestas y el informe, el contenido de tu conversación se envía a <strong>proveedores externos de modelos de inteligencia artificial</strong>. Lo que escribas en el chat llega a ese proveedor; por eso <strong>no escribas datos que no quieras compartir</strong>, como tu nombre completo, tu documento o tu dirección.',
            'OpenDoctor no envía a la IA tu nombre registrado ni tu número de identificación. Si los escribes tú en la conversación, sí llegarían.',
            'El acceso del personal de OpenDoctor a tu conversación se limita a fines estrictamente técnicos y de soporte (por ejemplo, resolver una falla del servicio o atender una solicitud tuya), y solo cuando es necesario.',
            'Si lo autorizas al comprar, también usamos información opcional de tu perfil para que la orientación sea más precisa. Es voluntario y puedes no marcarlo.',
            // REVISAR CON ABOGADO: transferencia internacional de datos (Ley 1581 de 2012) y encargados del tratamiento.
            'Tratamos tus datos personales conforme a nuestra <a href="' . route('privacy.show') . '" class="text-teal-600 underline">Política de Privacidad</a> y a la Ley 1581 de 2012. Los datos de salud son datos sensibles: solo los usamos para prestarte este servicio.',
        ]],
        ['id' => 'conservacion', 'title' => 'Cuánto tiempo se guarda tu conversación', 'p' => [
            // REVISAR CON ABOGADO: si la conversación califica como historia clínica y qué retención exige la normativa.
            'Por seguridad y por la normativa que rige la información de salud, tu conversación y tu informe se conservan <strong>' . $retentionDays . ' días</strong> después de terminar la sesión y luego se eliminan de forma definitiva.',
            'Puedes descargar la conversación en un archivo de texto desde tu informe y, si lo marcas al comprar, recibir una copia por correo. <strong>Guarda tu copia antes de esa fecha</strong>: después no podremos recuperarla.',
        ]],
        ['id' => 'uso', 'title' => 'Uso adecuado', 'p' => [
            // REVISAR CON ABOGADO: edad mínima y manejo de menores de edad.
            'El servicio es para personas mayores de 18 años que usan sus propios datos. Si lo usas para otra persona, hazlo con su conocimiento.',
            'No uses el chat para pedir contenido ilegal, para suplantar a otra persona ni para intentar manipular o vulnerar el sistema. La IA responde solo sobre temas de salud; los mensajes fuera de ese tema pueden limitarse o cerrar la sesión.',
        ]],
        ['id' => 'responsabilidad', 'title' => 'Límites y responsabilidad', 'p' => [
            'La inteligencia artificial puede equivocarse, omitir información o no entender tu situación completa. El informe es una guía general; <strong>las decisiones sobre tu salud deben tomarse con un profesional que te valore</strong>.',
            // REVISAR CON ABOGADO: alcance de la limitación de responsabilidad.
            'OpenDoctor no se hace responsable por decisiones tomadas únicamente con base en el contenido del chat o del informe, ni por demoras en buscar atención médica. Cuando el informe sugiera una especialidad, puedes agendar con los profesionales de la plataforma, sin ninguna obligación de hacerlo.',
        ]],
        ['id' => 'cambios', 'title' => 'Cambios y contacto', 'p' => [
            'Podemos actualizar estos términos; la versión vigente es la publicada en esta página. Los cambios no afectan sesiones ya compradas.',
            // REVISAR CON ABOGADO: ley aplicable y jurisdicción.
            'Estos términos se rigen por las leyes de la República de Colombia.',
            $supportEmail !== ''
                ? 'Para dudas, solicitudes sobre tus datos o problemas con tu sesión escríbenos a <a href="mailto:' . $e($supportEmail) . '" class="text-teal-600 underline">' . $e($supportEmail) . '</a>.'
                : 'Para dudas, solicitudes sobre tus datos o problemas con tu sesión, contáctanos desde la plataforma de OpenDoctor.',
        ]],
    ];
@endphp
<x-guest-layout
    meta_title="Términos del Chat de Salud con IA | OpenDoctor"
    meta_description="Condiciones del servicio Chat de Salud con IA de OpenDoctor: qué es, cómo funciona tu sesión, pagos, datos, conservación de la conversación y límites."
    meta_canonical="https://opendoctor.online/chat-salud-ia/terminos"
>
    <main class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8">
        <article class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

            <header class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Chat de Salud IA
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    Términos del Chat de Salud con IA
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Última actualización: 23 de noviembre de 2026
                </p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">
                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Navegación rápida</p>
                    <nav class="space-y-2 sticky top-24">
                        @foreach ($secs as $i => $s)
                            <a href="#{{ $s['id'] }}" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                                {{ $i + 1 }}. {{ $s['title'] }}
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <section class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    <div class="text-slate-700 space-y-10 text-base sm:text-lg leading-relaxed">
                        <p class="text-slate-500 font-medium pb-4 border-b border-slate-100">
                            Antes de comprar tu sesión, lee estas condiciones. Al marcar «Acepto los términos» confirmas que las entendiste.
                        </p>

                        @foreach ($secs as $i => $s)
                            <section id="{{ $s['id'] }}" class="scroll-mt-28">
                                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                    <span class="text-teal-600 me-2">{{ $i + 1 }}.</span> {{ $s['title'] }}
                                </h2>
                                <div class="space-y-3 text-slate-600">
                                    @foreach ($s['p'] as $para)
                                        <p>{!! $para !!}</p>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach

                        <div class="pt-6 border-t border-slate-100">
                            <a href="{{ route('ai-chat.landing') }}" class="text-teal-600 font-semibold underline">Volver al Chat de Salud IA</a>
                        </div>
                    </div>
                </section>
            </div>
        </article>
    </main>
</x-guest-layout>
