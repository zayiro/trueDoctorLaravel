@php $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale); @endphp
<x-guest-layout>
    <div class="max-w-md mx-auto py-16 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 space-y-5">
            <div class="text-center space-y-2">
                <div class="mx-auto w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">🔒</div>
                <h1 class="text-2xl font-black text-gray-800">{{ $t('verify_title') }}</h1>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $t('verify_text') }}</p>
            </div>

            @if (session('error'))
                <p class="text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">{{ session('error') }}</p>
            @endif
            @if (session('status'))
                <p class="text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">{{ session('status') }}</p>
            @endif

            @unless ($locked)
                <form method="POST" action="{{ route('ai-chat.verify', ['token' => $token]) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="identification" required autofocus autocomplete="off" inputmode="text" maxlength="30"
                           class="w-full rounded-xl border border-gray-300 px-4 py-3 text-center text-lg tracking-wide focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit"
                            class="w-full rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        {{ $t('verify_button') }}
                    </button>
                </form>
            @endunless

            <form method="POST" action="{{ route('ai-chat.resend', ['token' => $token]) }}" class="text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-blue-600 hover:underline">{{ $t('resend_button') }}</button>
            </form>
        </div>
    </div>
</x-guest-layout>
