
<x-guest-layout>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">
        <div class="w-full bg-white rounded-3xl border border-slate-200/80 shadow-2xl shadow-slate-100 p-6 sm:p-8 space-y-6">
            <!-- Encabezado -->
            <div class="text-center space-y-2">
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">Paso Final</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Confirmar Pedido</h1>
                <p class="text-xs text-slate-400">Estás a un paso de interpretar tus resultados clínicos.</p>
            </div>

            <!-- Resumen de Facturación -->
            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-slate-400">Servicio</span>
                    <span class="text-slate-800">Lectura de Examen por IA</span>
                </div>
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-slate-400">Motivo declarado</span>
                    <span class="text-slate-700 capitalize">{{ $analysis->reason_type }}</span>
                </div>
                <div class="border-t border-slate-200/60 my-2 pt-2 flex justify-between items-baseline">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total a Pagar</span>
                    <span class="text-2xl font-black text-slate-900 tabular-nums">${{ number_format($analysis->price, 2) }}</span>
                </div>
            </div>

            <!-- Formulario Seguro Interno -->
            <form action="{{ route('exams.id_pago', $analysis->id) }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Simulación de Datos de Tarjeta -->
                <div class="space-y-3 bg-slate-50/50 p-4 rounded-2xl border border-slate-200/60">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Titular de la Tarjeta</label>
                        <input type="text" disabled value="{{ auth()->user()->name ?? 'Paciente Registrado' }}" class="w-full p-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-xs text-slate-500 cursor-not-allowed">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Número de Tarjeta (Simulación)</label>
                        <input type="text" disabled value="•••• •••• •••• 4242" class="w-full p-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-xs text-slate-500 cursor-not-allowed">
                    </div>
                </div>

                <!-- Botón de Pago -->
                <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-100/50 hover:shadow-emerald-200 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Pagar de forma segura
                </button>
            </form>

            <p class="text-[10px] text-center text-slate-400 leading-normal">
                Garantizamos la privacidad de tus datos. Tus documentos clínicos están encriptados y protegidos bajo la normativa vigente.
            </p>
        </div>
    </div>
</x-guest-layout>