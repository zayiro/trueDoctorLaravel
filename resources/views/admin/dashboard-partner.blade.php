@php
$validation_status = auth()->user()->doctor->validation_status;
@endphp
<div>
    <!-- Alerta de éxito si se procesa el formulario -->
    @if (session('success'))
        <div class="max-w-2xl mx-auto p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 mt-5">
            {{ session('success') }}
        </div>
    @endif
    
    @if ($validation_status != 'approved')
        <x-verify-docs :status="$validation_status" />
    @else   
        <div>             
            <!-- Alerta de éxito si se procesa el formulario -->
            @if (session('success'))
                <div class="max-w-2xl mx-auto p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 mt-5">
                    {{ session('success') }}
                </div>
            @endif

            <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
                <div><a href="{{ route('partner.public.profile', auth()->user()->doctor) }}" target="_blank" title="{{ auth()->user()->name }}" class="text-blue-600 underline">Ver mi perfil publico</a></div>
                
                <!-- CABECERA PRINCIPAL -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm">
                    <div>
                        <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1 block">Consola de Control</span>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight">Estadísticas e Indicadores</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Monitoreo transaccional, volumen de pacientes y rendimiento financiero del mes.</p>
                    </div>
                    <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                        Inquilino: <span class="text-indigo-600 font-black">{{ $owner->name }}</span>
                    </div>
                </div>

                <!-- CUADRÍCULA DE INDICADORES CLAVE (KPIs CORE) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- KPI 1: Facturación del Mes -->
                    <div class="bg-white border rounded-[2rem] p-6 shadow-sm border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Ingresos Mes</span>
                            <h3 class="text-2xl font-black text-emerald-600 tracking-tight">${{ number_format($facturacionMes, 2) }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium block">Moneda de corte: {{ $owner->settings->currency ?? 'COP' }}</span>
                        </div>
                        <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>

                    <!-- KPI 2: Citas Agendadas Hoy -->
                    <div class="bg-white border rounded-[2rem] p-6 shadow-sm border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Consultas Hoy</span>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ $citasHoy }}</h3>
                            <span class="text-[10px] text-indigo-500 font-bold block uppercase tracking-tighter">Agenda diaria activa</span>
                        </div>
                        <div class="p-3.5 bg-indigo-50 text-indigo-600 rounded-2xl border border-indigo-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>

                    <!-- KPI 3: Próximas Citas -->
                    <div class="bg-white border rounded-[2rem] p-6 shadow-sm border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Próximos Turnos</span>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ $citasProximas }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium block">Pacientes en espera</span>
                        </div>
                        <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>

                    <!-- KPI 4: Tasa de Ausentismo / Cancelación -->
                    <div class="bg-white border rounded-[2rem] p-6 shadow-sm border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Tasa Cancelación</span>
                            <h3 class="text-2xl font-black text-red-600 tracking-tight">{{ $tasaCancelacion }}%</h3>
                            <span class="text-[10px] text-slate-400 font-medium block">Margen de abandono general</span>
                        </div>
                        <div class="p-3.5 bg-red-50 text-red-600 rounded-2xl border border-red-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </div>
                    </div>

                </div>
                <!-- PANELES DE DESGLOSE AVANZADO Y DISTRIBUCIÓN -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Panel: Distribución de Canales de Atención -->
                    <div class="lg:col-span-1 bg-white border rounded-[2.5rem] p-6 shadow-sm border-slate-100 flex flex-col justify-between">
                        <div class="border-b border-slate-100 pb-3 mb-4">
                            <h4 class="text-sm font-black uppercase text-slate-800 tracking-wide">Modalidad de Consulta</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Participación del canal digital frente al presencial.</p>
                        </div>

                        <div class="space-y-4 my-auto">
                            @php
                                $virtuales = $modalidades['virtual'] ?? 0;
                                $presenciales = $modalidades['physical'] ?? 0;
                                $totalModalidades = $virtuales + $presenciales;
                                
                                $pctVirtual = $totalModalidades > 0 ? round(($virtuales / $totalModalidades) * 100) : 0;
                                $pctPhysical = $totalModalidades > 0 ? round(($presenciales / $totalModalidades) * 100) : 0;
                            @endphp

                            <!-- Barra Canal Presencial -->
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span class="flex items-center gap-1.5">🏥 Presencial (En Sede)</span>
                                    <span>{{ $presenciales }} ({{ $pctPhysical }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                                    <div class="bg-blue-600 h-full rounded-full transition-all" style="width: {{ $pctPhysical }}%"></div>
                                </div>
                            </div>

                            <!-- Barra Canal Virtual -->
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span class="flex items-center gap-1.5">💻 Telemedicina (Virtual)</span>
                                    <span>{{ $virtuales }} ({{ $pctVirtual }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                                    <div class="bg-purple-600 h-full rounded-full transition-all" style="width: {{ $pctVirtual }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-50 text-[10px] text-slate-400 font-medium italic">
                            * Datos calculados en base al volumen histórico acumulado de reservas.
                        </div>
                    </div>

                    <!-- Panel: Consultorios y Sedes más Rentables -->
                    <div class="lg:col-span-2 bg-white border rounded-[2.5rem] p-6 shadow-sm border-slate-100 flex flex-col justify-between">
                        <div>
                            <div class="border-b border-slate-100 pb-3 mb-4">
                                <h4 class="text-sm font-black uppercase text-slate-800 tracking-wide">Top Sedes de Mayor Rendimiento</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">Ranking de los consultorios físicos con mayor tracción económica.</p>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @forelse($sedesTop as $index => $sede)
                                    <div class="py-3 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-600 font-black text-xs flex items-center justify-center border shadow-sm">
                                                #{{ $index + 1 }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-extrabold text-slate-800">{{ $sede->name }}</span>
                                                <span class="text-[11px] text-slate-400 font-medium">{{ $sede->cantidad }} {{ Str::plural('cita', $sede->cantidad) }} operadas</span>
                                            </div>
                                        </div>
                                        <span class="text-sm font-black text-emerald-600 bg-emerald-50 border border-emerald-100/50 px-3 py-1 rounded-xl">
                                            +${{ number_format($sede->ingresos, 2) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic py-6 text-center">No registras transacciones presenciales mapeadas en tus sedes físicas.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>

                <!-- REPORTE DE TRANSACCIONES CRONOLÓGICAS (TABLA HISTÓRICA) -->
                <div class="bg-white border rounded-[2.5rem] p-6 md:p-8 shadow-sm border-slate-100">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h4 class="text-sm font-black uppercase text-slate-800 tracking-wide">Evolución Comercial Mensual</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Historial consolidado de la facturación y flujos de pacientes de los últimos 5 meses.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-3.5 text-xs font-black uppercase text-slate-500 tracking-wider">Periodo / Mes</th>
                                    <th class="px-6 py-3.5 text-xs font-black uppercase text-slate-500 tracking-wider">Citas Consolidadas</th>
                                    <th class="px-6 py-3.5 text-xs font-black uppercase text-slate-500 tracking-wider text-right">Facturación Bruta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($historicoMensual as $mesInfo)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 text-sm font-extrabold text-slate-800 capitalize">
                                            {{ \Carbon\Carbon::parse($mesInfo->mes . '-01')->translatedFormat('F Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 font-semibold">
                                            {{ $mesInfo->conteo }} consulta(s) exitosa(s)
                                        </td>
                                        <td class="px-6 py-4 text-sm font-black text-emerald-600 text-right">
                                            ${{ number_format($mesInfo->total, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-xs text-slate-400 italic">No registras movimientos ni flujos comerciales históricos en el sistema.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div> 
