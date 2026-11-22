@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Liquidaciones', 'href' => route('administrator.payouts.index')],
    ['name' => 'Lotes de pago', 'href' => route('administrator.payouts.batches.index')],
    ['name' => $batch->number],
];
$money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
$colors = ['draft' => 'bg-gray-100 text-gray-700', 'approved' => 'bg-blue-100 text-blue-700', 'paid' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-red-100 text-red-700'];
$eventLabels = ['created' => 'Lote creado', 'line_released' => 'Línea excluida', 'line_auto_removed' => 'Línea retirada al aprobar', 'approved' => 'Lote aprobado', 'group_paid' => 'Transferencia registrada', 'completed' => 'Lote completado', 'cancelled' => 'Lote cancelado'];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">
        @if(session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">{{ session('success') }}</div>
        @endif
        @if(session('revealed'))
            <div class="p-3 mb-3 text-xs text-amber-800 rounded-xl bg-amber-50 border border-amber-200">Viste un número de cuenta completo; quedó registrado a tu nombre. No lo compartas por chat ni correo.</div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">{{ session('error') }}</div>
        @endif

        @include('administrator.payouts._tabs')

        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-bold text-gray-800">{{ $batch->number }}</h2>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $colors[$batch->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $batch->statusLabel() }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Pago: {{ $batch->pay_date->format('d/m/Y') }} · Corte: {{ $batch->cutoff_at->timezone('America/Bogota')->format('d/m/Y H:i') }}
                    · {{ $batch->payouts_count }} liquidación(es) · {{ $batch->beneficiaries_count }} beneficiario(s)
                </p>
                <p class="text-2xl font-black text-gray-800 mt-2">{{ $money($batch->total_amount) }} <span class="text-sm font-semibold text-gray-400">COP en total</span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('administrator.payouts.export', ['status' => 'all', 'batch' => $batch->id]) }}" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">Exportar CSV</a>
                @if($batch->status === 'draft')
                    <form method="POST" action="{{ route('administrator.payouts.batches.approve', $batch) }}" onsubmit="return confirm('¿Aprobar este lote? Se revisará de nuevo que cada línea siga siendo pagable.');">
                        @csrf
                        <button type="submit" class="text-sm font-semibold px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Aprobar lote</button>
                    </form>
                @endif
                @if($batch->isOpen())
                    <form method="POST" action="{{ route('administrator.payouts.batches.cancel', $batch) }}" onsubmit="return confirm('¿Cancelar el lote? Sus liquidaciones quedarán libres para el siguiente.');">
                        @csrf
                        <button type="submit" class="text-sm font-semibold px-4 py-2 rounded-lg border border-red-300 text-red-700 bg-white hover:bg-red-50">Cancelar lote</button>
                    </form>
                @endif
            </div>
        </div>

        @if($batch->status === 'draft')
            <div class="mb-5 p-4 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200">Borrador: revisa cada beneficiario. Si una línea no debe pagarse esta semana, exclúyela; pasará al siguiente lote. Al aprobar no se puede agregar nada más.</div>
        @elseif($batch->status === 'approved')
            <div class="mb-5 p-4 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200">Aprobado: haz la transferencia a cada beneficiario, verifica su cuenta (obligatorio) y registra aquí la referencia. Se marcan como pagadas todas sus líneas del lote a la vez.</div>
        @endif

        <div class="space-y-4">
            @foreach($groups as $key => $lines)
                @php
                    $first     = $lines->first();
                    $a         = $first->appointment;
                    $isClinic  = $first->payable_type === \App\Models\Clinic::class;
                    $name      = $isClinic ? ($a?->clinic?->user?->name ?? 'Clínica') : ($a?->doctor?->user?->name ?? 'Médico');
                    $pending   = $lines->where('status', 'pending');
                    $allPaid   = $pending->isEmpty();
                    $sumAll    = $lines->sum('amount_to_pay');
                    $sumPend   = $pending->sum('amount_to_pay');
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isClinic ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ $isClinic ? 'Clínica' : 'Médico' }}</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $allPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $allPaid ? 'Pagado' : 'Pendiente' }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mt-1">{{ $name }}</h3>
                            <p class="text-xs text-gray-500">{{ $lines->count() }} cita(s)</p>
                        </div>
                        <p class="text-xl font-black text-emerald-700">{{ $money($sumAll) }}</p>
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs font-semibold text-indigo-700 cursor-pointer">Ver las citas</summary>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full text-xs text-left text-gray-600">
                                <thead class="text-gray-500 uppercase">
                                    <tr><th class="py-1 pr-3">Cita</th><th class="py-1 pr-3">Fecha</th><th class="py-1 pr-3">Modalidad</th><th class="py-1 pr-3">Sede</th><th class="py-1 pr-3 text-right">A pagar</th><th class="py-1 pr-3">Estado</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach($lines as $line)
                                        @php
                                            $la = $line->appointment;
                                            $lv = ($la?->service?->type) === 'virtual';
                                        @endphp
                                        <tr class="border-t border-gray-100">
                                            <td class="py-1 pr-3">#{{ $line->appointment_id }}</td>
                                            <td class="py-1 pr-3">{{ $la?->date ? \Carbon\Carbon::parse($la->date)->format('d/m/Y') : '—' }}</td>
                                            <td class="py-1 pr-3">{{ $lv ? 'Virtual' : 'Presencial' }}
                                                @if(($la?->status instanceof \BackedEnum ? $la->status->value : (string) $la?->status) === 'no_show')
                                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-orange-100 text-orange-800 font-semibold" title="El paciente pagó en línea y no se presentó; por política se le paga al médico.">No asistió</span>
                                                @endif
                                                @if($lv && !$la?->zoom_started)
                                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-red-100 text-red-800 font-semibold" title="Zoom no registra que el médico haya iniciado la sala. Revisa antes de aprobar.">Sala no iniciada</span>
                                                @endif
                                            </td>
                                            <td class="py-1 pr-3">{{ $lv ? 'Virtual' : ($la?->address?->name ?? '—') }}</td>
                                            <td class="py-1 pr-3 text-right font-semibold">{{ $money($line->amount_to_pay) }}</td>
                                            <td class="py-1 pr-3">{{ $line->status === 'paid' ? 'Pagada · ' . $line->transfer_reference : 'Pendiente' }}</td>
                                            <td class="py-1 text-right">
                                                @if($batch->isOpen() && $line->status === 'pending')
                                                    <form method="POST" action="{{ route('administrator.payouts.batches.release', [$batch, $line]) }}" onsubmit="return confirm('¿Excluir esta cita del lote? Pasará al siguiente.');">
                                                        @csrf
                                                        <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>

                    @php
                        $acct = $accounts[$key]['account'] ?? null;
                        $flags = $accounts[$key]['flags'] ?? [];
                        $short = ($isClinic ? 'clinic' : 'doctor') . '-' . $first->payable_id;
                        $revealed = session('revealed')[$short] ?? null;
                    @endphp
                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">
                        <p class="font-bold text-gray-800 mb-1">Cuenta bancaria</p>
                        @if($acct)
                            <p>
                                {{ $acct->bank_name }} · {{ $acct->typeLabel() }} · <span class="font-mono">{{ $acct->masked() }}</span>
                                · Titular: {{ $acct->account_holder_name }} ({{ $acct->id_type }} {{ $acct->account_holder_id }})
                                @if($acct->isVerified())
                                    <span class="ml-1 font-bold text-emerald-700">Verificada</span>
                                @endif
                            </p>
                            @if($revealed)
                                <p class="mt-1">Número completo: <span class="font-mono font-bold text-gray-900 select-all">{{ $revealed }}</span> <span class="text-gray-400">(se ve una vez; queda registrado)</span></p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('administrator.payouts.batches.account.reveal', $batch) }}">
                                    @csrf
                                    <input type="hidden" name="payable_type" value="{{ $isClinic ? 'clinic' : 'doctor' }}">
                                    <input type="hidden" name="payable_id" value="{{ $first->payable_id }}">
                                    <button type="submit" class="font-semibold text-indigo-700 underline">Ver número completo</button>
                                </form>
                                @if(!$acct->isVerified())
                                    <form method="POST" action="{{ route('administrator.payouts.batches.account.verify', $batch) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="payable_type" value="{{ $isClinic ? 'clinic' : 'doctor' }}">
                                        <input type="hidden" name="payable_id" value="{{ $first->payable_id }}">
                                        <select name="method" required class="rounded-lg border-gray-300 text-xs py-1">
                                            @foreach(\App\Models\BankAccount::METHODS as $mk => $ml)
                                                <option value="{{ $mk }}">{{ $ml }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" onclick="return confirm('¿Confirmas que verificaste que la cuenta pertenece al beneficiario?')" class="font-semibold text-emerald-700 underline">Marcar como verificada</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                        @foreach($flags as $fk => $fm)
                            <p class="mt-2 px-2 py-1 rounded-lg {{ in_array($fk, ['holder', 'recent'], true) ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">{{ $fm }}</p>
                        @endforeach
                    </div>

                    @if($batch->status === 'approved' && !$allPaid && $acct && $acct->isVerified())
                        <form method="POST" action="{{ route('administrator.payouts.batches.pay', $batch) }}" class="mt-4 flex flex-wrap items-end gap-2 bg-slate-50 border border-slate-200 rounded-xl p-3">
                            @csrf
                            <input type="hidden" name="payable_type" value="{{ $isClinic ? 'clinic' : 'doctor' }}">
                            <input type="hidden" name="payable_id" value="{{ $first->payable_id }}">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Referencia de la transferencia por {{ $money($sumPend) }}</label>
                                <input type="text" name="transfer_reference" required maxlength="255" placeholder="Ej: TRF-20261127-001" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <button type="submit" onclick="return confirm('¿Confirmas que transferiste {{ $money($sumPend) }} a {{ addslashes($name) }}?')" class="text-xs bg-gray-800 text-white font-semibold px-4 py-2 rounded-lg hover:bg-gray-700">Registrar transferencia</button>
                        </form>
                    @elseif($batch->status === 'approved' && !$allPaid)
                        <p class="mt-4 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">
                            Para registrar la transferencia la cuenta debe estar registrada y verificada. Haz la transferencia, confirma en tu banco que el nombre del titular coincide y márcala como verificada aquí arriba.
                        </p>
                    @endif

                    @if($allPaid)
                        @php $rep = $reports[$key] ?? null; @endphp
                        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-gray-600 bg-emerald-50 border border-emerald-100 rounded-xl p-3">
                            @if($rep && $rep->pdf_path)
                                <span>Soporte <strong>{{ $rep->number }}</strong> · {{ $rep->emailed_at ? 'enviado a ' . $rep->email_to . ' el ' . $rep->emailed_at->timezone('America/Bogota')->format('d/m/Y H:i') : 'generado, correo pendiente' }}</span>
                                <a href="{{ route('administrator.payouts.reports.download', $rep) }}" class="font-semibold text-indigo-700 underline">Descargar PDF</a>
                                <form method="POST" action="{{ route('administrator.payouts.reports.resend', $rep) }}" onsubmit="return confirm('¿Reenviar el soporte por correo?');">
                                    @csrf
                                    <button type="submit" class="font-semibold text-indigo-700 underline">Reenviar correo</button>
                                </form>
                            @else
                                <span>El soporte se está generando y enviando (puede tardar un minuto). Actualiza la página.</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @if($groups->isEmpty())
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">Este lote no tiene liquidaciones.</div>
            @endif
        </div>

        @if($events->isNotEmpty())
            <div class="mt-8">
                <h3 class="text-sm font-bold text-gray-700 mb-2">Historial</h3>
                <ul class="text-xs text-gray-500 space-y-1">
                    @foreach($events as $e)
                        <li>{{ $e->created_at->timezone('America/Bogota')->format('d/m/Y H:i') }} · {{ $eventLabels[$e->event] ?? $e->event }}@if($e->payout_id) (liquidación #{{ $e->payout_id }})@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-admin-layout>
