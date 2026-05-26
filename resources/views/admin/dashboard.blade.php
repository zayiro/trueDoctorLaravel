@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Consola Analítica de Negocio']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">    
    <div>                        
        <x-appointment-search-form class="mb-6 p-4 bg-white rounded-lg shadow" />

        <div class="max-w-7xl mx-auto py-8 space-y-8">                
            <!-- CABECERA PRINCIPAL -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                <div>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1 block">Consola de Control</span>
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight">Estadísticas e Indicadores</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Monitoreo transaccional, volumen de pacientes y rendimiento financiero del mes.</p>
                </div>
                <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                    Inquilino: <span class="text-indigo-600 font-black">{{ $owner?->name ?? 'Consola Global / Administrador' }}</span>
                </div>
            </div>

            <!-- 🔥 SECCIÓN EXCLUSIVA: CONTROL DE USUARIOS DEL SAAS (SOLO ADMIN) -->
            @if($user->role === 'admin')
                <div class="space-y-3">
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] block">Comunidad opendoctor</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-900 p-6 rounded-[2rem] shadow-xl text-white">
                        
                        <!-- Doctores Registrados -->
                        <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Médicos Activos</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-black tracking-tight font-mono text-indigo-400">{{ $usuariosPorRol['doctor'] ?? 0 }}</span>
                                <span class="text-[10px] text-slate-500 font-bold">SaaS</span>
                            </div>
                        </div>

                        <!-- Clínicas/Socios Registrados -->
                        <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4 sm:pl-4">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Centros Médicos</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-black tracking-tight font-mono text-purple-400">{{ $usuariosPorRol['clinic'] ?? 0 }}</span>
                                <span class="text-[10px] text-slate-500 font-bold">Centros</span>
                            </div>
                        </div>

                        <!-- Pacientes Únicos -->
                        <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4 sm:pl-4">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Pacientes Totales</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-black tracking-tight font-mono text-emerald-400">{{ $usuariosPorRol['patient'] ?? 0 }}</span>
                                <span class="text-[10px] text-slate-500 font-bold">Historias</span>
                            </div>
                        </div>

                        <!-- Administradores del Sistema -->
                        <div class="space-y-1 sm:pl-4">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Staff Administrativo</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-black tracking-tight font-mono text-amber-400">{{ $usuariosPorRol['admin'] ?? 0 }}</span>
                                <span class="text-[10px] text-slate-500 font-bold">Admins</span>
                            </div>
                        </div>

                    </div>
                </div>
            @endif


            <!-- CUADRÍCULA DE INDICADORES CLAVE (KPIs CORE) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- KPI 1: Facturación del Mes -->
                <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Ingresos Mes</span>
                        <h3 class="text-2xl font-black text-emerald-600 tracking-tight">${{ number_format($facturacionMes, 2) }}</h3>
                        <span class="text-[10px] text-slate-400 font-medium block">Moneda de corte: {{ $owner?->settings->currency ?? 'COP' }}</span>
                    </div>
                    <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- KPI 2: Citas Agendadas Hoy -->
                <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
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
                <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
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
                <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
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
                <div class="lg:col-span-1 bg-white border rounded-[2.5rem] p-6 shadow-lg border-slate-100 flex flex-col justify-between">
                    <div class="border-b border-slate-100 pb-3 mb-4">
                        <h4 class="text-sm font-black uppercase text-slate-800 tracking-wide">Modalidad de Consulta</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Participación del canal digital frente al presencial.</p>
                    </div>

                    @php
                        $virtuales = $modalidades['virtual'] ?? 0;
                        $presenciales = $modalidades['physical'] ?? 0;
                        $totalModalidades = $virtuales + $presenciales;
                        
                        $pctVirtual = $totalModalidades > 0 ? round(($virtuales / $totalModalidades) * 100) : 0;
                        $pctPhysical = $totalModalidades > 0 ? round(($presenciales / $totalModalidades) * 100) : 0;
                    @endphp

                    @if($totalModalidades > 0)
                        <div class="space-y-4 my-auto">
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
                    @else
                        <!-- Estado Vacío si no hay datos -->
                        <div class="flex flex-col items-center justify-center py-8 px-4 text-center my-auto space-y-2 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5"/></svg>
                            <span class="text-xs font-bold text-slate-700">Sin historial de citas</span>
                            <p class="text-[11px] text-slate-400 max-w-[200px]">No hay transacciones registradas este mes para calcular modalidades.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

</x-admin-layout>
