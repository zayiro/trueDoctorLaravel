@php
    $breadcrumbs = [
        ['name' => 'Dashboard', 'href' => \Illuminate\Support\Facades\Route::has('patient.dashboard') ? route('patient.dashboard') : route('admin.dashboard')],
        ['name' => 'Mis chats de salud IA'],
    ];
    $labels = [
        'pendiente' => ['Por empezar', 'bg-blue-50 text-blue-700'],
        'activa' => ['En curso', 'bg-emerald-50 text-emerald-700'],
        'terminada' => ['Terminada', 'bg-gray-100 text-gray-600'],
        'vencida' => ['Vencida', 'bg-red-50 text-red-700'],
    ];
@endphp
<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Mis chats de salud con IA</h1>
            <p class="text-sm text-slate-500">Tus sesiones compradas. El tiempo empieza solo cuando pulsas «Empezar».</p>
        </div>

        @forelse ($rows as $row)
            @php [$label, $cls] = $labels[$row->status] ?? [$row->status, 'bg-gray-100 text-gray-600']; @endphp
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex items-center justify-between gap-3">
                <div>
                    <div class="font-bold text-gray-800">Sesión de {{ $row->duration_minutes }} minutos</div>
                    <div class="text-xs text-slate-500">
                        Comprada el {{ optional($row->paid_at)->timezone('America/Bogota')->format('d/m/Y') }}
                        @if ($row->status === 'pendiente' && $row->expires_at)
                            · empieza hasta el {{ $row->expires_at->timezone('America/Bogota')->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $cls }}">{{ $label }}</span>
                    @if ($row->status === 'terminada')
                        @if ($row->purged_at)
                            <span class="text-xs text-slate-400">{{ \App\Services\AiChat\AiChatReportText::t('my_chats_purged') }}</span>
                        @elseif ($row->report_content)
                            <a href="{{ route('ai-chat.report', ['token' => $row->token_access]) }}"
                               class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ \App\Services\AiChat\AiChatReportText::t('my_chats_view') }}
                            </a>
                        @elseif (in_array($row->report_status, ['pendiente', 'generando'], true))
                            <span class="text-xs text-slate-500">{{ \App\Services\AiChat\AiChatReportText::t('my_chats_preparing') }}</span>
                        @endif
                    @endif
                    @if (in_array($row->status, ['pendiente', 'activa'], true))
                        <a href="{{ route('ai-chat.enter', ['token' => $row->token_access]) }}"
                           class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $row->status === 'activa' ? 'Continuar' : 'Empezar' }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center space-y-3">
                <p class="text-slate-600">Aún no tienes chats de salud con IA.</p>
                <a href="{{ route('ai-chat.buy') }}" class="inline-flex rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Ver sesiones</a>
            </div>
        @endforelse

        {{ $rows->links() }}
    </div>
</x-admin-layout>
