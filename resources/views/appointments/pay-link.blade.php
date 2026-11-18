@php
    $date = \Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
    $time = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');
    $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');
@endphp
<x-guest-layout>
    <div class="max-w-xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-6 sm:p-8">

            @if(session('error'))
                <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-bold">{{ session('error') }}</div>
            @endif

            @if($state === 'paid')
                <div class="text-center space-y-3">
                    <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">Esta cita ya está pagada</h1>
                    <p class="text-sm text-slate-500">Te enviamos los detalles a tu correo. ¡Nos vemos pronto!</p>
                </div>
            @elseif($state === 'expired')
                <div class="text-center space-y-3">
                    <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">Este enlace ya no está disponible</h1>
                    <p class="text-sm text-slate-500">El tiempo para pagar terminó y el turno fue liberado. Comunícate con tu especialista para agendar un nuevo horario.</p>
                </div>
            @else
                <div x-data="{
                        code: '', applied: '', discount: 0, loading: false, msg: '', err: '', submitting: false,
                        base: {{ (float) $quote['base'] }}, commission: {{ (float) $quote['commission_amount'] }},
                        total: {{ (float) $quote['total'] }}, originalTotal: {{ (float) $quote['total'] }},
                        fmt(v) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v); },
                        async apply() {
                            if (!this.code.trim()) return;
                            this.loading = true; this.err = ''; this.msg = '';
                            try {
                                const r = await fetch('{{ route('appointments.pay-link.promo', $token) }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ code: this.code })
                                });
                                const d = await r.json();
                                if (r.ok && d.success) {
                                    this.applied = d.code; this.discount = d.discount; this.total = d.total;
                                    this.msg = '¡Cupón ' + d.code + ' aplicado!';
                                } else { this.err = d.message || 'Código inválido'; }
                            } catch (e) { this.err = 'No pudimos validar el código. Inténtalo de nuevo.'; }
                            this.loading = false;
                        }
                    }" class="space-y-5">

                    <div class="text-center">
                        <h1 class="text-2xl font-black text-slate-800">Paga tu cita médica</h1>
                        <p class="text-sm text-slate-500 mt-1">Confirma tu turno con Dr(a). {{ $appointment->doctor?->user?->name }}</p>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-400 font-bold uppercase text-[11px]">Servicio</span><span class="font-bold text-slate-800 text-right">{{ $appointment->service?->name }} · {{ $isVirtual ? 'Virtual' : 'Presencial' }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400 font-bold uppercase text-[11px]">Fecha</span><span class="font-bold text-slate-800 text-right">{{ ucfirst($date) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400 font-bold uppercase text-[11px]">Hora</span><span class="font-bold text-slate-800">{{ $time }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400 font-bold uppercase text-[11px]">Referencia</span><span class="font-mono font-bold text-slate-800">{{ $appointment->reference }}</span></div>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Valor de la consulta</span><span class="font-bold" x-text="fmt(base)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Comisión plataforma</span><span class="font-bold" x-text="fmt(commission)"></span></div>
                        <div class="flex justify-between text-green-600 font-bold" x-show="discount > 0" x-cloak><span x-text="'Código ' + applied"></span><span x-text="'-' + fmt(discount)"></span></div>
                        <div class="flex justify-between border-t border-blue-200 pt-2 mt-2"><span class="font-black text-slate-800">Total a pagar</span><span class="font-black text-blue-700 text-lg" x-text="fmt(total)"></span></div>
                    </div>

                    <div x-show="!applied" x-cloak>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-2">¿Tienes un código de descuento?</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="code" placeholder="EJ: DOCTOR10" class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-slate-200 text-sm uppercase font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="apply()" :disabled="!code.trim() || loading" class="px-5 py-3 text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40">
                                <span x-show="!loading">Aplicar</span><span x-show="loading" x-cloak>...</span>
                            </button>
                        </div>
                        <p x-show="err" x-text="err" class="mt-2 text-xs font-bold text-red-600" x-cloak></p>
                    </div>
                    <p x-show="msg" x-text="msg" class="text-xs font-bold text-emerald-600" x-cloak></p>

                    <form action="{{ route('appointments.pay-link.start', $token) }}" method="POST" @submit="submitting = true">
                        @csrf
                        <input type="hidden" name="promo_code" :value="applied">
                        <button type="submit" :disabled="submitting"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-wider shadow-lg shadow-blue-100 transition">
                            <span x-show="!submitting" x-text="total === 0 ? 'Confirmar cita sin costo' : 'Pagar ahora'"></span>
                            <span x-show="submitting" x-cloak>Procesando...</span>
                        </button>
                    </form>

                    @if($appointment->payment_expires_at)
                        <p class="text-center text-xs font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl p-3">
                            Tu turno está reservado hasta las {{ $appointment->payment_expires_at->timezone('America/Bogota')->format('g:i A') }} del {{ $appointment->payment_expires_at->timezone('America/Bogota')->format('d/m/Y') }}.
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-guest-layout>
