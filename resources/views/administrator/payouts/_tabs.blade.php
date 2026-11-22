<div class="flex gap-2 mb-6 border-b border-gray-200">
    <a href="{{ route('administrator.payouts.index') }}"
       class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ request()->routeIs('administrator.payouts.index') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Por cita</a>
    <a href="{{ route('administrator.payouts.batches.index') }}"
       class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ request()->routeIs('administrator.payouts.batches.*') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Lotes de pago</a>
</div>
