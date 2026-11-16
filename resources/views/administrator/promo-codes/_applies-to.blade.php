{{--
    Selector de módulos donde sirve el código promocional.
    Uso: @include('administrator.promo-codes._applies-to', ['selected' => old('applies_to', $promoCode->applies_to ?? [])])
--}}
@php $selected = (array) ($selected ?? []); @endphp

<div class="md:col-span-2">
    <span class="block text-sm font-medium text-gray-700">Aplica a (módulos donde sirve el código)</span>

    <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
        @foreach (\App\Models\PromoCode::MODULE_LABELS as $value => $label)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="applies_to[]" value="{{ $value }}"
                       @checked(in_array($value, $selected, true))
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                {{ $label }}
            </label>
        @endforeach
    </div>

    <p class="text-gray-500 text-xs mt-1">Un código solo se puede canjear en los módulos marcados. Debes elegir al menos uno.</p>

    @error('applies_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    @error('applies_to.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>