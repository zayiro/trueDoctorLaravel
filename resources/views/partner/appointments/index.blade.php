@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agenda Médica']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 max-w-7xl mx-auto">
        <!-- ALERTAS DE ÉXITO O ERROR -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <div class="ms-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        <!-- HEADER INTELIGENTE MULTIPERFIL -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Control de Citas</h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if($showAll)
                        Mostrando todas las <strong>próximas citas agendadas</strong>
                    @else
                        Citas consolidadas para el <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d \d\e F, Y') }}</strong>
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if(!$showAll)
                    <a href="{{ route('partner.appointments.index', ['all' => 1]) }}" 
                       class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest bg-gray-50 text-gray-500 hover:bg-gray-100 border border-gray-200/60 transition-all">
                        Ver Todo
                    </a>
                @else
                    <a href="{{ route('partner.appointments.index') }}" 
                       class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-indigo-50 text-indigo-700 border-indigo-100' }} transition-all">
                        Volver a Hoy
                    </a>
                @endif

                <form action="{{ route('partner.appointments.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                    @if($showAll)
                        <input type="hidden" name="all" value="1">
                    @endif

                    @if(auth()->user()->role === 'clinic')
                        <div class="bg-white px-3 py-1.5 rounded-2xl border border-gray-200 flex items-center shadow-sm">
                            <select name="doctor_id" onchange="this.form.submit()" class="border-none focus:ring-0 text-sm font-semibold text-gray-700 bg-transparent cursor-pointer py-1">
                                <option value="">Todos los Especialistas</option>
                                @foreach($availableDoctors as $doc)
                                    <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>
                                        {{ $doc->user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-gray-200 shadow-sm">
                        <input type="date" name="date" value="{{ $date ?? now()->toDateString() }}" onchange="this.form.submit()" 
                            class="border-none focus:ring-0 text-sm font-semibold text-gray-700 bg-transparent cursor-pointer py-1">
                        <button type="submit" class="{{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' }} text-white px-5 py-2 rounded-xl text-xs uppercase font-black tracking-wider transition-all shadow-sm">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CONTENEDOR DE AGENDAS EN CASCADA -->
        <div class="space-y-10">
            @forelse($appointments as $addressId => $group)
                @php $groupCount = $group->count(); @endphp
                
                <section class="animate-fade-in">
                    <!-- Cabecera de la sección tematizada según el contexto activo -->
                    <div class="flex items-center justify-between {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'bg-emerald-950' : 'bg-slate-900' }} px-6 py-4 rounded-t-3xl shadow-md text-white">
                        <div class="flex items-center gap-3">
                            <span class="font-bold tracking-wide uppercase text-xs sm:text-sm flex items-center gap-2">
                                📍 {{ $group->first()->address->name ?? 'Consulta Virtual / Telemedicina' }}
                            </span>
                        </div>
                        <span class="{{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'bg-emerald-600' : 'bg-indigo-600' }} text-white text-[10px] px-3 py-1 rounded-full font-black tracking-wider">
                            {{ $groupCount }} {{ Str::plural('CITA', $groupCount) }}
                        </span>
                    </div>
                    <div class="bg-white shadow-xl rounded-b-3xl overflow-hidden border-x border-b border-gray-100">
                        <!-- Tabla para Pantallas de Escritorio (Desktop) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Hora</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Paciente</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Servicio</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Estado</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($group as $app)                                    
                                        @include('partner.appointments.partials.row', ['app' => $app])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Tarjetas para Pantallas de Celulares (Mobile) -->
                        <div class="md:hidden divide-y divide-slate-100 bg-white">
                            @foreach($group as $app)
                                @include('partner.appointments.partials.card', ['app' => $app])
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <!-- Fallback: Estado vacío de la agenda -->
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200 p-6">
                    <div class="mx-auto w-12 h-12 text-slate-400 mb-3">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-12 h-12 mx-auto text-slate-300">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h5 class="text-base font-bold text-slate-800">No hay citas registradas</h5>
                    <p class="text-gray-500 text-sm mt-1 max-w-sm mx-auto">No se encontraron reservas de pacientes para los filtros o la fecha seleccionada en el cuadrante.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL DE NOTAS DE CONSULTA -->
    <div id="noteModal" class="fixed inset-0 hidden" style="z-index: 9999;" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl transform transition-all overflow-hidden border border-slate-100 relative">
                <div class="h-2 w-full bg-amber-400"></div>
                <div class="px-8 pt-10 pb-6 text-center relative">
                    <!-- Botón de cerrado atómico -->
                    <button type="button" onclick="closeNoteModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors font-bold text-lg p-2">&times;</button>
                    
                    <div class="mx-auto w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500 mb-4 shadow-sm shadow-amber-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-black text-slate-900 mb-2">Notas de la Consulta</h3>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-left max-h-48 overflow-y-auto mt-3">
                        <p id="modalNoteText" class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">Cargando anotaciones...</p>
                    </div>

                    <div class="mt-6">
                        <button type="button" onclick="closeNoteModal()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-6 rounded-2xl transition duration-200 uppercase tracking-wider text-xs">
                            Entendido / Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

<!-- ======================================================== -->
<!-- SCRIPT AUXILIAR INTERACTIVO PARA EL MODAL -->
<!-- ======================================================== -->
<script>
    function openNoteModal(text) {
        document.getElementById('modalNoteText').innerText = text || 'El paciente no ingresó observaciones adicionales para esta cita.';
        document.getElementById('noteModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeNoteModal() {
        document.getElementById('noteModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>
