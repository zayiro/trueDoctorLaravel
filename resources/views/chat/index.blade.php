@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Chats',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto px-4 py-8">

        <div class="flex flex-col md:flex-row gap-6 h-[80vh]">

            {{-- SIDEBAR: Lista de conversaciones --}}
            <div class="md:w-80 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">

                {{-- Header bandeja --}}
                <div class="p-4 border-b border-slate-100">
                    <h2 class="text-lg font-black text-slate-800">Mensajes</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Chat con pacientes y especialistas</p>
                </div>

                {{-- Tabs de estado --}}
                <div class="flex border-b border-slate-100">
                    @foreach(['active' => 'Activos', 'managed' => 'Gestionados', 'blocked' => 'Bloqueados'] as $key => $label)
                        <a href="{{ route('chat.index', ['status' => $key]) }}"
                            class="flex-1 text-center py-2.5 text-xs font-bold transition
                                {{ request('status', 'active') === $key
                                    ? 'text-blue-600 border-b-2 border-blue-600'
                                    : 'text-slate-400 hover:text-slate-600' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                {{-- Lista --}}
                <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                    @forelse($conversations as $conv)
                        @php
                            $other = match(Auth::user()->role) {
                                'patient' => $conv->doctor?->user ?? $conv->clinic,
                                'doctor', 'clinic' => $conv->patient?->user,
                                default => null,
                            };
                            $unread = $conv->unreadCount(Auth::id());
                        @endphp

                        <a href="{{ route('chat.show', $conv) }}"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition">

                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-700 font-black text-sm">
                                {{ substr($other?->name ?? '?', 0, 1) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold text-slate-800 truncate">
                                        {{ $other?->name ?? 'Usuario' }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 flex-shrink-0 ml-1">
                                        {{ $conv->last_message_at?->diffForHumans(null, true) }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 truncate">
                                    {{ $conv->lastMessage?->body ?? 'Adjunto' }}
                                </p>
                            </div>

                            @if($unread > 0)
                                <span class="w-5 h-5 bg-blue-600 text-white text-[10px] font-black rounded-full flex items-center justify-center flex-shrink-0">
                                    {{ $unread }}
                                </span>
                            @endif
                        </a>
                    @empty
                        <div class="flex flex-col items-center justify-center h-40 text-slate-400">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                            </svg>
                            <p class="text-xs font-medium">Sin conversaciones</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- PANEL DERECHO: Placeholder cuando no hay conversación abierta --}}
            <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center justify-center text-slate-300">
                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                </svg>
                <p class="text-sm font-medium">Selecciona una conversación</p>
            </div>

        </div>
    </div>
</x-admin-layout>