@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Chat de Salud con IA'],
];

$inputBase = 'w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2';

$durations = [
    20 => ['ring' => 'focus:ring-cyan-500', 'border' => 'border-cyan-500'],
    30 => ['ring' => 'focus:ring-indigo-500', 'border' => 'border-indigo-500'],
];

$limits = [
    'ai_chat_max_messages_20' => ['Mensajes del paciente — sesión de 20 min', 'Tope de mensajes que puede enviar', 5, 200],
    'ai_chat_max_messages_30' => ['Mensajes del paciente — sesión de 30 min', 'Tope de mensajes que puede enviar', 5, 200],
    'ai_chat_max_chars' => ['Caracteres por mensaje', 'Largo máximo de cada mensaje del paciente', 100, 4000],
    'ai_chat_max_reply_words' => ['Palabras por respuesta de la IA', 'Largo aproximado de cada respuesta', 50, 1000],
    'ai_chat_offtopic_limit' => ['Mensajes fuera de tema', 'Cuántos antes del aviso firme (el reloj no se detiene)', 1, 10],
    'ai_chat_nudge_seconds' => ['Silencio antes del empujoncito (seg)', 'Una sola invitación amable si el paciente no escribe', 30, 600],
];

$rules = [
    'ai_chat_expiry_days' => ['Días para empezar la sesión', 'Pasado este plazo la sesión vence y no hay devolución', 1, 60],
    'ai_chat_retention_days' => ['Días que el paciente puede releer el chat', 'Después se anonimiza o se elimina', 7, 365],
    'ai_chat_id_max_attempts' => ['Intentos de identificación', 'Al agotarlos se bloquea y se reenvía el enlace', 3, 10],
];

$modelRoles = [
    'ai_chat_model_chat' => ['Modelo del chat', 'Responde al paciente durante la sesión'],
    'ai_chat_model_report' => ['Modelo del reporte final', 'Puede ser distinto (por ejemplo, uno más económico)'],
    'ai_chat_model_fallback' => ['Modelo de respaldo', 'Si el primero falla; conviene que sea de otro proveedor'],
];

$statusLabels = [
    'pago_pendiente' => 'Pago pendiente',
    'pendiente' => 'Pagadas, sin empezar',
    'activa' => 'En curso',
    'terminada' => 'Terminadas',
    'vencida' => 'Vencidas',
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <script>
        // Vista previa (estimada) de lo que paga el cliente: base + comisión de pasarela (% + fijo, IVA sobre la comisión).
        function priceCalc(b20, b30, pct, fixed, iva) {
            return {
                b20: b20, b30: b30, fixed: fixed, iva: iva, pct: pct,
                total(base) {
                    const f = 1 + Number(this.iva || 0) / 100;
                    const t = (Number(base || 0) + Number(this.fixed || 0) * f) / (1 - (Number(this.pct || 0) / 100) * f);
                    return Math.ceil(t / 100) * 100;
                },
                fmt(n) { return new Intl.NumberFormat('es-CO').format(n); }
            };
        }
    </script>

    <div class="space-y-8">

        <!-- ENCABEZADO -->
        <div class="space-y-2">
            <h1 class="text-3xl font-black">💬 Chat de Salud con IA</h1>
            <p class="text-slate-400">Precios, límites, modelos de IA y pausa de ventas del módulo «Chat de Salud con IA de OpenDoctor»</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 space-y-2">
                <p class="text-red-400 font-semibold">❌ Errores en la validación:</p>
                <ul class="text-red-300 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <p class="text-green-400 font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        <!-- RESUMEN DE SESIONES -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @foreach ($statusLabels as $status => $label)
                <div class="bg-slate-950 border border-white/10 rounded-xl p-4">
                    <p class="text-2xl font-black text-white">{{ (int) ($stats[$status] ?? 0) }}</p>
                    <p class="text-xs text-slate-400">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <form action="{{ route('administrator.ai-chat.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 1️⃣ VENTAS -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-power-off text-emerald-400"></i>
                    Ventas del chat
                </h2>
                <p class="text-slate-400 text-sm">Si las pausas, nadie puede comprar sesiones nuevas. Las ya pagadas siguen funcionando.</p>

                <div class="space-y-2 md:w-1/2">
                    <label for="ai_chat_sales_paused" class="text-sm font-bold text-white">Estado de las ventas</label>
                    <select id="ai_chat_sales_paused" name="ai_chat_sales_paused"
                            class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="0" @selected((string) old('ai_chat_sales_paused', $values['ai_chat_sales_paused']) === '0')>✅ Ventas activas</option>
                        <option value="1" @selected((string) old('ai_chat_sales_paused', $values['ai_chat_sales_paused']) === '1')>⏸️ Ventas pausadas</option>
                    </select>
                </div>
            </div>

            <!-- 2️⃣ PRECIOS -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4"
                 x-data="priceCalc({{ (int) old('ai_chat_price_20', $values['ai_chat_price_20']) }}, {{ (int) old('ai_chat_price_30', $values['ai_chat_price_30']) }}, {{ (float) old('ai_chat_wompi_percent', $values['ai_chat_wompi_percent']) }}, {{ (int) old('ai_chat_wompi_fixed_fee', $values['ai_chat_wompi_fixed_fee']) }}, {{ (int) old('ai_chat_wompi_iva_percent', $values['ai_chat_wompi_iva_percent']) }})">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-tag text-cyan-400"></i>
                    Precio de las sesiones
                </h2>
                <p class="text-slate-400 text-sm">Precio base en COP: lo que le queda a OpenDoctor. El cliente paga además la comisión de la pasarela.</p>

                <div class="grid md:grid-cols-2 gap-6">
                    @foreach ($durations as $minutes => $style)
                        @php $field = "ai_chat_price_{$minutes}"; @endphp
                        <div class="bg-slate-900 rounded-lg p-4 space-y-3 border-l-4 {{ $style['border'] }}">
                            <label for="{{ $field }}" class="text-sm font-bold text-white">Sesión de {{ $minutes }} minutos</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
                                <input type="number" id="{{ $field }}" name="{{ $field }}"
                                       x-model.number="b{{ $minutes }}"
                                       class="{{ $inputBase }} pl-10 {{ $style['ring'] }}"
                                       step="100" min="3000" max="500000" required>
                            </div>
                            <p class="text-xs text-slate-400">
                                El cliente pagaría aprox. <strong class="text-white" x-text="'$' + fmt(total(b{{ $minutes }})) + ' COP'"></strong>
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-cyan-500/10 border border-cyan-500/30 rounded-lg p-3">
                    <p class="text-slate-300 text-xs">
                        <strong>ℹ️</strong> El cambio aplica a las compras nuevas. Cada sesión conserva el precio con el que se empezó a pagar. El cobro real lo calcula el servidor al pagar.
                    </p>
                </div>
            </div>

            <!-- 3️⃣ MODELOS DE IA -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-purple-400"></i>
                    Modelos de IA del chat
                </h2>
                <p class="text-slate-400 text-sm">Las claves de API se quedan en el servidor; aquí solo eliges el modelo.</p>

                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($modelRoles as $field => $meta)
                        <div class="bg-slate-900 rounded-lg p-4 space-y-2 border-l-4 border-purple-500">
                            <label for="{{ $field }}" class="text-sm font-bold text-white">{{ $meta[0] }}</label>
                            <select id="{{ $field }}" name="{{ $field }}"
                                    class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                @foreach ($models as $value => $info)
                                    <option value="{{ $value }}" @selected((string) old($field, $values[$field]) === (string) $value)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-400">{{ $meta[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 4️⃣ LÍMITES -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-gauge-high text-amber-400"></i>
                    Límites de la conversación
                </h2>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($limits as $field => $meta)
                        <div class="space-y-1">
                            <label for="{{ $field }}" class="block text-sm font-semibold text-slate-300">{{ $meta[0] }}</label>
                            <input type="number" id="{{ $field }}" name="{{ $field }}"
                                   value="{{ old($field, $values[$field]) }}"
                                   class="{{ $inputBase }} focus:ring-amber-500"
                                   min="{{ $meta[2] }}" max="{{ $meta[3] }}" required>
                            <p class="text-xs text-slate-400">{{ $meta[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 5️⃣ VIGENCIA Y PRIVACIDAD -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-green-400"></i>
                    Vigencia y privacidad
                </h2>

                <div class="grid md:grid-cols-3 gap-4">
                    @foreach ($rules as $field => $meta)
                        <div class="space-y-1">
                            <label for="{{ $field }}" class="block text-sm font-semibold text-slate-300">{{ $meta[0] }}</label>
                            <input type="number" id="{{ $field }}" name="{{ $field }}"
                                   value="{{ old($field, $values[$field]) }}"
                                   class="{{ $inputBase }} focus:ring-green-500"
                                   min="{{ $meta[2] }}" max="{{ $meta[3] }}" required>
                            <p class="text-xs text-slate-400">{{ $meta[1] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                    <p class="text-slate-300 text-xs">
                        <strong>ℹ️</strong> Módulo en construcción: por ahora esta pantalla solo guarda la configuración; el chat todavía no es visible al público.
                    </p>
                </div>
            </div>

            <!-- BOTÓN GUARDAR -->
            <div class="flex gap-3 sticky bottom-4">
                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3.5 px-3 rounded-xl transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
