@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale);
    $expires = $session->expires_at
        ? $session->expires_at->copy()->timezone('America/Bogota')->locale($locale)->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y')
        : null;
@endphp

<x-guest-layout>
    @if (! $confirmed)
        {{-- Mientras el pago no está confirmado, la página se vuelve a consultar sola. --}}
        <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
    @endif

    <div class="max-w-xl mx-auto py-16 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center space-y-4">
            @if ($confirmed)
                <div class="mx-auto w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">✓</div>
                <h1 class="text-2xl font-black text-gray-800">{{ $t('purchased_title') }}</h1>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $t('purchased_text', ['min' => $session->duration_minutes]) }}</p>
                @if ($expires)
                    <p class="text-sm font-semibold text-slate-700">{{ $t('purchased_expires', ['date' => $expires]) }}</p>
                @endif
                <a href="{{ route('ai-chat.enter', ['token' => $session->token_access]) }}"
                   class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    {{ $t('purchased_open') }}
                </a>
            @else
                <div class="mx-auto w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">⏳</div>
                <h1 class="text-2xl font-black text-gray-800">{{ $t('purchased_pending_title') }}</h1>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $t('purchased_pending_text') }}</p>
                <a href="{{ route('ai-chat.checkout', ['token' => $session->token_access]) }}" class="text-xs font-semibold text-slate-400 underline">{{ $t('ck_retry') }}</a>
            @endif

            <p class="pt-2 text-xs leading-relaxed text-slate-400">{{ $t('disclaimer') }}</p>
        </div>
    </div>
</x-guest-layout>
