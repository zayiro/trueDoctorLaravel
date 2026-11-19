{{-- Campos compartidos. Variables: $promoCode (opcional), $maxPercent --}}
@php $c = $promoCode ?? null; @endphp

<div class="rounded-md bg-blue-50 border border-blue-100 p-4 text-sm text-blue-800">
    <strong>Cómo funciona:</strong> el descuento lo asumes tú. Sale de lo que recibes por la cita; OpenDoctor mantiene su comisión
    completa y el paciente siempre paga al menos esa comisión. El descuento máximo es del {{ rtrim(rtrim(number_format($maxPercent, 2), '0'), '.') }} % del valor de la cita.
    El código solo aplica a citas {{ ($owner['type'] ?? '') === 'clinic' ? 'de tu clínica' : 'de tu consulta particular' }}.
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="code" class="block text-sm font-medium text-gray-700">Código</label>
        <input type="text" name="code" id="code" value="{{ old('code', $c?->code) }}" maxlength="30" placeholder="Ej: PRIMERACITA10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 uppercase">
        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-gray-700">Tipo de descuento</label>
        <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
            <option value="percent" {{ old('type', $c?->type ?? 'percent') === 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
            <option value="fixed" {{ old('type', $c?->type) === 'fixed' ? 'selected' : '' }}>Valor fijo ($)</option>
        </select>
        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="reward" class="block text-sm font-medium text-gray-700">Valor del descuento</label>
        <input type="number" step="0.01" min="0" name="reward" id="reward" value="{{ old('reward', $c ? (float) $c->reward : null) }}" placeholder="Ej: 10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
        <p class="text-gray-500 text-xs mt-1">Porcentaje: hasta {{ rtrim(rtrim(number_format($maxPercent, 2), '0'), '.') }}. Valor fijo: en pesos; nunca superará el {{ rtrim(rtrim(number_format($maxPercent, 2), '0'), '.') }} % de la cita.</p>
        @error('reward') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="max_uses" class="block text-sm font-medium text-gray-700">Máximo de usos (vacío = ilimitado)</label>
        <input type="number" min="1" name="max_uses" id="max_uses" value="{{ old('max_uses', $c?->max_uses) }}" placeholder="Ej: 20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
        @error('max_uses') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="starts_at" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
        <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at', $c?->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
        @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="expires_at" class="block text-sm font-medium text-gray-700">Fecha de expiración</label>
        <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at', $c?->expires_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
        @error('expires_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="flex items-start mt-4">
    <div class="flex items-center h-5">
        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $c ? $c->is_active : true) ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
    </div>
    <div class="ml-3 text-sm">
        <label for="is_active" class="font-medium text-gray-700">Código activo</label>
        <p class="text-gray-500">Si lo desactivas, los pacientes ya no podrán usarlo.</p>
    </div>
</div>
