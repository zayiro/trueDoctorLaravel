@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale);
    $money = fn (int $v) => '$' . number_format($v, 0, ',', '.');
@endphp

<x-guest-layout>
    <div class="bg-gradient-to-b from-white via-blue-50 to-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-12 md:py-16">

            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-xs font-semibold text-blue-700">
                    🔒 {{ $t('checkout_secure') }}
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ $t('checkout_title') }}</h1>
            </div>

            @if (session('error'))
                <div class="mt-6 rounded-xl border-l-4 border-red-500 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            <div class="mt-8 space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-md md:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('checkout_summary') }}</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-600">{{ $t('ck_session') }}</span>
                        <span class="font-semibold text-slate-800">{{ $t('ck_minutes', ['min' => $session->duration_minutes]) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-600">{{ $t('ck_price') }}</span>
                        <span class="font-semibold text-slate-800">{{ $money((int) $session->price_cop) }} COP</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-600">{{ $t('ck_fee') }}</span>
                        <span class="font-semibold text-slate-800">{{ $money($commission) }} COP</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                        <span class="font-bold text-slate-900">{{ $t('ck_total') }}</span>
                        <span class="font-bold text-slate-900">{{ $money((int) $session->total_cop) }} COP</span>
                    </div>
                </div>

                {{-- El intento de pago ya está creado: este enlace lleva al checkout de Wompi y regresa a payments.return --}}
                <a href="{{ $checkoutUrl }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3.5 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    {{ $t('ck_pay', ['total' => $money((int) $session->total_cop)]) }}
                </a>

                <p class="text-center text-xs leading-relaxed text-slate-500">{{ $t('ck_note', ['days' => $days]) }}</p>
                <p class="text-center text-xs leading-relaxed text-slate-400">{{ $t('disclaimer') }}</p>
            </div>
        </div>
    </div>
</x-guest-layout>
