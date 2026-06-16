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
    <div class="max-w-5xl mx-auto py-10 px-4 sm:px-6">
        
        <!-- Encabezado Principal -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Centro de Notificaciones</h2>
                <p class="text-sm text-gray-500 mt-1">Mantente al tanto de los cambios en tus citas médicas y agenda.</p>
            </div>
            
            <!-- Conteo optimizado a nivel de base de datos -->
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <form action="{{ route('notifications.readAll') }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 text-sm font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>

        <!-- Contenedor Principal de Alertas -->
        <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-200/80">
            <div class="divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <!-- Formulario contenedor para que toda la fila sea clicable hacia la cita médica -->
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                        @csrf 
                        @method('PATCH')
                        
                        <button type="submit" class="w-full text-left p-5 flex items-start justify-between gap-4 transition duration-150 {{ $notification->read_at ? 'bg-white hover:bg-gray-50/70' : 'bg-blue-50/40 hover:bg-blue-50/80' }}">
                            
                            <div class="flex gap-4">
                                <!-- Icono de Calendario Estilizado -->
                                <div class="mt-0.5 p-2.5 rounded-xl shrink-0 {{ $notification->read_at ? 'bg-gray-100 text-gray-500' : 'bg-blue-100 text-blue-600' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                                    </svg>
                                </div>
                                
                                <div>
                                    <!-- Título dinámico interno -->
                                    <span class="text-xs font-semibold uppercase tracking-wider {{ $notification->read_at ? 'text-gray-400' : 'text-blue-600' }}">
                                        {{ $notification->data['title'] ?? 'Actualización de Cita' }}
                                    </span>
                                    
                                    <!-- Mensaje descriptivo -->
                                    <p class="text-sm mt-0.5 leading-relaxed {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900 font-semibold' }}">
                                        {{ $notification->data['message'] }}
                                    </p>
                                    
                                    <!-- Metadatos (Blindado contra errores de índices inexistentes) -->
                                    <div class="flex items-center gap-2 text-xs text-gray-400 mt-1.5">
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                        @if(isset($notification->data['service']))
                                            <span>•</span>
                                            <span class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-md">Servicio: {{ $notification->data['service'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Indicador visual / Botón derecho de Check -->
                            <div class="shrink-0 self-center">
                                @if(!$notification->read_at)
                                    <div class="p-1 text-blue-500 bg-blue-50 rounded-full" title="No leída">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="5"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="p-1 text-gray-300" title="Leída">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                        </button>
                    </form>
                @empty
                    <!-- Estado de bandeja vacía estilizado -->
                    <div class="p-12 text-center bg-white">
                        <div class="inline-flex p-4 bg-gray-50 text-gray-400 rounded-full mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                            </svg>
                        </div>
                        <p class="text-base font-medium text-gray-900">Bandeja de entrada limpia</p>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">No tienes ninguna notificación pendiente de revisar en este momento.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Paginación Estilizada nativa de Tailwind -->
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</x-admin-layout>
