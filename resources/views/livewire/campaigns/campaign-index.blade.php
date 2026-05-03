@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis Landings',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Mis Landings</h2>
            <x-button wire:navigate href="{{ route('campaigns.create') }}">
                + Nueva Campaña
            </x-button>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Enlace</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($campaigns as $campaign)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $campaign->title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600">                    
                        <div x-data="{ 
                                copyText: '{{ route('landing.public', [auth()->user()->doctor->slug, $campaign->slug]) }}',
                                showMsg: false 
                            }" 
                            class="flex items-center space-x-2">                                            

                            <!-- Input de solo lectura con el link -->
                            <input type="text" 
                                x-model="copyText" 
                                readonly 
                                class="text-xs border-gray-200 rounded bg-gray-50 text-gray-600 w-48">

                            <!-- Botón para ver la página (Ojo) -->
                            <a :href="copyText" target="_blank" title="Visitar landing" class="text-gray-400 hover:text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            
                            <!-- Botón de Copiar -->
                            <button @click="
                                    navigator.clipboard.writeText(copyText); 
                                    showMsg = true; 
                                    setTimeout(() => showMsg = false, 2000)
                                " 
                                class="text-indigo-600 hover:text-indigo-900 font-bold flex items-center">
                                
                                <span x-show="!showMsg">📋 Copiar</span>
                                <span x-show="showMsg" class="text-green-600">✅ ¡Copiado!</span>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button wire:click="toggleStatus({{ $campaign->id }})" 
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campaign->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $campaign->is_active ? 'Activa' : 'Pausada' }}
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="delete({{ $campaign->id }})" 
                            wire:confirm="¿Estás seguro de eliminar esta landing?"
                            class="text-red-600 hover:text-red-900 ml-4">Eliminar</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
