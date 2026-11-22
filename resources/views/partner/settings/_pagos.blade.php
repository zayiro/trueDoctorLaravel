@php
    $owner    = $owner ?? null;
    $mode     = old('presential_payment_mode', $settings->presential_payment_mode ?? 'onsite');
    $presRate = $owner ? \App\Services\Payments\CommissionRates::forOwner($owner, 'presential') : null;
    $virtRate = $owner ? \App\Services\Payments\CommissionRates::forOwner($owner, 'virtual') : null;
    $accepted = ($owner && $presRate !== null) ? \App\Models\CommissionAcceptance::isAcceptedFor($owner, $presRate) : false;
    $lastAcceptance = $owner ? \App\Models\CommissionAcceptance::latestFor($owner) : null;
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
    $virtTxt = $virtRate !== null ? ', con una comisión de servicio de <strong>' . $fmt($virtRate) . ' %</strong> que paga el paciente' : '';
    $accTxt  = $lastAcceptance ? ' el ' . $lastAcceptance->accepted_at->timezone('America/Bogota')->format('d/m/Y') : '';
    $options = [
        'onsite' => ['Solo pago en sede', 'El paciente paga directamente en la consulta. No hay comisión.'],
        'choice' => ['El paciente elige', 'Verá dos opciones al reservar: pagar en línea o pagar en sede. Recomendado.'],
        'online' => ['Pago en línea obligatorio', 'El paciente debe pagar en línea para conservar su turno.'],
    ];
@endphp

<!-- SECCIÓN 1: Pagos -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200"
     x-data="{ mode: @json($mode), accepted: @json($accepted) }">
    <h2 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">💳 Pagos y Facturación</h2>
    <p class="text-sm text-gray-500 mb-4">
        Las citas virtuales siempre se pagan en línea{!! $virtTxt !!}.
        Aquí decides cómo se pagan tus citas <strong>presenciales</strong>.
    </p>

    <input type="hidden" name="currency" value="{{ $settings->currency ?: 'COP' }}">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ($options as $value => [$title, $help])
            <label class="relative flex flex-col gap-1 p-4 rounded-xl border cursor-pointer transition"
                   :class="mode === '{{ $value }}' ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="presential_payment_mode" value="{{ $value }}" x-model="mode" class="sr-only">
                <span class="text-sm font-bold text-gray-800">{{ $title }}</span>
                <span class="text-xs text-gray-500">{{ $help }}</span>
            </label>
        @endforeach
    </div>

    <div x-show="mode !== 'onsite'" x-transition x-cloak class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-slate-600">
        @if ($presRate !== null)
            <p>
                Cuando el paciente paga en línea, él paga el valor de tu consulta <strong>más una comisión de servicio de {{ $fmt($presRate) }} %</strong>.
                Tú recibes el <strong>100 %</strong> del valor de tu consulta, y liquidamos tus pagos en línea de forma periódica.
                Si el paciente paga en sede, no hay comisión.
            </p>

            @if ($accepted)
                <p class="mt-3 text-xs text-emerald-700 font-semibold">
                    ✓ Ya aceptaste esta comisión{{ $accTxt }}.
                </p>
            @else
                <label class="mt-3 flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="presential_terms_accepted" value="1"
                           :required="mode !== 'onsite' && !accepted"
                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs text-slate-600">
                        Entiendo y acepto que, en mis citas presenciales pagadas en línea, el paciente paga una comisión de servicio de
                        {{ $fmt($presRate) }} % adicional al valor de mi consulta, y que yo recibo el 100 % de ese valor.
                    </span>
                </label>
            @endif
        @else
            <p>Cuando el paciente paga en línea, él paga el valor de tu consulta más una comisión de servicio. Tú recibes el 100 % del valor de tu consulta.</p>
        @endif
    </div>
</div>

