@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Notificaciones',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-black text-gray-800">Centro de Notificaciones</h2>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <form action="{{ route('doctor.notifications.readAll') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm font-bold text-blue-600 hover:text-blue-800 underline">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <div class="divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <div class="p-6 flex items-start justify-between gap-4 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/50' }}">
                        <div class="flex gap-4">
                            <!-- Icono dinámico según el tipo -->
                            <div class="mt-1 p-2 rounded-xl {{ $notification->read_at ? 'bg-gray-100 text-gray-400' : 'bg-blue-100 text-blue-600' }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            
                            <div>
                                <p class="{{ $notification->read_at ? 'text-gray-600' : 'text-gray-900 font-bold' }}">
                                    {{ $notification->data['message'] }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }} • 
                                    Servicio: {{ $notification->data['service'] }}
                                </p>
                            </div>
                        </div>

                        @if(!$notification->read_at)
                            <form action="{{ route('doctor.notifications.read', $notification->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 transition" title="Marcar como leída">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="p-10 text-center text-gray-400 italic">
                        No hay notificaciones para mostrar.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</x-admin-layout>
