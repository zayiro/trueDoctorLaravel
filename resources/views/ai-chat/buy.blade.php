@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale);
    $money = fn (int $v) => '$' . number_format($v, 0, ',', '.');
@endphp

<x-guest-layout>
    <div class="bg-gradient-to-b from-white via-blue-50 to-slate-50">
        <div class="max-w-5xl mx-auto py-12 px-4 mt-6">

            {{-- Idioma --}}
            <div class="flex justify-end gap-2 text-xs font-bold mb-4">
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'es']) }}" class="px-3 py-1 rounded-full {{ $locale === 'es' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $t('lang_es') }}</a>
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1 rounded-full {{ $locale === 'en' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $t('lang_en') }}</a>
            </div>

            <div class="text-center space-y-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-xs font-semibold text-blue-700">💬 {{ $t('name') }}</span>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900">{{ $t('buy_title') }}</h1>
                <p class="max-w-2xl mx-auto text-sm md:text-base text-slate-600 leading-relaxed">{{ $t('buy_sub') }}</p>
            </div>

            @if (session('error'))
                <div class="mt-6 rounded-xl border-l-4 border-amber-500 bg-amber-50 p-4">
                    <p class="text-sm font-medium text-amber-800">{{ session('error') }}</p>
                </div>
            @endif

            @if ($paused)
                <div class="mt-10 max-w-xl mx-auto bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center space-y-3">
                    <h2 class="text-xl font-black text-gray-800">{{ $t('paused_title') }}</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $t('paused_text') }}</p>
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">{{ $t('back_home') }}</a>
                </div>
            @else
                <div class="mt-10 grid md:grid-cols-2 gap-6">
                    @foreach ($prices as $minutes => $quote)
                        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 flex flex-col">
                            <h2 class="text-xl font-black text-gray-800">{{ $t('duration_title', ['min' => $minutes]) }}</h2>
                            <p class="text-sm text-slate-500 mt-1">{{ $t("duration_{$minutes}_tag") }}</p>

                            <div class="mt-6">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $t('price_label') }}</p>
                                <p class="text-4xl font-black text-slate-900">{{ $money($quote['total']) }} <span class="text-base font-bold text-slate-500">COP</span></p>
                                @if ($locale === 'en' && $usdRate > 0)
                                    <p class="text-sm text-slate-500">{{ $t('usd_approx', ['usd' => number_format($quote['total'] / $usdRate, 2)]) }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-1">{{ $t('price_incl') }}</p>
                            </div>

                            <form action="{{ route('ai-chat.select') }}" method="POST" class="mt-auto pt-6">
                                @csrf
                                <input type="hidden" name="duration" value="{{ $minutes }}">
                                <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3.5 font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                    {{ $t('choose') }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <ul class="mt-10 max-w-2xl mx-auto space-y-3 text-sm text-slate-600">
                    @foreach (['feat_1', 'feat_2', 'feat_3'] as $feature)
                        <li class="flex gap-3"><span class="text-emerald-600 font-black">✓</span><span>{{ $t($feature, ['days' => $days]) }}</span></li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-10 max-w-2xl mx-auto text-center text-xs leading-relaxed text-slate-500">{{ $t('disclaimer') }}</p>
        </div>
    </div>
</x-guest-layout>
