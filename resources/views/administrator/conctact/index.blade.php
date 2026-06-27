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
    <div class="max-w-7xl mx-auto py-8 space-y-6">
        
        <!-- Alertas de Sistema -->
        @if (session('success'))
            <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                <span class="font-semibold">Error:</span> {{ session('error') }}
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                            <th class="p-4">Nombre</th>
                            <th class="p-4">Email</th>
                            <th rowspan="2" class="p-4">Mensaje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($contactMessages as $contactMessage)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <!-- Información del contacto -->
                                <td class="p-4">
                                    <div class="font-semibold text-slate-900">{{ $contactMessage->name }}</div>
                                </td>    
                                <td class="p-4">
                                    <div class="font-semibold text-slate-900">{{ $contactMessage->email }}</div>
                                </td> 
                                <td rowspan="2" class="p-4">
                                    <div class="font-semibold text-slate-900">{{ $contactMessage->subject }}</div>
                                    <div class="font-semibold text-slate-900">{{ $contactMessage->message }}</div>
                                </td> 
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center bg-slate-50/30">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-100 text-slate-400 rounded-full mt-3 mb-3 text-xl">🔍</div>
                                    <div class="text-sm font-semibold text-slate-900">Sin coincidencias</div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto mb-3">No se encontrarón mensajes de contacto.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
