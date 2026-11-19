<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="sm:px-6 lg:px-10">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded shadow-sm">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded shadow-sm">{{ session('error') }}</div>
            @endif
            @if (!$owner['can_create'])
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded shadow-sm">{{ $owner['reason'] }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-2xl font-bold text-gray-800">Códigos promocionales</h2>
                        @if ($owner['can_create'])
                            <a href="{{ route('partner.promo-codes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">+ Nuevo código</a>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mb-6">
                        El descuento lo asumes tú (máximo {{ rtrim(rtrim(number_format($maxPercent, 2), '0'), '.') }} % del valor de la cita). OpenDoctor mantiene su comisión. Solo aplican a citas.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-b border-gray-200 text-gray-600 text-sm font-semibold uppercase tracking-wider">
                                    <th class="p-4">Código</th>
                                    <th class="p-4">Descuento</th>
                                    <th class="p-4">Usos (Uso / Máx)</th>
                                    <th class="p-4">Vigencia</th>
                                    <th class="p-4">Estado</th>
                                    <th class="p-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm">
                                @forelse ($promoCodes as $code)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4 font-mono font-bold text-indigo-600">{{ $code->code }}</td>
                                        <td class="p-4 font-semibold">
                                            {{ $code->type === 'fixed' ? '$' . number_format($code->reward, 0, ',', '.') : rtrim(rtrim(number_format($code->reward, 2), '0'), '.') . '%' }}
                                        </td>
                                        <td class="p-4 text-gray-600">{{ $code->uses }} / {{ $code->max_uses ?? '∞' }}</td>
                                        <td class="p-4 text-gray-600 text-xs">
                                            {{ $code->starts_at ? 'Desde ' . $code->starts_at->format('d/m/Y H:i') : 'Sin inicio' }}<br>
                                            {{ $code->expires_at ? 'Hasta ' . $code->expires_at->format('d/m/Y H:i') : 'Sin vencimiento' }}
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $code->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $code->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right space-x-2" x-data="{ openDelete: false }">
                                            @if ($owner['can_create'])
                                                <a href="{{ route('partner.promo-codes.edit', $code) }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-900">Editar</a>

                                                @if ($code->uses == 0)
                                                    <button @click="openDelete = true" class="text-red-600 hover:text-red-900 font-medium ml-2">Eliminar</button>

                                                    <div x-show="openDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-cloak>
                                                        <div @click.away="openDelete = false" class="bg-white rounded-lg max-w-md w-full p-6 text-left shadow-xl">
                                                            <h3 class="text-lg font-bold text-gray-900">¿Confirmar eliminación?</h3>
                                                            <p class="text-sm text-gray-500 mt-2">¿Eliminar el código <span class="font-bold">{{ $code->code }}</span>? Esta acción no se puede deshacer.</p>
                                                            <div class="mt-6 flex justify-end space-x-3">
                                                                <button @click="openDelete = false" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                                                                <form action="{{ route('partner.promo-codes.destroy', $code) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Eliminar</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400 ml-2" title="Ya se usó: desactívalo en lugar de eliminarlo">Ya usado</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="p-8 text-center text-gray-500">Aún no has creado códigos promocionales.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">{{ $promoCodes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
