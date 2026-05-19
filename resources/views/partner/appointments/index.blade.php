@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agenda médica']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8">
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <span class="sr-only">Éxito</span>
                <div class="ms-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="sr-only">Cerrar</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div style="background-color: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #f87171;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Mi Agenda</h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if($showAll)
                        Mostrando todas las <strong>próximas citas</strong>
                    @else
                        Citas para el <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d \d\e F, Y') }}</strong>
                    @endif
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                @if(!$showAll)
                    <a href="{{ route('partner.appointments.index', ['all' => 1]) }}" 
                    class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-gray-100 text-gray-500 hover:bg-gray-200 transition-all border border-gray-200">
                    Ver Todo
                    </a>
                @else
                    <a href="{{ route('partner.appointments.index') }}" 
                    class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-100 text-indigo-700 border border-indigo-200 transition-all">
                    Volver a Hoy
                    </a>
                @endif

                <form action="{{ route('partner.appointments.index') }}" method="GET" class="flex items-center gap-2 bg-white p-2 rounded-2xl shadow-sm border border-gray-100">
                    <input type="date" name="date" value="{{ $date ?? now()->toDateString() }}" onchange="this.form.submit()" 
                        class="border-none focus:ring-0 text-sm font-semibold text-gray-700 bg-transparent cursor-pointer">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md">
                        Filtrar
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-10">
            @forelse($appointments as $addressId => $group)
                @php $groupCount = $group->count(); @endphp
                
                <section>
                    <div class="flex items-center justify-between bg-gray-900 px-6 py-4 rounded-t-3xl shadow-lg text-white">
                        <div class="flex items-center gap-3">
                            <span class="font-bold tracking-wide uppercase text-sm">
                                📍 {{ $group->first()->address->name ?? 'Consulta Virtual' }}
                            </span>
                        </div>
                        <span class="bg-indigo-500 text-white text-[10px] px-3 py-1 rounded-full font-black">
                            {{ $groupCount }} {{ Str::plural('CITA', $groupCount) }}
                        </span>
                    </div>

                    <div class="bg-white shadow-xl rounded-b-3xl overflow-hidden border-x border-b border-gray-100">
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-400">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-600">Hora</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-600">Paciente</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-600">Servicio</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-600">Estado</th>
                                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-600 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($group as $app)
                                        @include('partner.appointments.partials.row', ['app' => $app])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden divide-y divide-gray-100">
                            @foreach($group as $app)
                                @include('partner.appointments.partials.card', ['app' => $app])
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-500 font-medium">No hay citas para esta fecha.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal de Notas -->
    <div id="noteModal" class="fixed inset-0 hidden" style="z-index: 9999;" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md transition-opacity"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl transform transition-all overflow-hidden border border-gray-100 relative">
                <div class="h-2 w-full bg-amber-400"></div>
                <div class="px-8 pt-10 pb-6 text-center">
                    <div class="mx-auto w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 mb-2">Notas de la Consulta</h3>
                    <p id="modalNoteText" class="text-gray-600 text-sm leading-relaxed text-left bg-gray-50 p-4 rounded-2xl border border-gray-100 max-h-60 overflow-y-auto"></p>
                </div>
                <div class="bg-gray-50 px-8 py-4 flex justify-end">
                    <button type="button" onclick="closeNoteModal()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold text-sm transition-colors">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openNoteModal(notes) {
            document.getElementById('modalNoteText').innerText = notes || 'No hay notas para esta cita.';
            document.getElementById('noteModal').classList.remove('hidden');
        }
        function closeNoteModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }
    </script>
</x-admin-layout>
