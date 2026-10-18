@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Configuraciones'],
];

// Estilos de cada grupo de modelos de IA (las clases viven aquí, en la vista, para que Tailwind las encuentre)
$aiGroups = [
    'lab' => ['title' => '🧪 LABORATORIO', 'border' => 'border-blue-500', 'ring' => 'focus:ring-blue-500'],
    'imaging' => ['title' => '🔬 IMAGENOLOGÍA', 'border' => 'border-purple-500', 'ring' => 'focus:ring-purple-500'],
];

$aiRoles = [
    'primary' => ['label' => 'Modelo Primario', 'hint' => 'Se intenta primero'],
    'fallback' => ['label' => 'Modelo Fallback', 'hint' => 'Si el primero falla'],
];

$inputBase = 'w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2';
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="space-y-8">

        <!-- ENCABEZADO -->
        <div class="space-y-2">
            <h1 class="text-3xl font-black">⚙️ Configuración del Sistema</h1>
            <p class="text-slate-400">Modifica las variables operativas y comerciales globales del sistema</p>
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

        <form action="{{ route('administrator.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 1️⃣ PRECIOS DE EXÁMENES -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-tags text-green-400"></i>
                    Precios de Exámenes por Tipo
                </h2>
                <p class="text-slate-400 text-sm">Configurar precio fijo para cada tipo de examen médico</p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($examTypes as $type => $label)
                        @php $field = "exam_type_{$type}_price"; @endphp
                        <div class="space-y-2">
                            <label for="{{ $field }}" class="text-sm font-bold text-white">{{ $label }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
                                <input type="number"
                                       id="{{ $field }}"
                                       name="{{ $field }}"
                                       value="{{ old($field, $settings->get($field, 0)) }}"
                                       class="{{ $inputBase }} pl-10 focus:ring-green-500"
                                       step="1000"
                                       min="5000"
                                       required>
                            </div>
                            <p class="text-xs text-slate-400">
                                Actual: ${{ number_format($settings->get($field, 0), 0, ',', '.') }} COP
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
                    <p class="text-slate-300 text-xs">
                        <strong>ℹ️</strong> Estos precios son FIJOS y no varían según cantidad de imágenes.
                    </p>
                </div>
            </div>

            <!-- 2️⃣ MODELOS DE IA POR TIPO -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-pink-400"></i>
                    Modelos de IA por Tipo de Examen
                </h2>
                <p class="text-slate-400 text-sm">Selecciona qué modelo de IA usar primero para cada tipo</p>

                <div class="grid md:grid-cols-2 gap-6">
                    @foreach ($aiGroups as $group => $style)
                        <div class="bg-slate-900 rounded-lg p-4 space-y-3 border-l-4 {{ $style['border'] }}">
                            <p class="text-sm font-bold text-white">{{ $style['title'] }}</p>

                            @foreach ($aiRoles as $role => $meta)
                                @php $field = "{$group}_ai_{$role}_model"; @endphp
                                <div class="space-y-2">
                                    <label for="{{ $field }}" class="text-xs font-bold text-white">{{ $meta['label'] }}</label>
                                    <select id="{{ $field }}"
                                            name="{{ $field }}"
                                            class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 {{ $style['ring'] }}">
                                        @foreach ($aiModels as $value => $name)
                                            <option value="{{ $value }}" @selected(old($field, $settings->get($field)) === $value)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-slate-400">{{ $meta['hint'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-3">
                    <p class="text-slate-300 text-xs">
                        <strong>ℹ️</strong> Los cambios se aplican inmediatamente a nuevos análisis.
                    </p>
                </div>
            </div>

            <!-- 3️⃣ FACTORES DE DECIMACIÓN -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-purple-400"></i>
                    Factores de Decimación Automática
                </h2>
                <p class="text-slate-400 text-sm">Procesa cada N-ésima imagen (1 = todas, 10 = cada 10ª)</p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($decimationTypes as $type => $label)
                        @php
                            $field = "{$type}_auto_decimation";
                            $decimation = (int) $settings->get($field, 1);
                        @endphp
                        <div class="space-y-2">
                            <label for="{{ $field }}" class="text-sm font-bold text-white">{{ $label }}</label>
                            <input type="number"
                                   id="{{ $field }}"
                                   name="{{ $field }}"
                                   value="{{ old($field, $decimation) }}"
                                   class="{{ $inputBase }} focus:ring-purple-500"
                                   min="1"
                                   max="100"
                                   required>
                            <p class="text-xs text-slate-400">
                                {{ $decimation === 1 ? 'Procesa todas las imágenes' : "Procesa cada {$decimation}ª imagen" }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-3 space-y-2">
                    <p class="text-slate-300 text-xs font-semibold">ℹ️ Ejemplo:</p>
                    <ul class="text-slate-300 text-xs space-y-1">
                        <li>• Factor 1: 300 de 300 imágenes (más tiempo, más costo IA)</li>
                        <li>• Factor 10: 30 de 300 imágenes (rápido, bajo costo IA)</li>
                        <li>• El usuario paga lo MISMO, menos imágenes procesadas</li>
                    </ul>
                </div>
            </div>

            <!-- 4️⃣ GENERAL SAAS -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-gear text-blue-400"></i>
                    General SaaS
                </h2>
                <p class="text-slate-400 text-sm">Configuración operativa del sistema</p>

                <div class="space-y-2">
                    <label for="support_email" class="text-sm font-bold text-white">Correo Electrónico de Soporte</label>
                    <input type="email"
                           id="support_email"
                           name="support_email"
                           value="{{ old('support_email', $settings->get('support_email', '')) }}"
                           placeholder="support@opendoctor.online"
                           class="{{ $inputBase }} focus:ring-blue-500 placeholder:text-slate-500"
                           required>
                </div>
            </div>

            <!-- 5️⃣ COMISIONES -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-percent text-orange-400"></i>
                    Comisiones de la Plataforma
                </h2>
                <p class="text-slate-400 text-sm">Porcentajes cobrados sobre el valor de cada cita</p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($commissions as $field => [$label, $default])
                        <div class="space-y-2">
                            <label for="{{ $field }}" class="text-sm font-bold text-white">{{ $label }}</label>
                            <input type="number"
                                   id="{{ $field }}"
                                   name="{{ $field }}"
                                   value="{{ old($field, $settings->get($field, $default)) }}"
                                   class="{{ $inputBase }} focus:ring-orange-500"
                                   step="0.1"
                                   min="0"
                                   max="100"
                                   required>
                            @if ($field === 'wompi_fee')
                                <p class="text-xs text-slate-400">Comisión por pago con Wompi</p>
                            @endif
                        </div>
                    @endforeach
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