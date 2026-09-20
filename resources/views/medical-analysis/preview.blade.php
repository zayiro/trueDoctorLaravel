<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4">
                    Revisar tu Orden
                </h1>
                <p class="text-lg text-slate-600">
                    Verifica todos los detalles antes de proceder al pago
                </p>
            </div>

            <!-- Archivos Cargados -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-blue-600">
                            <path d="M9 6.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM9 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM9 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM10.5 6a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM21.75 18.75a.75.75 0 0 0-1.5 0v2.25h-2.25a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25h2.25a.75.75 0 0 0 0-1.5h-2.25v-2.25Z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">Archivos Cargados</h2>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($files as $file)
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200 hover:border-blue-300 transition">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <span class="text-2xl">
                                    @php
                                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                        $icon = match($ext) {
                                            'pdf' => '📄',
                                            'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
                                            'dcm', 'dicom' => '🩻',
                                            default => '📋'
                                        };
                                    @endphp
                                    {{ $icon }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">{{ $file['name'] }}</p>
                                    <p class="text-sm text-slate-500">{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</p>
                                </div>
                            </div>
                            <span class="text-green-600 font-bold ml-2">✓</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <p class="text-sm text-slate-600">
                        <strong class="text-slate-900">{{ count($files) }} archivo(s)</strong> · 
                        {{ number_format($totalSize / 1024 / 1024, 2) }} MB
                    </p>
                </div>
            </div>

            <!-- Resumen de Orden -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 p-8 mb-6 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-blue-600">
                            <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.75 3.75 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 007.5 15h11.218a.75.75 0 00.674-.415l3.638-5.45a.75.75 0 00-.67-1.135H17.25v-1.5a.75.75 0 00-.75-.75H5.546L5.057 2.559a.75.75 0 00-.72-.559H2.25z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">Resumen de tu Orden</h2>
                </div>

                <div class="space-y-3">
                    <!-- Tipo de Examen -->
                    <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-600 font-medium">Tipo de Examen:</span>
                        <span class="text-slate-900 font-bold">
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

                    <!-- Idioma -->
                    <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-600 font-medium">Idioma del Resultado:</span>
                        <span class="text-slate-900 font-bold">
                            @php
                                $langLabels = [
                                    'es' => '🇪🇸 Español',
                                    'en' => '🇺🇸 English',
                                    'fr' => '🇫🇷 Français',
                                    'pt' => '🇧🇷 Português',
                                    'de' => '🇩🇪 Deutsch'
                                ];
                                echo $langLabels[$language] ?? '🌍 ' . strtoupper($language);
                            @endphp
                        </span>
                    </div>

                    <!-- Email -->
                    <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-600 font-medium">Email:</span>
                        <span class="text-slate-900 font-bold text-right truncate ml-2">{{ $email }}</span>
                    </div>

                    <!-- Precio Base -->
                    <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-600 font-medium">Precio Base:</span>
                        <span class="text-slate-900 font-bold text-lg" id="summaryBasePrice">${{ number_format($price, 0, ',', '.') }}</span>
                    </div>

                    <!-- Descuento (si aplica) -->
                    <div id="discountRow" class="hidden flex justify-between items-center p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                        <span class="text-emerald-700 font-bold">Descuento:</span>
                        <div class="text-right">
                            <span class="text-emerald-700 font-bold text-lg" id="discountAmount">-$0</span>
                            <span class="text-emerald-600 text-sm ml-2" id="discountPercent"></span>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center p-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg text-white">
                        <span class="font-bold text-lg">Total a Pagar:</span>
                        <span class="text-4xl font-black" id="totalPrice">${{ number_format($price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Código Promocional -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-amber-600">
                            <path d="M5.223 2.25c-.497 0-.974.198-1.325.554a2.25 2.25 0 0 0 2.236 3.75c.967-.3 1.745-1.086 2.045-2.053a2.25 2.25 0 0 0-2.956-2.251ZM9.5 7.5A3 3 0 1 1 12 3.5a3 3 0 0 1-2.5 4Zm7.48 13.75c.497 0 .974-.198 1.325-.554a2.25 2.25 0 0 1-2.236-3.75c.967.3 1.745 1.086 2.045 2.053a2.25 2.25 0 0 1 .866 2.251Zm-7.48-13.75a3 3 0 1 1 2.5 4 3 3 0 0 1-2.5-4ZM19 19.5a3 3 0 1 0-3 3 3 3 0 0 0 3-3Z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">¿Tienes un Código de Descuento?</h2>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <input 
                        type="text" 
                        id="promoInput" 
                        placeholder="Ej: VERANO2024"
                        class="flex-1 px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                    >
                    <button 
                        type="button" 
                        onclick="applyPromoCode()" 
                        class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-bold transition flex items-center justify-center gap-2 whitespace-nowrap"
                    >
                        <span id="applyBtnText">Aplicar</span>
                        <svg id="applySpinner" class="animate-spin h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>

                <div id="promoResult" class="hidden p-4 rounded-lg"></div>
                <input type="hidden" id="promoCodeField" name="promotional_code" value="">
            </div>

            <!-- Motivo de la Consulta -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
                <h2 class="text-xl font-bold text-slate-900">Motivo de tu Consulta</h2>
                
                @php
                    $reasons = [
                        'routine' => 'Control de rutina anual',
                        'monitoring' => 'Monitoreo de condición médica',
                        'symptoms' => 'Evaluación por síntomas',
                        'other' => 'Otros'
                    ];
                @endphp

                <!-- Mostrar la opción seleccionada -->
                <div class="p-6 bg-blue-50 rounded-lg border-2 border-blue-300">
                    <p class="text-sm text-slate-600 mb-2"><strong>Opción seleccionada:</strong></p>
                    <p class="text-2xl font-bold text-blue-600">
                        {{ $reasons[$reasonType] ?? 'No especificado' }}
                    </p>
                </div>

                <!-- Detalles adicionales si existen -->
                @if($reasonCustom)
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <p class="text-sm text-slate-600 mb-2"><strong>Detalles adicionales:</strong></p>
                        <p class="text-slate-900">{{ $reasonCustom }}</p>
                    </div>
                @endif
            </div>

            <!-- Aviso Legal -->
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-6 mb-8">
                <p class="text-sm text-amber-900">
                    <strong>⚠️ Importante:</strong> Este análisis con IA es una segunda opinión educativa y <strong>no reemplaza</strong> la consulta médica profesional. Siempre consulta con tu médico certificado para diagnóstico definitivo.
                </p>
            </div>

            <!-- Botón Procesar -->
            <form id="processForm" class="space-y-4">
                @csrf
                <input type="hidden" id="promoCodeField" name="promotional_code" value="">

                <button 
                    type="submit"
                    id="processBtn"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 shodow-lg"
                >
                    <span id="processBtnText">Proceder al Pago</span>
                    <svg id="processSpinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 animate-spin hidden">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                </button>

                <p class="text-center text-sm text-slate-600">
                    Después de confirmar, serás redirigido a la pasarela de pago segura
                </p>
            </form>

            <!-- Loading Status -->
            <div id="loadingStatus" class="hidden mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p id="statusMessage" class="text-lg font-semibold text-slate-900">
                            Preparando tu análisis...
                        </p>
                        <div class="w-full bg-slate-200 rounded-full h-2 mt-2">
                            <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const basePrice = {{ $price }};
        const processForm = document.getElementById('processForm');
        const processBtn = document.getElementById('processBtn');
        const processSpinner = document.getElementById('processSpinner');
        const processBtnText = document.getElementById('processBtnText');
        const loadingStatus = document.getElementById('loadingStatus');
        const statusMessage = document.getElementById('statusMessage');
        const progressBar = document.getElementById('progressBar');

        let appliedDiscount = 0;
        let appliedPromoCode = '';

        async function applyPromoCode() {
            const code = document.getElementById('promoInput').value.trim().toUpperCase();
            
            if (!code) {
                alert('Por favor ingresa un código');
                return;
            }

            const applyBtn = document.querySelector('button[onclick="applyPromoCode()"]');
            const applyBtnText = document.getElementById('applyBtnText');
            const applySpinner = document.getElementById('applySpinner');

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
            
            promoResult.className = 'p-4 rounded-lg bg-emerald-50 border border-emerald-200';
            promoResult.innerHTML = `
                <div class="flex items-center gap-2 text-emerald-700">
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
            promoResult.className = 'p-4 rounded-lg bg-red-50 border border-red-200';
            promoResult.innerHTML = `
                <div class="flex items-center gap-2 text-red-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">${message}</span>
                </div>
            `;
            promoResult.classList.remove('hidden');
        }

        processForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            processBtn.disabled = true;
            processBtn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            processSpinner.classList.remove('hidden');
            processBtnText.innerText = 'Procesando...';
            loadingStatus.classList.remove('hidden');
            progressBar.style.width = '30%';
            statusMessage.innerText = '📝 Preparando tu orden...';

            const formData = new FormData(processForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                setTimeout(() => {
                    progressBar.style.width = '70%';
                    statusMessage.innerText = '🔒 Validando información...';
                }, 1000);

                const response = await fetch("{{ route('medical-analysis.process-documents') }}", {
                    method: "POST",
                    headers: { "X-CSRF-TOKEN": csrfToken },
                    body: formData
                });

                if (!response.ok) throw new Error('Error al procesar.');

                const data = await response.json();
                
                if (data.status === 'success') {
                    progressBar.style.width = '100%';
                    statusMessage.innerText = '✅ Redirigiendo a pago...';
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                } else {
                    alert(data.message || 'Error al procesar.');
                }

            } catch (error) {
                console.error(error);
                alert('Ocurrió un error al procesar tu orden.');
            } finally {
                processBtn.disabled = false;
                processBtn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                processSpinner.classList.add('hidden');
                processBtnText.innerText = 'Proceder al Pago';
                setTimeout(() => loadingStatus.classList.add('hidden'), 2000);
            }
        });

        // Enter en código promocional
        document.getElementById('promoInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') applyPromoCode();
        });
    </script>
</x-guest-layout>