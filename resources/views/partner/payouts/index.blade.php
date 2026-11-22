@php
$breadcrumbs = [
    ['name' => 'Mis liquidaciones'],
];
$money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">{{ session('success') }}</div>
        @endif

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Mis liquidaciones</h2>
            <p class="text-sm text-gray-500">Cada viernes transferimos lo de las citas pagadas en línea que ya se realizaron hasta el jueves anterior. Recibes el 100 % del valor de tu consulta; la comisión la paga el paciente.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-[11px] font-bold text-amber-700 uppercase">Por liquidar</p>
                <p class="text-xl font-black text-amber-800">{{ $money($pendingTotal) }}</p>
                <p class="text-xs text-amber-700">{{ $pending->count() }} cita(s)</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                <p class="text-[11px] font-bold text-emerald-700 uppercase">Liquidado este año</p>
                <p class="text-xl font-black text-emerald-800">{{ $money($paidYear) }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase">Próximo pago</p>
                <p class="text-xl font-black text-gray-800">{{ $nextPayDate->format('d/m/Y') }}</p>
                <p class="text-xs text-gray-500">Citas realizadas hasta el {{ $nextCutoff->format('d/m/Y') }}</p>
            </div>
        </div>

        <!-- Cuenta para recibir los pagos -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8" x-data="{ editing: {{ ($account && !$errors->any()) ? 'false' : 'true' }} }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-700">Cuenta para recibir tus pagos</h3>
                    @if($account)
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $account->bank_name }} · {{ $account->typeLabel() }} · <span class="font-mono">{{ $account->masked() }}</span>
                            <span class="text-gray-400">· Titular: {{ $account->account_holder_name }}</span>
                        </p>
                        <p class="text-xs mt-1 {{ $account->isVerified() ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $account->isVerified() ? 'Cuenta verificada' : 'Pendiente de verificación: la verificamos antes de tu primera transferencia.' }}
                        </p>
                    @else
                        <p class="text-sm text-amber-700 mt-1">Aún no registras una cuenta. La necesitamos para transferirte.</p>
                    @endif
                </div>
                @if($account)
                    <button type="button" @click="editing = !editing" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" x-text="editing ? 'Cerrar' : 'Cambiar cuenta'"></button>
                @endif
            </div>

            <form method="POST" action="{{ route($prefix . 'bank') }}" x-show="editing" x-cloak class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                @method('PUT')
                @if($errors->any())
                    <div class="md:col-span-2 p-3 text-xs text-red-800 rounded-lg bg-red-50 border border-red-200">
                        @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Banco</label>
                    <input type="text" name="bank_name" list="bancos" required maxlength="80" value="{{ old('bank_name', $account?->bank_name) }}" class="w-full rounded-lg border-gray-300 text-sm">
                    <datalist id="bancos">
                        @foreach($banks as $b)<option value="{{ $b }}">@endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Tipo de cuenta</label>
                    <select name="account_type" required class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="savings" {{ old('account_type', $account?->account_type) === 'savings' ? 'selected' : '' }}>Ahorros (incluye Nequi y Daviplata)</option>
                        <option value="checking" {{ old('account_type', $account?->account_type) === 'checking' ? 'selected' : '' }}>Corriente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Número de cuenta</label>
                    <input type="text" name="account_number" inputmode="numeric" autocomplete="off" {{ $account ? '' : 'required' }} placeholder="{{ $account ? 'Déjalo vacío para conservar ' . $account->masked() : 'Solo números' }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Nombre del titular</label>
                    <input type="text" name="account_holder_name" required maxlength="120" value="{{ old('account_holder_name', $account?->account_holder_name) }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Tipo de documento del titular</label>
                    <select name="id_type" required class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach(['CC' => 'Cédula de ciudadanía', 'NIT' => 'NIT', 'CE' => 'Cédula de extranjería'] as $k => $label)
                            <option value="{{ $k }}" {{ old('id_type', $account?->id_type ?? 'CC') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Número de documento del titular</label>
                    <input type="text" name="account_holder_id" inputmode="numeric" required maxlength="20" value="{{ old('account_holder_id', $account?->account_holder_id) }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">La cuenta debe estar a tu nombre o al de tu clínica. Si el documento del titular no coincide con el tuyo, lo revisaremos antes de transferir.</p>
                    <button type="submit" class="text-sm font-semibold px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-gray-700">Guardar cuenta</button>
                </div>
            </form>
        </div>

        @if($pending->isNotEmpty())
            <h3 class="text-sm font-bold text-gray-700 mb-2">Citas pendientes de liquidar</h3>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto mb-8">
                <table class="w-full text-xs text-left text-gray-600">
                    <thead class="text-gray-500 uppercase bg-gray-50">
                        <tr><th class="px-3 py-2">Cita</th><th class="px-3 py-2">Fecha</th><th class="px-3 py-2">Modalidad</th><th class="px-3 py-2">Sede</th><th class="px-3 py-2 text-right">Valor</th><th class="px-3 py-2">Estado</th></tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $p)
                            @php
                                $a = $p->appointment;
                                $virtual = ($a?->service?->type) === 'virtual';
                                $inBatch = $p->batch && in_array($p->batch->status, ['approved'], true);
                            @endphp
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2">#{{ $p->appointment_id }}</td>
                                <td class="px-3 py-2">{{ $a?->date ? \Carbon\Carbon::parse($a->date)->format('d/m/Y') : '—' }}</td>
                                <td class="px-3 py-2">{{ $virtual ? 'Virtual' : 'Presencial' }}</td>
                                <td class="px-3 py-2">{{ $virtual ? 'Virtual' : ($a?->address?->name ?? '—') }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $money($p->amount_to_pay) }}</td>
                                <td class="px-3 py-2">{{ $inBatch ? 'En el lote de este viernes' : 'En espera del próximo corte' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h3 class="text-sm font-bold text-gray-700 mb-2">Soportes de pago</h3>
        <div class="space-y-3">
            @forelse($reports as $r)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-bold text-gray-900">{{ $r->number }}</p>
                        <p class="text-xs text-gray-500">
                            Pagado el {{ $r->paid_at?->timezone('America/Bogota')->format('d/m/Y') }} · {{ $r->lines_count }} cita(s) · Ref. {{ $r->transfer_reference }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-lg font-black text-emerald-700">{{ $money($r->total_amount) }}</p>
                        @if($r->pdf_path)
                            <a href="{{ route($prefix . 'download', $r) }}" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">Descargar PDF</a>
                        @else
                            <span class="text-xs text-gray-400">Generando…</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">Todavía no tienes liquidaciones pagadas.</div>
            @endforelse
            <div class="mt-4">{{ $reports->links() }}</div>
        </div>
    </div>
</x-admin-layout>
