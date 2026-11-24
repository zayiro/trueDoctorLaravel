@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatReportText::t($key, $r, $locale);
    $urgency = $report['urgency'] ?? 'media';
    $urgencyCls = ['baja' => 'bg-emerald-50 text-emerald-700', 'media' => 'bg-amber-50 text-amber-700', 'alta' => 'bg-orange-50 text-orange-700', 'urgente' => 'bg-red-50 text-red-700'][$urgency] ?? 'bg-amber-50 text-amber-700';
@endphp
<x-guest-layout>
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } }</style>
    <div class="max-w-2xl mx-auto py-10 px-4 mt-4">

        @if ($state === 'ready')
            <div class="bg-white rounded-3xl shadow-xl p-6 sm:p-8 border border-gray-100 space-y-6">
                <div class="text-center space-y-1">
                    <div class="text-xs font-bold uppercase tracking-wider text-indigo-500">{{ $t('brand') }}</div>
                    <h1 class="text-2xl font-black text-gray-800">{{ $t('title') }}</h1>
                    @if ($date)<p class="text-sm text-slate-500">{{ $t('subtitle', ['date' => $date]) }}</p>@endif
                </div>

                <div class="rounded-xl px-4 py-3 text-sm font-semibold {{ $urgencyCls }}">{{ $t('urgency') }}: {{ $t('urgency_'.$urgency) }}</div>

                @if ($urgency === 'urgente')
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $t('urgent_banner') }}</div>
                @endif

                @if (!empty($report['reason']))
                    <section>
                        <h2 class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $t('reason') }}</h2>
                        <p class="mt-1 font-semibold text-gray-800">{{ $report['reason'] }}</p>
                    </section>
                @endif

                <section>
                    <h2 class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $t('summary') }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $report['summary'] }}</p>
                </section>

                @if (!empty($report['impressions']))
                    <section>
                        <h2 class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $t('impressions') }}</h2>
                        <ul class="mt-1 list-disc pl-5 text-sm leading-relaxed text-slate-700 space-y-1">
                            @foreach ($report['impressions'] as $i)<li>{{ $i }}</li>@endforeach
                        </ul>
                    </section>
                @endif

                <section>
                    <h2 class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $t('action_plan') }}</h2>
                    <ol class="mt-1 list-decimal pl-5 text-sm leading-relaxed text-slate-700 space-y-1.5">
                        @foreach ($report['action_plan'] as $i)<li>{{ $i }}</li>@endforeach
                    </ol>
                </section>

                @if (!empty($report['warning_signs']))
                    <section class="rounded-xl bg-red-50 px-4 py-3">
                        <h2 class="text-xs font-bold uppercase tracking-wide text-red-700">{{ $t('warning_signs') }}</h2>
                        <ul class="mt-1 list-disc pl-5 text-sm leading-relaxed text-red-900 space-y-1">
                            @foreach ($report['warning_signs'] as $i)<li>{{ $i }}</li>@endforeach
                        </ul>
                    </section>
                @endif

                @if (!empty($report['questions']))
                    <section>
                        <h2 class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $t('questions') }}</h2>
                        <ul class="mt-1 list-disc pl-5 text-sm leading-relaxed text-slate-700 space-y-1">
                            @foreach ($report['questions'] as $i)<li>{{ $i }}</li>@endforeach
                        </ul>
                    </section>
                @endif

                @if ($targetSlug)
                    <section class="no-print rounded-2xl bg-slate-900 px-5 py-6 text-center">
                        <span class="inline-block rounded-md bg-indigo-950 px-2 py-1 text-[10px] font-extrabold uppercase tracking-wide text-indigo-300">{{ $t('spec_badge') }}</span>
                        <h3 class="mt-3 text-base font-extrabold text-white">{{ $t('spec_title') }}</h3>
                        <p class="mt-1 mb-4 text-sm leading-relaxed text-slate-400">
                            @if ($hasSuggested && $available)
                                {{ $t('spec_available', ['spec' => $suggestedName]) }}
                            @elseif ($hasSuggested)
                                {{ $t('spec_fallback', ['spec' => $suggestedName, 'target' => $targetName]) }}
                            @else
                                {{ $t('spec_generic', ['target' => $targetName]) }}
                            @endif
                        </p>
                        <a href="{{ route('search', ['specialty' => $targetSlug]) }}" class="inline-block rounded-lg bg-white px-6 py-3 text-sm font-bold text-slate-900 hover:bg-slate-100">{{ $t('spec_button', ['target' => $targetName]) }}</a>
                    </section>
                @endif

                <p class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-xs leading-relaxed text-amber-800">{{ $t('disclaimer') }}</p>

                <p class="rounded-xl bg-sky-50 border border-sky-100 px-4 py-3 text-xs leading-relaxed text-sky-800">
                    {{ \App\Services\AiChat\AiChatReportText::retention($locale) }}
                    @if ($purgeDate) <strong>{{ $t('retention_until', ['date' => $purgeDate]) }}</strong> @endif
                </p>

                <div class="no-print flex flex-wrap items-center justify-center gap-3">
                    <button type="button" onclick="window.print()" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ $t('print') }}</button>
                    <a href="{{ route('ai-chat.download', ['token' => $token]) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ $t('download') }}</a>
                    @auth
                        <a href="{{ route('patient.ai-chats.index') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $t('back_chats') }}</a>
                    @else
                        <a href="{{ url('/') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $t('back_home') }}</a>
                    @endauth
                </div>
                <p class="text-center text-[11px] text-slate-400">{{ $t('private') }}</p>
            </div>
        @else
            <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center space-y-4">
                <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center text-2xl {{ $state === 'preparing' ? 'bg-indigo-50' : 'bg-gray-50' }}">{{ $state === 'preparing' ? '📝' : ($state === 'failed' ? '⏳' : ($state === 'purged' ? '🔒' : '💬')) }}</div>
                <h1 class="text-xl font-black text-gray-800">{{ $t($state.'_title') }}</h1>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $t($state.'_text', ['days' => max(1, \App\Services\AiChat\AiChatSettings::int('ai_chat_retention_days'))]) }}</p>
                @if ($state === 'purged')
                    <a href="{{ route('ai-chat.buy') }}" class="inline-flex rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $t('new_session') }}</a>
                @else
                    <a href="{{ url('/') }}" class="inline-flex rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $t('back_home') }}</a>
                @endif
            </div>
            @if ($state === 'preparing')
                <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
            @endif
        @endif
    </div>
</x-guest-layout>
