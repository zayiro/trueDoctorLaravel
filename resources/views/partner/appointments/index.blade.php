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
                <!-- Botón Ver Todo / Volver a Hoy -->
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

                <!-- Formulario Filtro -->
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
                    <!-- Header de Sede -->
                    <div class="flex items-center justify-between bg-gray-900 px-6 py-4 rounded-t-3xl shadow-lg text-white">
                        <div class="flex items-center gap-3">
                            <span class="font-bold tracking-wide uppercase text-sm">
                                📍 {{ $group->first()->address->name ?? 'Sede no definida' }}
                            </span>
                        </div>
                        <span class="bg-indigo-500 text-white text-[10px] px-3 py-1 rounded-full font-black">
                            {{ $groupCount }} {{ Str::plural('CITA', $groupCount) }}
                        </span>
                    </div>

                    <!-- Contenedor de Citas -->
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
                <!-- Vista Vacía -->
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-500 font-medium">No hay citas para esta fecha.</p>
                </div>
            @endforelse
        </div>
    </div>
    <!-- Modal de Notas -->
    <div id="noteModal" class="fixed inset-0 hidden" style="z-index: 9999;" role="dialog" aria-modal="true">
        <!-- Overlay Oscuro con Blur -->
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md transition-opacity"></div>

        <!-- Contenedor de Posicionamiento -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white w-1/2 rounded-[2.5rem] shadow-[0_35px_60px_-15px_rgba(0,0,0,0.5)] transform transition-all overflow-hidden border border-gray-100 relative">
                
                <!-- Decoración Superior -->
                <div class="h-2 w-full bg-amber-400"></div>

                <div class="px-8 pt-10 pb-6 text-center">
                    <div class="mx-auto w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-2xl font-black text-gray-900 mb-2">Notas del Paciente</h3>
                    <div id="modalNoteContent" class="bg-gray-50 p-6 rounded-3xl border border-gray-100 text-sm text-gray-700 leading-relaxed min-h-[140px] max-h-[50vh] overflow-y-auto text-left shadow-inner">
                        <!-- Contenido -->
                    </div>
                </div>

                <!-- Footer con Botón Gigante para Móvil -->
                <div class="px-8 pb-8 mb-4">
                    
                    <button onclick="closeNoteModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

        @if ($appointments->count() > 0)
            <!-- Dropdown Menú Global -->    
            <div id="appointmentDropdown" class="hidden fixed z-[10000] w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden transform transition-all scale-95 opacity-0">
                <div class="py-2">
                    <a href="#" id="dropReminder" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Recordatorio SMS
                    </a>

                    <a id="dropReschedule" href="{{ route('partner.appointments.reschedule', ['appointment' => $app]) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Reagendar Cita
                    </a>                
                    
                    <form id="formComplete" action="{{ route('partner.appointments.complete', ['appointment' => $app]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de completar esta cita?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Cita completada
                        </button>
                    </form>

                    <form id="formCancel" action="{{ route('partner.appointments.cancel', ['appointment' => $app]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de cancelar esta cita?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Cancelar Cita
                        </button>
                    </form>

                    <form id="formDelete" action="{{ route('partner.appointments.destroy', ['appointment' => $app]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta cita?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Eliminar Cita
                        </button>
                    </form>
                </div>
            </div>
            
            <script>
                let currentAppointmentId = null;

                function openNoteModal(noteText) {
                    const modal = document.getElementById('noteModal');
                    const content = document.getElementById('modalNoteContent');
                    
                    // Insertar el texto (manejando nulos)
                    content.innerText = noteText || 'No hay notas registradas para esta cita.';
                    
                    // Mostrar el modal quitando la clase hidden
                    modal.classList.remove('hidden');
                    
                    // Bloquear el scroll del cuerpo
                    document.body.style.overflow = 'hidden';
                }

                function closeNoteModal() {
                    const modal = document.getElementById('noteModal');
                    
                    // Ocultar el modal añadiendo la clase hidden
                    modal.classList.add('hidden');
                    
                    // Restaurar el scroll
                    document.body.style.overflow = 'auto';
                }

                // Cerrar si se hace click fuera del contenido (en el overlay)
                window.onclick = function(event) {
                    const modal = document.getElementById('noteModal');
                    if (event.target == modal) {
                        closeNoteModal();
                    }
                }
            
                function toggleDropdown(event, appointmentId) {
                    event.stopPropagation();
                    const dropdown = document.getElementById('appointmentDropdown');
                    const trigger = event.currentTarget;
                    const rect = trigger.getBoundingClientRect();

                    // Lógica de posicionamiento
                    dropdown.style.top = `${rect.bottom + window.scrollY + 5}px`;
                    dropdown.style.left = `${rect.right - 192}px`;

                    dropdown.classList.remove('hidden');
                    setTimeout(() => {
                        dropdown.classList.remove('scale-95', 'opacity-0');
                        dropdown.classList.add('scale-100', 'opacity-100');
                    }, 10);
                }

                function openRescheduleModal() {
                    hideDropdown(); // Cerramos el menú pequeño
                    const modal = document.getElementById('recheduleModal');
                    const form = document.getElementById('rescheduleForm');
                    
                    // Seteamos la URL del controlador
                    form.action = `/partner/appointments/${currentAppointmentId}/reschedule`;
                    
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }

                function closeRescheduleModal() {
                    document.getElementById('recheduleModal').classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }

                function hideDropdown() {
                    const dropdown = document.getElementById('appointmentDropdown');
                    dropdown.classList.remove('scale-100', 'opacity-100');
                    dropdown.classList.add('scale-95', 'opacity-0');
                    setTimeout(() => dropdown.classList.add('hidden'), 150);
                }

                // Cerrar al hacer clic en cualquier otro lado
                window.addEventListener('click', function(e) {
                    if (!document.getElementById('appointmentDropdown').contains(e.target)) {
                        hideDropdown();
                    }
                });
            </script>
        @endif
</x-admin-layout>

