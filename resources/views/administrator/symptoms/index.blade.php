@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Url dinamicas',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">
        <!-- Encabezado con estadísticas -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    URLs de SEO Programático (Síntomas IA)
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Monitoreo de páginas de aterrizaje automáticas generadas para Google.
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    Total generado: {{ $totalSymptoms }} URLs
                </span>
            </div>
        </div>

        <!-- Tabla de Contenido -->
        <div class="flex flex-col mt-4">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg bg-white">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Síntoma / Consulta Original
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Especialidad Sugerida
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Enlace Público (SEO)
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Creación
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($symptoms as $symptom)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <!-- Síntoma -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ $symptom->original_query }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Slug: {{ $symptom->slug }}
                                            </div>
                                        </td>

                                        <!-- Especialidad -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                                {{ $symptom->specialty_suggested ?? 'No asignada' }}
                                            </span>
                                        </td>

                                        <!-- URL Destino con enlace real -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="{{ route('symptom.seo.view', ['slug' => $symptom->slug]) }}" 
                                               target="_blank" 
                                               class="text-blue-600 hover:text-blue-900 hover:underline flex items-center gap-1 font-mono text-xs">
                                                /sintomas/{{ $symptom->slug }}
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        </td>

                                        <!-- Fecha -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                            {{ $symptom->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                            No se han generado URLs dinámicas mediante la IA todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque de Paginación de Laravel -->
        <div class="mt-6">
            {{ $symptoms->links() }}
        </div>
    </div>
</x-admin-layout>
