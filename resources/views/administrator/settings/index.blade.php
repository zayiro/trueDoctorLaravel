@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Configuraciones'],
];
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
            <div class="bg-green-100 border border-green-500/30 rounded-lg p-4">
                <p class="text-green-400 font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        <form action="{{ route('administrator.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <!-- 1️⃣ PRECIOS DE EXÁMENES -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-3.5 6A.75.75 0 003.75 10h12.5a.75.75 0 00.66-1.25l-3.5-6z"/>
                    </svg>
                    Precios de Exámenes por Tipo
                </h2>
                <p class="text-slate-400 text-sm">Configurar precio fijo para cada tipo de examen médico</p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach([
                        'lab' => '🧪 Laboratorio',
                        'xray' => '📸 Radiografía',
                        'ultrasound' => '🔊 Ecografía',
                        'ct' => '📊 Tomografía',
                        'mri' => '🧠 Resonancia Magnética',
                        'dicom' => '💾 DICOM',
                        'mammography' => '🎀 Mamografía'
                    ] as $key => $label)
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white">{{ $label }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
                                <input 
                                    type="number" 
                                    name="exam_type_{{ $key }}_price"
                                    value="{{ $settings['exam_type_' . $key . '_price'] ?? 0 }}"
                                    class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                                    step="1000"
                                    min="5000"
                                    required
                                >
                            </div>
                            <p class="text-xs text-slate-400">
                                Actual: ${{ number_format($settings['exam_type_' . $key . '_price'] ?? 0, 0, ',', '.') }} COP
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

            <!-- 6️⃣ MODELOS DE IA POR TIPO -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3.196 12.87c-.263.13-.698.523-.873.931-.284.681-.281 1.431.008 2.064.288.634.785 1.221 1.443 1.62.324.2.744.202 1.06.002.32-.202.533-.652.533-1.102 0-.45-.212-.9-.533-1.102l-.502-.31.502-.31c.32-.202.533-.652.533-1.102 0-.45-.213-.9-.534-1.102a.745.745 0 00-1.036.21zM9 5a2 2 0 100 4 2 2 0 000-4zM7.371 8.644a5 5 0 1110.258 0M15.75 12c0 .449.213.9.533 1.102.32.202.738.196 1.036-.21.263-.13.698-.523.873-.931.284-.681.281-1.431-.008-2.064-.288-.634-.785-1.221-1.443-1.62-.324-.2-.744-.202-1.06-.002-.32.202-.533.652-.533 1.102 0 .45.212.9.533 1.102l.502.31-.502.31c-.32.202-.533.652-.533 1.102z"/>
                    </svg>
                    Modelos de IA por Tipo de Examen
                </h2>
                <p class="text-slate-400 text-sm">Selecciona qué modelo de IA usar primero para cada tipo</p>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- LABORATORIO -->
                    <div class="bg-slate-900 rounded-lg p-4 space-y-3 border-l-4 border-blue-500">
                        <p class="text-sm font-bold text-white">🧪 LABORATORIO</p>
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-white">Modelo Primario</label>
                            <select name="lab_ai_primary_model" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="gpt-4o" @selected($settings['lab_ai_primary_model'] == 'gpt-4o')>GPT-4o (OpenAI)</option>
                                <option value="claude-sonnet-4-5" @selected($settings['lab_ai_primary_model'] == 'claude-sonnet-4-5')>Claude Sonnet 4.5</option>
                            </select>
                            <p class="text-xs text-slate-400">Se intenta primero</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-white">Modelo Fallback</label>
                            <select name="lab_ai_fallback_model" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="gpt-4o" @selected($settings['lab_ai_fallback_model'] == 'gpt-4o')>GPT-4o (OpenAI)</option>
                                <option value="claude-sonnet-4-5" @selected($settings['lab_ai_fallback_model'] == 'claude-sonnet-4-5')>Claude Sonnet 4.5</option>
                            </select>
                            <p class="text-xs text-slate-400">Si el primero falla</p>
                        </div>
                    </div>

                    <!-- IMAGENOLOGÍA -->
                    <div class="bg-slate-900 rounded-lg p-4 space-y-3 border-l-4 border-purple-500">
                        <p class="text-sm font-bold text-white">🔬 IMAGENOLOGÍA</p>
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-white">Modelo Primario</label>
                            <select name="imaging_ai_primary_model" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="claude-sonnet-4-5" @selected($settings['imaging_ai_primary_model'] == 'claude-sonnet-4-5')>Claude Sonnet 4.5</option>
                                <option value="gpt-4o" @selected($settings['imaging_ai_primary_model'] == 'gpt-4o')>GPT-4o (OpenAI)</option>
                            </select>
                            <p class="text-xs text-slate-400">Se intenta primero</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-white">Modelo Fallback</label>
                            <select name="imaging_ai_primary_model" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="claude-sonnet-4-5" @selected($settings['imaging_ai_fallback_model'] == 'claude-sonnet-4-5')>Claude Sonnet 4.5</option>
                                <option value="gpt-4o" @selected($settings['imaging_ai_fallback_model'] == 'gpt-4o')>GPT-4o (OpenAI)</option>
                            </select>
                            <p class="text-xs text-slate-400">Si el primero falla</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-3">
                    <p class="text-slate-300 text-xs">
                        <strong>ℹ️</strong> Los cambios se aplican inmediatamente a nuevos análisis.
                    </p>
                </div>
            </div>

            <!-- 2️⃣ FACTORES DE DECIMACIÓN -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Factores de Decimación Automática
                </h2>
                <p class="text-slate-400 text-sm">Procesa cada N-ésima imagen (1 = todas, 10 = cada 10ª)</p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach([
                        'ct' => '📊 Tomografía',
                        'mri' => '🧠 Resonancia Magnética',
                        'xray' => '📸 Radiografía',
                        'ultrasound' => '🔊 Ecografía',
                        'dicom' => '🩻 DICOM',
                        'mammography' => '🎀 Mamografía'
                    ] as $key => $label)
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white">{{ $label }}</label>
                            <input 
                                type="number" 
                                name="{{ $key }}_auto_decimation"
                                value="{{ $settings[$key . '_auto_decimation'] ?? 1 }}"
                                class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                min="1"
                                max="100"
                                required
                            >
                            @php
                                $decimation = $settings[$key . '_auto_decimation'] ?? 1;
                                if ($decimation == 1) {
                                    $info = "Procesa todas las imágenes";
                                } else {
                                    $info = "Procesa cada {$decimation}ª imagen";
                                }
                            @endphp
                            <p class="text-xs text-slate-400">{{ $info }}</p>
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

            <!-- 3️⃣ GENERAL SAAS -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    General SaaS
                </h2>
                <p class="text-slate-400 text-sm">Configuración operativa del sistema</p>

                <div class="space-y-2">
                    <label class="text-sm font-bold text-white">Correo Electrónico de Soporte</label>
                    <input 
                        type="email" 
                        name="support_email"
                        value="{{ $settings['support_email'] ?? '' }}"
                        placeholder="support@opendoctor.online"
                        class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-slate-500"
                        required
                    >
                </div>
            </div>

            <!-- 4️⃣ COMISIONES -->
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-3.5 6A.75.75 0 003.75 10h12.5a.75.75 0 00.66-1.25l-3.5-6z"/>
                    </svg>
                    Comisiones de la Plataforma
                </h2>
                <p class="text-slate-400 text-sm">Porcentajes cobrados sobre el valor de cada cita</p>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-white">Comisión Cita Virtual - Doctor (%)</label>
                        <input 
                            type="number" 
                            name="virtual_commission_doctor"
                            value="{{ $settings['virtual_commission_doctor'] ?? 0 }}"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                            step="0.1"
                            min="0"
                            max="100"
                            required
                        >
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-white">Comisión Cita Virtual - Clínica (%)</label>
                        <input 
                            type="number" 
                            name="virtual_commission_clinic"
                            value="{{ $settings['virtual_commission_clinic'] ?? 0 }}"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                            step="0.1"
                            min="0"
                            max="100"
                            required
                        >
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-white">Comisión Cita Presencial - Doctor (%)</label>
                        <input 
                            type="number" 
                            name="presential_commission_doctor"
                            value="{{ $settings['presential_commission_doctor'] ?? 0 }}"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                            step="0.1"
                            min="0"
                            max="100"
                            required
                        >
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-white">Comisión Cita Presencial - Clínica (%)</label>
                        <input 
                            type="number" 
                            name="presential_commission_clinic"
                            value="{{ $settings['presential_commission_clinic'] ?? 0 }}"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                            step="0.1"
                            min="0"
                            max="100"
                            required
                        >
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-white">Fee Wompi (%)</label>
                        <input 
                            type="number" 
                            name="wompi_fee"
                            value="{{ $settings['wompi_fee'] ?? 2.9 }}"
                            class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                            step="0.1"
                            min="0"
                            max="100"
                            required
                        >
                        <p class="text-xs text-slate-400">Comisión por pago con Wompi</p>
                    </div>
                </div>
            </div>

            <!-- BOTÓN GUARDAR -->
            <div class="flex gap-3 sticky bottom-4">
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3.5 px-3 rounded-xl transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2"
                >
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/>
                        </svg>
                        Guardar Cambios
                    </span>
                </button>
            </div>

        </form>
    </div>
</x-admin-layout>