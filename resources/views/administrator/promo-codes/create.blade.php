@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Promo Codes - Create',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6">
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Crear Código de Promoción</h2>
                    <a href="{{ route('administrator.promo-codes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver al listado</a>
                </div>

                <form action="{{ route('administrator.promo-codes.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Código -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Código de Cupón</label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" placeholder="Ej: BIENVENIDA20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Tipo de descuento -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Tipo de Descuento</label>
                            <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Valor Fijo ($)</option>
                                <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
                            </select>
                            @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Reward / Descuento -->
                        <div>
                            <label for="reward" class="block text-sm font-medium text-gray-700">Valor de Descuento</label>
                            <input type="number" step="0.01" name="reward" id="reward" value="{{ old('reward') }}" placeholder="Ej: 15.00" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            @error('reward') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Máximo de usos -->
                        <div>
                            <label for="max_uses" class="block text-sm font-medium text-gray-700">Máximo de Usos (Vacío para ilimitado)</label>
                            <input type="number" name="max_uses" id="max_uses" value="{{ old('max_uses') }}" placeholder="Ej: 100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            @error('max_uses') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Fecha de inicio -->
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700">Fecha de Inicio</label>
                            <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Fecha de Expiración -->
                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700">Fecha de Expiración</label>
                            <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            @error('expires_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Activo Checkbox -->
                    <div class="flex items-start mt-4">
                        <div class="flex items-center h-5">
                            <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_active" class="font-medium text-gray-700">Activar cupón inmediatamente</label>
                            <p class="text-gray-500">Permite que el cupón pueda ser procesado por la plataforma tras guardarse.</p>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('administrator.promo-codes.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200 text-sm font-medium">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium shadow-sm">
                            Crear Código
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>