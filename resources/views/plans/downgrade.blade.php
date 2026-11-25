<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-10">
            <h2 class="text-2xl font-black text-gray-800 mb-2">Pasar al {{ $plan->name }}</h2>
            <p class="text-sm text-gray-600 mb-6">Revisa lo que cambia antes de confirmar.</p>

            <ul class="space-y-3 text-sm text-gray-700 mb-8">
                <li class="flex gap-2"><span>•</span><span>El {{ $plan->name }} permite <strong>{{ $plan->max_addresses }}</strong> {{ Str::plural('sede', (int) $plan->max_addresses) }} y <strong>{{ $plan->max_services }}</strong> servicios en total.</span></li>
                @if ($preview['addresses_off'] > 0)
                    <li class="flex gap-2"><span>•</span><span>Tienes {{ $preview['addresses_total'] }} {{ Str::plural('sede activa', $preview['addresses_total']) }}: <strong>{{ $preview['addresses_off'] }}</strong> {{ $preview['addresses_off'] === 1 ? 'se desactivará' : 'se desactivarán' }} (se conservan las más antiguas hasta el límite). No se borra nada y podrás activarlas de nuevo con un plan mayor.</span></li>
                @endif
                @if ($preview['services_total'] > $preview['services_limit'])
                    <li class="flex gap-2"><span>•</span><span>Tienes {{ $preview['services_total'] }} servicios y el plan permite {{ $preview['services_limit'] }}. No podrás agregar más hasta quedar por debajo del límite.</span></li>
                @endif
                <li class="flex gap-2"><span>•</span><span>Podrás volver a un plan de pago cuando quieras.</span></li>
            </ul>

            <form method="POST" action="{{ route('partner.profile.plan.update') }}" class="flex flex-wrap gap-3">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan->plan }}">
                <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded-2xl font-black hover:bg-gray-900 transition">Confirmar cambio</button>
                <a href="{{ url('/partner/profile') }}" class="px-6 py-3 rounded-2xl font-black text-gray-600 border border-gray-200 hover:border-gray-300 transition">Cancelar</a>
            </form>
        </div>
    </div>
</x-guest-layout>
