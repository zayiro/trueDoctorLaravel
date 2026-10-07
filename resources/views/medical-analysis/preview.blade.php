<x-guest-layout>
@php
    $typeLabels = [
        'lab' => '🧪 Laboratorio',
        'xray' => '📸 Radiografía',
        'ultrasound' => '🔊 Ecografía',
        'ct' => '📊 Tomografía',
        'mri' => '🧠 Resonancia',
        'mammography' => '🎀 Mamografía',
        'dicom' => '🩻 DICOM',
    ];

    $langLabels = [
        'es' => '🇪🇸 Español',
        'en' => '🇺🇸 English',
        'fr' => '🇫🇷 Français',
        'pt' => '🇧🇷 Português',
        'de' => '🇩🇪 Deutsch',
    ];

    $reasons = [
        'routine' => 'Control de rutina anual',
        'monitoring' => 'Monitoreo de condición médica',
        'symptoms' => 'Evaluación por síntomas',
        'other' => 'Otros',
    ];

    $fileIcon = fn (string $name) => match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
        'pdf' => '📄',
        'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
        'dcm', 'dicom' => '🩻',
        default => '📋',
    };

    // Configuración que lee el componente Alpine (@js escapa las comillas de forma segura)
    $cfg = [
        'basePrice' => (int) $price,
        'validateUrl' => route('medical-analysis.promo-codes.validate'),
        'processUrl' => route('medical-analysis.process-documents'),
    ];
@endphp

<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8"
     x-data="orderReview(@js($cfg))">
    <div class="max-w-5xl mx-auto">

        {{-- Encabezado --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4">Revisar tu Orden</h1>
            <p class="text-lg text-slate-600">Verifica todos los detalles antes de proceder al pago</p>
        </div>

        {{-- Archivos cargados --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-file-medical text-blue-600"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Archivos Cargados</h2>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach ($files as $file)
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200 hover:border-blue-300 transition">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <span class="text-2xl">{{ $fileIcon($file['name']) }}</span>
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

        {{-- Resumen de la orden --}}
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 p-8 mb-6 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-cart-shopping text-blue-600"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Resumen de tu Orden</h2>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Tipo de Examen:</span>
                    <span class="text-slate-900 font-bold">{{ $typeLabels[$examType] ?? '📋 Examen' }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Idioma del Resultado:</span>
                    <span class="text-slate-900 font-bold">{{ $langLabels[$language] ?? '🌍 ' . strtoupper($language) }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Email:</span>
                    <span class="text-slate-900 font-bold text-right truncate ml-2">{{ $email }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Precio Base:</span>
                    <span class="text-slate-900 font-bold text-lg" x-text="money(basePrice)">${{ number_format($price, 0, ',', '.') }}</span>
                </div>

                {{-- Descuento (aparece al aplicar un código) --}}
                <div x-show="promo.discountAmount > 0" x-cloak
                     class="flex justify-between items-center p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <span class="text-emerald-700 font-bold">Descuento:</span>
                    <div class="text-right">
                        <span class="text-emerald-700 font-bold text-lg" x-text="'-' + money(promo.discountAmount)"></span>
                        <span class="text-emerald-600 text-sm ml-2" x-show="discountLabel" x-text="'(' + discountLabel + ')'"></span>
                    </div>
                </div>

                {{-- Total --}}
                <div class="flex justify-between items-center p-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg text-white">
                    <span class="font-bold text-lg">Total a Pagar:</span>
                    <span class="text-4xl font-black" x-text="money(total)">${{ number_format($price, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Código promocional --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-ticket text-amber-600"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">¿Tienes un Código de Descuento?</h2>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text"
                       x-model="promoInput"
                       @input="onPromoInput()"
                       @keydown.enter.prevent="applyPromo()"
                       maxlength="50"
                       autocomplete="off"
                       placeholder="Ej: VERANO2024"
                       class="flex-1 px-4 py-3 border border-slate-300 rounded-lg uppercase focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                <button type="button"
                        @click="applyPromo()"
                        :disabled="applying"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-bold transition flex items-center justify-center gap-2 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="applying ? 'Validando...' : 'Aplicar'">Aplicar</span>
                    <i class="fa-solid fa-spinner animate-spin" x-show="applying" x-cloak></i>
                </button>
            </div>

            {{-- Resultado de la validación --}}
            <div x-show="promoMessage" x-cloak
                 :class="promoError ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
                 class="p-4 rounded-lg border">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-semibold" x-text="promoMessage"></span>
                    <button type="button" x-show="promo.code" x-cloak @click="clearPromo(true)"
                            class="shrink-0 text-sm underline text-slate-500 hover:text-slate-700">Quitar código</button>
                </div>
                <p x-show="isFree" x-cloak class="mt-2 text-sm">
                    Este código cubre el total de tu orden: no necesitas pagar.
                </p>
            </div>
        </div>

        {{-- Motivo de la consulta --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-6 space-y-6">
            <h2 class="text-xl font-bold text-slate-900">Motivo de tu Consulta</h2>

            <div class="p-6 bg-blue-50 rounded-lg border-2 border-blue-300">
                <p class="text-sm text-slate-600 mb-2"><strong>Opción seleccionada:</strong></p>
                <p class="text-2xl font-bold text-blue-600">{{ $reasons[$reasonType] ?? 'No especificado' }}</p>
            </div>

            @if ($reasonCustom)
                <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <p class="text-sm text-slate-600 mb-2"><strong>Detalles adicionales:</strong></p>
                    <p class="text-slate-900">{{ $reasonCustom }}</p>
                </div>
            @endif
        </div>

        {{-- Aviso legal --}}
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-6 mb-8">
            <p class="text-sm text-amber-900">
                <strong>⚠️ Importante:</strong> Este análisis con IA es una segunda opinión educativa y <strong>no reemplaza</strong> la consulta médica profesional. Siempre consulta con tu médico certificado para diagnóstico definitivo.
            </p>
        </div>

        {{-- Envío --}}
        <form x-ref="form" @submit.prevent="submit()" class="space-y-4">
            @csrf
            {{-- Único campo del código: el que se envía al servidor --}}
            <input type="hidden" name="promotional_code" :value="promo.code">

            <button type="submit"
                    :disabled="submitting"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg">
                <span x-text="submitting ? 'Procesando...' : (isFree ? 'Continuar sin pago' : 'Proceder al Pago')">Proceder al Pago</span>
                <i class="fa-solid fa-spinner animate-spin" x-show="submitting" x-cloak></i>
            </button>

            <p class="text-center text-sm text-slate-600"
               x-text="isFree
                    ? 'Tu análisis se procesará de inmediato, sin pasar por la pasarela de pago'
                    : 'Después de confirmar, serás redirigido a la pasarela de pago segura'">
                Después de confirmar, serás redirigido a la pasarela de pago segura
            </p>
        </form>

        {{-- Estado de carga --}}
        <div x-show="showProgress" x-cloak class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="flex items-center gap-4">
                <i class="fa-solid fa-spinner animate-spin text-3xl text-blue-600"></i>
                <div class="flex-1">
                    <p class="text-lg font-semibold text-slate-900" x-text="statusMessage">Preparando tu análisis...</p>
                    <div class="w-full bg-slate-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderReview', (cfg) => ({
            basePrice: cfg.basePrice,

            promoInput: '',
            promo: { code: '', discountAmount: 0, type: null, value: null },
            promoMessage: '',
            promoError: false,
            applying: false,

            submitting: false,
            showProgress: false,
            progress: 0,
            statusMessage: '',

            // ---- Cálculos (solo para mostrar: el servidor recalcula el precio real) ----
            get total() {
                return Math.max(0, this.basePrice - this.promo.discountAmount);
            },
            get isFree() {
                return this.total === 0;
            },
            get discountLabel() {
                return this.promo.type === 'percent' ? Number(this.promo.value) + '%' : '';
            },
            money(amount) {
                return '$' + Number(amount).toLocaleString('es-CO');
            },
            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content
                    || this.$refs.form.querySelector('[name="_token"]')?.value
                    || '';
            },

            // ---- Código promocional ----
            setPromoMessage(text, isError) {
                this.promoMessage = text;
                this.promoError = isError;
            },
            clearPromo(clearInput = false) {
                this.promo = { code: '', discountAmount: 0, type: null, value: null };
                this.promoMessage = '';
                this.promoError = false;
                if (clearInput) this.promoInput = '';
            },
            onPromoInput() {
                // Si el usuario edita el código ya aplicado, se descarta el descuento.
                if (this.promo.code && this.promoInput.trim().toUpperCase() !== this.promo.code) {
                    this.clearPromo();
                }
            },
            async applyPromo() {
                const code = this.promoInput.trim().toUpperCase();

                if (!code) {
                    this.setPromoMessage('Por favor ingresa un código', true);
                    return;
                }

                this.applying = true;

                try {
                    const response = await fetch(cfg.validateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({ code }),
                    });

                    const data = await response.json();

                    if (data.valid) {
                        this.promo = {
                            code,
                            discountAmount: Number(data.discount_amount) || 0,
                            type: data.discount_type,
                            value: data.discount_value,
                        };

                        const amount = this.money(this.promo.discountAmount);
                        const text = this.isFree
                            ? '✓ Código válido: 100% de descuento'
                            : (this.promo.type === 'percent'
                                ? `✓ Código válido: ${Number(this.promo.value)}% de descuento (${amount})`
                                : `✓ Código válido: ${amount} de descuento`);

                        this.setPromoMessage(text, false);
                    } else {
                        this.clearPromo();
                        this.setPromoMessage(data.message || 'Código inválido o expirado.', true);
                    }
                } catch (error) {
                    console.error(error);
                    this.clearPromo();
                    this.setPromoMessage('Error al validar el código.', true);
                } finally {
                    this.applying = false;
                }
            },

            // ---- Envío de la orden ----
            async submit() {
                if (this.submitting) return;

                this.submitting = true;
                this.showProgress = true;
                this.progress = 30;
                this.statusMessage = '📝 Preparando tu orden...';

                setTimeout(() => {
                    if (this.submitting) {
                        this.progress = 70;
                        this.statusMessage = '🔒 Validando información...';
                    }
                }, 1000);

                try {
                    const response = await fetch(cfg.processUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: new FormData(this.$refs.form),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.status !== 'success') {
                        throw new Error(data.message || 'Error al procesar.');
                    }

                    this.progress = 100;
                    this.statusMessage = this.isFree
                        ? '✅ Procesando tu análisis...'
                        : '✅ Redirigiendo a pago...';

                    // El botón queda deshabilitado hasta salir de la página.
                    setTimeout(() => { window.location.href = data.redirect_url; }, 1200);
                } catch (error) {
                    console.error(error);
                    alert(error.message || 'Ocurrió un error al procesar tu orden.');
                    this.submitting = false;
                    this.showProgress = false;
                    this.progress = 0;
                }
            },
        }));
    });
</script>
</x-guest-layout>