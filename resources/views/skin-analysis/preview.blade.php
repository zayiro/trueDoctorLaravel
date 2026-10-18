<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis"
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
@php
    $langLabels = [
        'es' => '🇪🇸 Español',
        'en' => '🇺🇸 English',
        'fr' => '🇫🇷 Français',
        'pt' => '🇧🇷 Português',
        'de' => '🇩🇪 Deutsch',
    ];

    // Configuración que lee el componente Alpine (@js escapa las comillas de forma segura)
    $cfg = [
        'basePrice' => (int) $price,
        'fee' => (int) $wompiCommission,
        'total' => (int) $total,
        'validateUrl' => route('skin-analysis.promo-codes.validate'),
    ];
@endphp

<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8"
     x-data="skinPreview(@js($cfg))"
     @pageshow.window="submitting = false">
    <div class="max-w-5xl mx-auto">

        {{-- Encabezado --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4">Revisar tu Orden</h1>
            <p class="text-lg text-slate-600">Verifica todos los detalles antes de proceder al pago</p>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Resumen de la orden --}}
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 p-8 mb-6 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-file-medical text-blue-600"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Resumen de tu Orden</h2>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Tipo de Análisis:</span>
                    <span class="text-slate-900 font-bold">🩺 Análisis de piel con IA</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Idioma del Resultado:</span>
                    <span class="text-slate-900 font-bold">{{ $langLabels[$sessionData['language']] ?? '🌍 ' . strtoupper($sessionData['language']) }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Email:</span>
                    <span class="text-slate-900 font-bold text-right truncate ml-2">{{ $sessionData['email'] }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Zona del Cuerpo:</span>
                    <span class="text-slate-900 font-bold text-right ml-2">{{ $sessionData['body_location'] ?: 'No indicada' }}</span>
                </div>

                <div class="justify-between items-center p-4 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-600 font-medium">Síntomas o Descripción:</span>
                    <p class="text-slate-900">{{ $sessionData['description'] ?: 'Sin descripción' }}</p>
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
                    <span class="text-4xl font-black" x-text="money(total)">${{ number_format($total, 0, ',', '.') }}</span>
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

        {{-- Aviso legal --}}
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-6 mb-8">
            <p class="text-sm text-amber-900">
                <strong>⚠️ Importante:</strong> Este análisis con IA es orientativo, <strong>no constituye un diagnóstico</strong> y no reemplaza la valoración de un profesional de la salud. Si notas sangrado, crecimiento rápido o dolor intenso, consulta a un médico cuanto antes.
            </p>
        </div>

        {{-- Envío --}}
        <form action="{{ route('skin-analysis.process-documents') }}" method="POST"
              @submit="onSubmit()" class="space-y-4">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionId }}">
            {{-- Único campo del código: el que se envía al servidor --}}
            <input type="hidden" name="promo_code" :value="promo.code">

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('skin-analysis.upload') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-6 py-4 font-bold text-slate-700 transition hover:bg-slate-200">
                    ← Volver
                </a>
                <button type="submit"
                        :disabled="submitting"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg">
                    <span x-text="submitting ? 'Procesando...' : (isFree ? 'Continuar sin pago' : 'Proceder al Pago')">Proceder al Pago</span>
                    <i class="fa-solid fa-spinner animate-spin" x-show="submitting" x-cloak></i>
                </button>
            </div>

            <p class="text-center text-sm text-slate-600"
               x-text="isFree
                    ? 'Tu análisis se procesará de inmediato, sin pasar por la pasarela de pago'
                    : 'Después de confirmar, serás redirigido a la pasarela de pago segura'">
                Después de confirmar, serás redirigido a la pasarela de pago segura
            </p>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('skinPreview', (cfg) => ({
            basePrice: cfg.basePrice,
            fee: cfg.fee,
            total: cfg.total,

            promoInput: '',
            promo: { code: '', discountAmount: 0, type: null, value: null },
            promoMessage: '',
            promoError: false,
            applying: false,
            submitting: false,

            // ---- Solo para mostrar: el servidor recalcula el precio real al enviar ----
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
                    || document.querySelector('input[name="_token"]')?.value
                    || '';
            },

            // ---- Código promocional ----
            setPromoMessage(text, isError) {
                this.promoMessage = text;
                this.promoError = isError;
            },
            clearPromo(clearInput = false) {
                this.promo = { code: '', discountAmount: 0, type: null, value: null };
                this.fee = cfg.fee;
                this.total = cfg.total;
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
                        this.fee = Number(data.fee);
                        this.total = Number(data.total);

                        const amount = this.money(this.promo.discountAmount);
                        const text = data.is_free
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

            // ---- Envío: el formulario se envía normalmente; solo se bloquea el botón ----
            onSubmit() {
                this.submitting = true;
            },
        }));
    });
</script>
</x-guest-layout>