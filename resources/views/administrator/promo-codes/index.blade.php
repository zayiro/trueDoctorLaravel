@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Promo Codes',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Alerta de éxito -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <!-- Busca el título h2 en index.blade.php y reemplázalo por este bloque flex -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Códigos de Promoción</h2>
                        <a href="{{ route('administrator.promo-codes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nuevo Código
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-b border-gray-200 text-gray-600 text-sm font-semibold uppercase tracking-wider">
                                    <th class="p-4">Código</th>
                                    <th class="p-4">Tipo</th>
                                    <th class="p-4">Descuento</th>
                                    <th class="p-4">Usos (Uso / Máx)</th>
                                    <th class="p-4">Estado</th>
                                    <th class="p-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm">
                                @foreach ($promoCodes as $code)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4 font-mono font-bold text-indigo-600">{{ $code->code }}</td>
                                        <td class="p-4 capitalize">{{ $code->type === 'fixed' ? 'Fijo' : 'Porcentaje' }}</td>
                                        <td class="p-4 font-semibold">
                                            {{ $code->type === 'fixed' ? '$' . number_format($code->reward, 2) : number_format($code->reward, 0) . '%' }}
                                        </td>
                                        <td class="p-4 text-gray-600">
                                            {{ $code->uses }} / {{ $code->max_uses ?? '∞' }}
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $code->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $code->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <!-- Celda de acciones con Alpine.js para confirmación de borrado -->
                                        <td class="p-4 text-right space-x-2" x-data="{ openDelete: false }">
                                            <a href="{{ route('administrator.promo-codes.edit', $code) }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                                Editar
                                            </a>

                                            <!-- Botón Eliminar -->
                                            <button @click="openDelete = true" class="text-red-600 hover:text-red-900 font-medium ml-2">
                                                Eliminar
                                            </button>

                                            <!-- Modal de confirmación Alpine.js -->
                                            <div x-show="openDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-cloak>
                                                <div @click.away="openDelete = false" class="bg-white rounded-lg max-w-md w-full p-6 text-left shadow-xl">
                                                    <h3 class="text-lg font-bold text-gray-900">¿Confirmar eliminación?</h3>
                                                    <p class="text-sm text-gray-500 mt-2">¿Estás seguro de que deseas eliminar el código <span class="font-bold">{{ $code->code }}</span>? Esta acción no se puede deshacer.</p>
                                                    <div class="mt-6 flex justify-end space-x-3">
                                                        <button @click="openDelete = false" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                                                            Cancelar
                                                        </button>
                                                        <form action="{{ route('administrator.promo-codes.destroy', $code) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                                                Eliminar de todos modos
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación de Laravel -->
                    <div class="mt-6">
                        {{ $promoCodes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>