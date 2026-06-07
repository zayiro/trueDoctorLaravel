<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        
        <!-- ALERTAS DE INFORMACIÓN DEL SISTEMA -->
        @if(session('info'))
            <div class="p-4 mb-6 text-sm text-blue-800 rounded-2xl bg-blue-50 border border-blue-200/60 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-900 flex items-center gap-3 shadow-sm animate-fade-in" role="alert">
                {{-- Heroicon: InformationCircle --}}
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063 1.06l-.041.02a.75.75 0 01-1.063-1.06zm0-3.75a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z" />
                </svg>
                <div>
                    <span class="font-bold">Aviso del sistema:</span> {{ session('info') }}
                </div>
            </div>
        @endif
        <!-- CONTROL DE ALERTAS DE ERROR CON ALPINE.JS -->
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between p-4 mt-5 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="flex items-center">
                    {{-- Heroicon: ExclamationTriangle --}}
                    <svg class="flex-shrink-0 w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                </div>
                <button type="button" @click="show = false" class="text-red-400 hover:text-red-600 font-black text-sm px-2">×</button>
            </div>
        @endif

        <!-- BARRA DE FILTROS NATIVA (INMUNE A LIVEWIRE) -->
        <x-medical-search-bar :specialties="$specialties" :cities="$cities" />

        <!-- ENCABEZADO DE RESULTADOS ESTILIZADO -->
        <div class="mb-8 bg-white border border-slate-150/60 rounded-3xl shadow-sm overflow-hidden animate-fade-in">
            <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-50">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl shadow-inner border border-indigo-100/40 flex-shrink-0">
                        {{-- Heroicon: MagnifyingGlass --}}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight leading-none">                    
                            @if($results->total() === 1)
                                1 centro o especialista encontrado
                            @elseif($results->total() > 1)
                                {{ $results->total() }} centros o especialistas encontrados
                            @else
                                Búsqueda de especialistas
                            @endif
                        </h2>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-ping"></span>
                            Disponibilidad en tiempo real
                        </p>
                    </div>
                </div>

                @if(request()->filled('specialty') || request()->filled('city'))
                    <div class="flex flex-wrap gap-2 items-center md:justify-end">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Filtros:</span>
                        @if(request()->filled('specialty'))
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-lg border border-slate-200 uppercase tracking-wide">{{ str_replace('-', ' ', request('specialty')) }}</span>
                        @endif
                        @if(request()->filled('city'))
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-bold rounded-lg border border-indigo-100/30 uppercase tracking-wide">{{ str_replace('-', ' ', request('city')) }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Fila Inferior Condicional: Aviso de Ubicación Integrado -->
            @if(request()->missing('city') || empty(request('city')))
                <div class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center gap-3">
                    <div class="p-1.5 bg-amber-50 text-amber-600 rounded-lg border border-amber-100/40 flex-shrink-0">
                        {{-- Heroicon: MapPin --}}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-0.5 sm:gap-2">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Filtrar por ubicación:</span>
                        <span class="text-xs text-slate-500 font-medium">Elige una ciudad arriba para descubrir consultorios físicos o clínicas más cercanas.</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- CONTENEDOR GRID DE TARJETAS -->
        <div
            x-data="{ redirecting: false }" 
            class="space-y-6"    
        >
            @forelse($results as $result)
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-6 hover:shadow-md transition duration-200">
                    <!-- Foto / Icono Lateral Izquierdo -->
                    <div class="flex-shrink-0 text-center">
                        @if($result['user'] && $result['user']->profile_photo_path)
                            <img src="{{ \Storage::url($result['user']->profile_photo_path) }}" class="w-24 h-24 rounded-2xl mx-auto mb-3 object-cover border-2 border-indigo-50 shadow-inner">
                        @else
                           <div class="w-24 h-24 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-2xl mx-auto mb-3">
                                @if($result['type'] === 'clinic')
                                    {{-- Heroicon: BuildingOffice2 --}}
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                @else
                                    {{-- Heroicon: User --}}
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                @endif
                            </div>
                        @endif
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-md border border-indigo-100/30 tracking-wider">
                            {{ $result['type'] === 'clinic' ? 'Clínica' : 'Profesional' }}
                        </span>
                    </div>

                    <!-- Cuerpo de Información Centralizado -->
                    <div class="flex-1">                       
                        @if($result['type'] === 'clinic')
                            <!-- 🏛️ CARD DE CLÍNICA (Muestra: Clínica de los Colores - Sede Norte) -->
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $result['title'] }}</h3>
                            <p class="text-sm font-bold text-indigo-600 mt-1 uppercase tracking-wide">{{ $result['badge_text'] }}</p>
                        @else
                            <!-- 🩺 CARD DE DOCTOR (Muestra Ej.: Dr. Andrés Ocampo) -->
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $result['title'] }}</h3>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                <span class="px-2.5 py-0.5 bg-blue-50/60 text-blue-600 text-[10px] font-bold rounded-lg uppercase tracking-wide border border-blue-100/40">
                                    {{ $result['badge_text'] }}
                                </span>
                            </div>
                        @endif

                        <!-- Estrellas de Reputación Comunes -->
                        <div class="flex items-center space-x-1 mt-2.5">
                            @php $ratingScore = round($result['rating'] ?? 0); @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $ratingScore ? 'text-yellow-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            @endfor
                            <span class="ml-2 text-xs font-bold text-slate-400">Garantía OpenDoctor</span>
                        </div>
                        <!-- Detalles de la Sede y Dirección -->
                        <div class="mt-4 pt-3 border-t border-slate-50">
                            <div class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Dirección de Atención</div>
                            <div class="flex items-start gap-2 text-xs text-slate-600 mt-1.5">
                                {{-- Heroicon: MapPin --}}
                                <svg class="w-3.5 h-3.5 mt-0.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                <div><span class="font-semibold text-slate-700">{{ $result['subtitle'] }}</span></div>
                            </div>
                            
                            <!-- Catálogo Rápido de Servicios -->
                            <div class="flex flex-wrap gap-1 mt-2.5 mb-2 pl-5">
                                @php 
                                    $currentAddress = \App\Models\Address::find($result['address_id']);
                                    $addressServices = $currentAddress ? $currentAddress->services()->where('services.active', true)->take(3)->get() : collect();
                                @endphp
                                @if($addressServices->isNotEmpty())
                                    @foreach($addressServices as $service)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200/60">{{ $service->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Consulta general</span>
                                @endif
                            </div>                                    
                        </div>

                        <!-- 🔥 EL GANCHO DE INMEDIATEZ (Muestra: Próximo turno disponible: Hoy a las 10:30 AM) -->
                        @if($result['next_turn'])
                            <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-100/70 text-emerald-800 text-xs font-black rounded-xl shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                Próximo turno disponible: {{ $result['next_turn'] }}
                            </div>
                        @endif
                    </div>
                    
                    <!-- Columna de Botones (Acciones Unificadas e Inteligentes con Bifurcación Semántica) -->
                    <div class="flex flex-col justify-center gap-2.5 min-w-[200px] border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 px-2">
                        <div x-data="{ redirecting: false }" class="flex flex-col gap-2 w-full">
                            
                            <!-- BOTÓN ADAPTATIVO: Detecta el tipo de inquilino (Tenant) para cambiar el destino y los parámetros -->
                            <a href="{{ $result['type'] === 'clinic' 
                                            ? route('partner.clinic.public.decision', [
                                                'slug'       => $result['slug'], 
                                                'specialty_slug'  => request('specialty'), 
                                                'city'       => request('city')
                                            ]) 
                                            : route('partner.public.profile', [
                                                'slug'       => $result['slug'], 
                                                'clinic_id'  => null, 
                                                'address_id' => $result['address_id'], 
                                                'specialty'  => request('specialty')
                                            ]) }}" 
                                @click="redirecting = true"
                                :class="redirecting ? 'opacity-80 cursor-not-allowed bg-blue-700 pointer-events-none transform-none' : 'bg-blue-600 hover:bg-blue-700 active:scale-[0.99] hover:-translate-y-0.5'"
                                class="w-full sm:w-auto text-white font-black text-[11px] uppercase tracking-wider text-center py-3.5 px-6 rounded-xl shadow-md shadow-blue-500/10 transition-all transform flex items-center justify-center gap-2.5 min-h-[46px] select-none">
                                
                                <!-- Spinner reactivo de Alpine.js -->
                                <svg x-show="redirecting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;" x-cloak xmlns="http://w3.org">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Icono de Calendario Vectorial -->
                                <svg x-show="!redirecting" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>

                                <!-- Textos reactivos controlados por el tipo de registro -->
                                <span class="mt-1" x-text="redirecting ? 'Abriendo agenda...' : '{{ $result['type'] === 'clinic' ? 'Ver Especialistas' : 'Agendar Cita' }}'">
                                    {{ $result['type'] === 'clinic' ? 'Ver Especialistas' : 'Agendar Cita' }}
                                </span>
                            </a>


                            <p class="text-[9px] text-center text-slate-400 font-bold uppercase tracking-wider">Reserva directa garantizada</p>
                        </div>
                    </div>
                </div>
            @empty
                <!-- ESTADO VACÍO EN CASO DE NO COINCIDIR FILTROS -->
                <div class="text-center py-20 bg-white rounded-[2rem] border-2 border-dashed border-slate-200 p-6 shadow-inner">
                    <div class="mx-auto w-12 h-12 text-slate-300 mb-3 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-full">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z"/></svg>
                    </div>
                    <h4 class="text-base font-black text-slate-800 tracking-tight">Sin resultados exactos</h4>
                    <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto leading-relaxed">No encontramos clínicas ni especialistas con esos criterios de búsqueda en este momento.</p>
                    <a href="{{ route('search') }}" class="text-indigo-600 font-bold text-xs uppercase tracking-wider mt-3 inline-block hover:text-indigo-800">Limpiar todos los filtros</a>
                </div>
            @endforelse
        </div> <!-- Cierra el contenedor de las tarjetas (.space-y-6) -->

        <!-- ENLACES DE PAGINACIÓN ADAPTADOS -->
        <div class="mt-10">
            {{ $results->links() }}
        </div>

    </div> <!-- Cierra el contenedor interno principal (.max-w-7xl.mx-auto) -->
</div> <!-- Cierra el div del min-h-screen de resultados -->
</x-guest-layout>
