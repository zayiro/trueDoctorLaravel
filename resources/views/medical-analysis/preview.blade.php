<x-guest-layout>
    <div class="max-w-7xl w-full mx-auto px-6 py-12 flex-grow mt-6">
        <div class="bg-slate-950 rounded-2xl border border-white/10 p-8 shadow-2xl space-y-8">
            
            <!-- ENCABEZADO -->
            <div class="space-y-2 text-center">
                <h1 class="text-3xl font-black text-white">Revisar tu Orden</h1>
                <p class="text-md text-slate-400">Verifica los detalles antes de procesar tu análisis médico</p>
            </div>

            <!-- RESUMEN DE ARCHIVOS -->
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-6 space-y-4">
                <h2 class="text-white font-bold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    Archivos Cargados
                </h2>
                
                <div class="space-y-2 max-h-[300px] overflow-y-auto">
                    @foreach($files as $file)
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded border border-white/5 hover:border-blue-500/30 transition">
                            <div class="flex items-center gap-3 flex-1">
                                <span class="text-2xl">
                                    @php
                                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                        $icon = match($ext) {
                                            'pdf' => '📕',
                                            'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
                                            'dcm', 'dicom' => '🩻',
                                            default => '📄'
                                        };
                                    @endphp
                                    {{ $icon }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-200 font-medium truncate">{{ $file['name'] }}</p>
                                    <p class="text-slate-400 text-sm">{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</p>
                                </div>
                            </div>
                            <span class="text-green-400 text-sm font-bold">✓</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-blue-500/20">
                    <p class="text-slate-300 text-sm">
                        <strong>Total:</strong> {{ count($files) }} archivo(s) · {{ number_format($totalSize / 1024 / 1024, 2) }} MB
                    </p>
                </div>
            </div>

            <!-- RESUMEN DE ORDEN (Consolidado) -->
            <div class="bg-gradient-to-r from-purple-500/10 to-indigo-500/10 border border-purple-500/30 rounded-lg p-6 space-y-4">
                <h3 class="text-white font-bold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.08.02l-2.5 2.75H2.75A1.75 1.75 0 001 7.25v10A1.75 1.75 0 002.75 19h14.5A1.75 1.75 0 0019 17.25v-10a1.75 1.75 0 00-1.75-1.75h-1.83l-2.5-2.75a.75.75 0 00-1.08-.02L10 4.5l-1.84-1.75zm1.84.98l1.84 2.02h2.66A.25.25 0 0115 6.5v10a.25.25 0 01-.25.25H2.75A.25.25 0 012.5 16.5v-10a.25.25 0 01.25-.25h2.66l1.84-2.02z"/>
                    </svg>
                    Resumen de tu Orden
                </h3>

                <!-- Tipo de Examen -->
                <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5">
                    <span class="text-slate-300">Tipo de Examen:</span>
                    <span class="text-white font-bold text-lg">
                        @php
                            $typeLabels = [
                                'lab' => '🧪 Laboratorio',
                                'xray' => '📸 Radiografía',
                                'ultrasound' => '🔊 Ecografía',
                                'ct' => '📊 Tomografía',
                                'mri' => '🧠 Resonancia',
                                'mammography' => '🎀 Mamografía',
                                'dicom' => '🩻 DICOM'
                            ];
                            echo $typeLabels[$examType] ?? '📋 Examen';
                        @endphp
                    </span>
                </div>

                <!-- Precio Base -->
                <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-white/5">
                    <span class="text-slate-300">Precio base:</span>
                    <span class="text-white font-medium" id="summaryBasePrice">${{ number_format($price, 0, ',', '.') }}</span>
                </div>

                <!-- Descuento (si aplica) -->
                <div id="discountRow" class="hidden flex justify-between items-center p-3 bg-emerald-500/10 rounded border border-emerald-500/30">
                    <span class="text-emerald-300 font-medium">Descuento:</span>
                    <div class="text-right">
                        <span class="text-emerald-400 font-bold" id="discountAmount">-$0</span>
                        <span class="text-emerald-300 text-sm ml-2" id="discountPercent"></span>
                    </div>
                </div>

                <!-- Total (separado visualmente) -->
                <div class="flex justify-between items-center p-4 bg-purple-500/20 rounded-lg border border-purple-500/50 mt-2">
                    <span class="text-white font-bold text-lg">Total a Pagar:</span>
                    <span class="text-4xl font-black text-purple-400" id="totalPrice">${{ number_format($price, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- CÓDIGO PROMOCIONAL -->
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-6 space-y-4">
                <h3 class="text-white font-bold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4.5 2a1 1 0 00-.96.732L2.332 6H2a1 1 0 000 2h.26l.882 4.41a1 1 0 001.962 0l.882-4.41H18a1 1 0 000-2h-.332L15.46 2.732A1 1 0 0014.5 2H4.5zM8 16a2 2 0 110-4 2 2 0 010 4zm8 0a2 2 0 110-4 2 2 0 010 4z"/>
                    </svg>
                    ¿Tienes un Código Promocional?
                </h3>

                <div class="flex gap-2">
                    <input type="text" id="promoInput" placeholder="Ej: DESCUENTO2024" 
                        class="flex-1 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-yellow-500 placeholder:text-slate-500">
                    <button type="button" onclick="applyPromoCode()" 
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2">
                        <span id="applyBtnText">Aplicar</span>
                        <svg id="applySpinner" class="animate-spin h-4 w-4 text-white hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>

                <div id="promoResult" class="hidden p-3 rounded-lg"></div>
            </div>

            <!-- DATOS DEL PACIENTE -->
            <div class="bg-slate-900/50 border border-white/5 rounded-lg p-6 space-y-4">
                <h3 class="text-white font-bold text-lg">Información del Paciente</h3>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-slate-400 text-sm">Correo Electrónico</p>
                        <p class="text-white font-medium">{{ $email }}</p>
                    </div>

                    <div>
                        <p class="text-slate-400 text-sm">Idioma del Resultado</p>
                        <p class="text-white font-medium">
                            @if($language === 'es') 🇪🇸 Español @else 🇺🇸 English @endif
                        </p>
                    </div>

                    @if($reasonType)
                        <div>
                            <p class="text-slate-400 text-sm">Motivo del Examen</p>
                            <p class="text-white font-medium">
                                @php
                                    $reasons = [
                                        'rutina' => 'Control de rutina',
                                        'control' => 'Seguimiento de enfermedad',
                                        'sintomas' => 'Por síntomas recientes',
                                        'otros' => 'Otro motivo'
                                    ];
                                    echo $reasons[$reasonType] ?? $reasonType;
                                @endphp
                            </p>
                        </div>
                    @endif
                </div>

                @if($reasonCustom)
                    <div class="pt-4 border-t border-white/5">
                        <p class="text-slate-400 text-sm mb-2">Observaciones</p>
                        <p class="text-slate-200 italic">{{ $reasonCustom }}</p>
                    </div>
                @endif
            </div>

            <!-- ACCIONES -->
            <div class="grid md:grid-cols-2 gap-4">
                <form action="{{ route('medical-analysis.upload') }}" method="GET" class="flex">
                    <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3.5 px-4 rounded-xl transition-all">
                        ← Volver a editar
                    </button>
                </form>

                <form id="processForm" method="POST">
                    @csrf
                    <input type="hidden" name="customer_email" value="{{ $email }}">
                    <input type="hidden" name="selected_language" value="{{ $language }}">
                    <input type="hidden" name="reason_type" value="{{ $reasonType }}">
                    <input type="hidden" name="reason_custom" value="{{ $reasonCustom }}">
                    <input type="hidden" name="detected_exam_type" value="{{ $examType }}">
                    <input type="hidden" id="promoCodeField" name="promotional_code" value="">
                    
                    <button type="submit" id="processBtn" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3.5 px-3 rounded-xl transition-all shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2">
                        <span id="processBtnText">Procesar Análisis Médico</span>
                        <svg id="processSpinner" class="animate-spin h-5 w-5 text-white hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="text-center">
                <small class="text-sm text-slate-400 font-medium">Al hacer clic, aceptas nuestros <a href="{{ route('terms.show') }}" class="underline hover:text-indigo-600 transition-colors">Términos de Servicio</a> y <a href="{{ route('privacy.show') }}" class="underline hover:text-indigo-600 transition-colors">Política de Privacidad</a>.</small>
            </div>

            <!-- ESTADO DE PROCESAMIENTO -->
            <div id="loadingStatus" class="hidden bg-slate-900 border border-blue-500/20 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-white font-semibold" id="statusMessage">Analizando documentos...</span>
                    <span class="text-white font-bold" id="progressPercentage">0%</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-white/5">
                    <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-emerald-500 text-white h-full w-0 transition-all duration-300"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        const basePrice = {{ $price }};
        let appliedDiscount = 0;
        let appliedPromoCode = '';

        const processForm = document.getElementById('processForm');
        const processBtn = document.getElementById('processBtn');
        const processBtnText = document.getElementById('processBtnText');
        const processSpinner = document.getElementById('processSpinner');
        const loadingStatus = document.getElementById('loadingStatus');
        const progressBar = document.getElementById('progressBar');
        const progressPercentage = document.getElementById('progressPercentage');
        const statusMessage = document.getElementById('statusMessage');

        // Aplicar código promocional
        async function applyPromoCode() {
            const code = document.getElementById('promoInput').value.trim();
            const applyBtn = event.target.closest('button');
            const applyBtnText = document.getElementById('applyBtnText');
            const applySpinner = document.getElementById('applySpinner');

            if (!code) {
                showPromoError('Por favor, ingresa un código.');
                return;
            }

            applyBtn.disabled = true;
            applyBtnText.innerText = 'Validando...';
            applySpinner.classList.remove('hidden');

            try {
                const response = await fetch("{{ route('medical-analysis.promo-codes.validate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ code: code })
                });

                const data = await response.json();

                if (data.valid) {
                    appliedDiscount = data.discount_amount;
                    appliedPromoCode = code;
                    updatePriceDisplay();
                    showPromoSuccess(data.discount_amount, data.discount_type, data.discount_value);
                    document.getElementById('promoCodeField').value = code;
                } else {
                    showPromoError(data.message || 'Código inválido o expirado.');
                    appliedDiscount = 0;
                    appliedPromoCode = '';
                    updatePriceDisplay();
                }
            } catch (error) {
                console.error(error);
                showPromoError('Error al validar el código.');
            } finally {
                applyBtn.disabled = false;
                applyBtnText.innerText = 'Aplicar';
                applySpinner.classList.add('hidden');
            }
        }

        function updatePriceDisplay() {
            const totalPrice = Math.max(0, basePrice - appliedDiscount);
            
            document.getElementById('totalPrice').textContent = `$${totalPrice.toLocaleString('es-CO')}`;
            document.getElementById('summaryBasePrice').textContent = `$${basePrice.toLocaleString('es-CO')}`;

            if (appliedDiscount > 0) {
                document.getElementById('discountRow').classList.remove('hidden');
                document.getElementById('discountAmount').textContent = `-$${appliedDiscount.toLocaleString('es-CO')}`;
            } else {
                document.getElementById('discountRow').classList.add('hidden');
            }
        }

        function showPromoSuccess(discountAmount, type, value) {
            const promoResult = document.getElementById('promoResult');
            const text = type === 'percentage' ? `${value}% descuento` : `$${value} descuento`;
            
            promoResult.className = 'p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30';
            promoResult.innerHTML = `
                <div class="flex items-center gap-2 text-emerald-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">✓ Código válido: ${text}</span>
                </div>
            `;
            promoResult.classList.remove('hidden');
        }

        function showPromoError(message) {
            const promoResult = document.getElementById('promoResult');
            promoResult.className = 'p-3 rounded-lg bg-red-500/10 border border-red-500/30';
            promoResult.innerHTML = `
                <div class="flex items-center gap-2 text-red-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">${message}</span>
                </div>
            `;
            promoResult.classList.remove('hidden');
        }

        // Procesar análisis
        processForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            processBtn.disabled = true;
            processBtn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            processSpinner.classList.remove('hidden');
            processBtnText.innerText = 'Analizando...';
            loadingStatus.classList.remove('hidden');
            updateProgress(35, 'Preparando análisis médico...');

            const formData = new FormData(processForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                setTimeout(() => {
                    updateProgress(65, 'Conectando con IA médica...');
                }, 1000);

                const response = await fetch("{{ route('medical-analysis.process-documents') }}", {
                    method: "POST",
                    headers: { "X-CSRF-TOKEN": csrfToken },
                    body: formData
                });

                if (!response.ok) throw new Error('Error al procesar.');

                const data = await response.json();
                
                if (data.status === 'success') {
                    updateProgress(100, 'Análisis completado.');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                        console.log("redirecciona");
                    }, 1500);
                } else {
                    alert(data.message || 'Error al procesar.');
                }

            } catch (error) {
                console.error(error);
                alert('Ocurrió un error al procesar el análisis.');
            } finally {
                processBtn.disabled = false;
                processBtn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                processSpinner.classList.add('hidden');
                processBtnText.innerText = 'Procesar Análisis Médico';
                setTimeout(() => loadingStatus.classList.add('hidden'), 2000);
            }
        });

        function updateProgress(value, message) {
            progressBar.style.width = `${value}%`;
            progressPercentage.innerText = `${value}%`;
            if (message) statusMessage.innerText = message;
        }

        // Permitir Enter en el input de código
        document.getElementById('promoInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') applyPromoCode();
        });
    </script>
</x-guest-layout>