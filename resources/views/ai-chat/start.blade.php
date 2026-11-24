@php $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale); @endphp
<x-guest-layout>
    <div class="max-w-xl mx-auto py-16 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center space-y-5">
            <div class="mx-auto w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">🩺</div>
            <h1 class="text-2xl font-black text-gray-800">{{ $t('start_title') }}</h1>
            <p class="text-sm text-slate-600 leading-relaxed">{{ $t('start_text', ['min' => $session->duration_minutes]) }}</p>
            <p class="text-sm text-slate-500 leading-relaxed">{{ $t('start_tips') }}</p>
            @if ($expires)
                <p class="text-xs text-slate-400">{{ $t('start_expires', ['date' => $expires]) }}</p>
            @endif

            <form method="POST" action="{{ route('ai-chat.begin', ['token' => $token]) }}" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <button type="submit" :disabled="sending"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-10 py-3 text-lg font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60">
                    {{ $t('start_button') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
