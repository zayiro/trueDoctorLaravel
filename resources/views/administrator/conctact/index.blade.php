@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Contáctanos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6">
        
        <!-- Alertas de Sistema -->
        @if (session('success'))
            <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm mb-3">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm mb-3">
                <span class="font-semibold">Error:</span> {{ session('error') }}
            </div>
        @endif

        <!-- Encabezado Principal -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Mensajes de Contacto</h2>
                <p class="text-sm text-gray-500 mt-1">Mantente al tanto de los mensajes de los usuarios.</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <!-- Contenedor Principal de Alertas -->
                <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-200/80">
                    <div class="divide-y divide-gray-100">
                        @forelse ($contactMessages as $contactRecord)
                           <div class="relative group">                                  
                                    <div class="font-semibold text-slate-900">{{ $contactRecord->name }}</div>
                                    <div class="text-slate-500 text-xs">{{ $contactRecord->email }}</div>
                                    <div class="font-semibold text-slate-900 mt-2">{{ $contactRecord->subject }}</div>
                                    <p class="text-slate-700 mt-1 whitespace-pre-line">{{ $contactRecord->message }}</p>
                                    <div class="font-normal text-slate-400 text-xs mt-2">
                                        {{ \Carbon\Carbon::parse($contactRecord->created_at)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] H:mm A') }}
                                    </div>
                                </td>

                                <!-- Botón de Eliminar con Alerta de Confirmación (Aceptar / Cancelar) -->
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 z-10">
                                    <form action="{{ route('administrator.contact.destroy', $contactRecord->id) }}" method="POST"
                                        onsubmit="return confirm('¿Estás seguro de que deseas eliminar este mensaje?')">
                                        @csrf
                                        @method('DELETE')
                                        <!-- Tu botón de Tailwind adentro se mantiene igual -->
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all duration-200"
                                                title="Eliminar mensaje">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>    
                        @empty
                            <!-- Estado de bandeja vacía estilizado -->
                            <div class="p-12 text-center bg-white">
                                <div class="inline-flex p-4 bg-gray-50 text-gray-400 rounded-full mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                                    </svg>
                                </div>
                                <p class="text-base font-medium text-gray-900">Bandeja de entrada limpia</p>
                                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">No tienes ningún mensaje pendiente de revisar en este momento.</p>
                            </div>
                        @endforelse
                    </div>
                </div>        
            </div>
        </div>


        <!-- Paginación con soporte Query String -->
        @if($contactMessages->hasPages())
            <div class="mt-4">
                {{ $contactMessages->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
