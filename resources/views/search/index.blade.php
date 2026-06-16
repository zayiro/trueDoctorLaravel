<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">

        @if($showingSuggestions)
            <!-- 🚨 BANNER DE ALTO CONTRASTE CON CIERRE REACTIVO LOCAL EN ALPINE.JS -->
            <div x-data="{ show: true }" 
                x-show="show" 
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="mt-8 mb-8 bg-white border-l-4 border-amber-500 rounded-2xl shadow-xs border-y border-r border-slate-200/60 p-5 flex items-start justify-between gap-4 dark:bg-gray-800 dark:border-l-amber-500 dark:border-y-gray-700 dark:border-r-gray-700">
                
                <div class="flex items-start gap-4">
                    <!-- Icono de Alerta Rediseñado con Fondo Sólido Ámbar para Enfoque Inmediato -->
                    <div class="p-2.5 bg-amber-50 rounded-xl text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 shrink-0 border border-amber-100/50 dark:border-amber-900/30">
                        <!-- Heroicons: LightBulb -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v3m0 0h.008v.008H12V21zm0-6h.008v.008H12V15zm0-6h.008v.008H12V9zm0-6h.008v.008H12V3zM3.22 8.22a.75.75 0 011.06 0L12 15.69l7.72-7.47a.75.75 0 111.06 1.06l-8.25 8a.75.75 0 01-1.06 0l-8.25-8a.75.75 0 010-1.06z"></path>
                        </svg>
                    </div>
                    
                    <div class="space-y-1">
                        <h3 class="font-black text-slate-800 text-base tracking-tight dark:text-white flex items-center gap-2">
                            Búsqueda Optimizada
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-wider dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900">Cobertura Ampliada</span>
                        </h3>
                        <!-- Mensaje Empático Requerido con Variables Dinámicas en Contraste Premium -->
                        <p class="text-sm text-slate-600 leading-relaxed font-medium dark:text-gray-300">
                            No encontramos <span class="font-extrabold text-amber-600 dark:text-amber-400">{{ $expertName }}</span> con atención física en <span class="font-black text-slate-900 dark:text-white underline decoration-amber-400 decoration-2">{{ $targetCity->name }}</span>, pero estos especialistas están disponibles en otras ubicaciones de manera virtual o presencial.
                        </p>
                    </div>
                </div>

                <!-- ❌ BOTÓN DE CIERRE MAESTRO: Heroicons SVG Nativo (X-Mark) -->
                <button @click="show = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 p-1.5 rounded-xl transition-all shrink-0 focus:outline-none focus:ring-2 focus:ring-amber-500" 
                        title="Cerrar aviso">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
        
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
                        <span class="text-sm font-black text-slate-700 uppercase tracking-wider">Filtrar por ubicación:</span>
                        <span class="text-sm text-slate-500 font-medium">Elige una ciudad arriba para descubrir consultorios físicos o clínicas más cercanas.</span>
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
                        <span class="text-[12px] font-black uppercase px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-md border border-indigo-100/30 tracking-wider">
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
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <!-- Badge Base de la Especialidad Buscada -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900">
                                    {{ $result['badge_text'] }}
                                </span>

                                <!-- 🧬 INDICADOR PREMIUM MULTI-ESPECIALIDAD -->
                                @if(($result['type'] ?? '') === 'doctor' && ($result['specialties_count'] ?? 0) > 1)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 animate-pulse dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900" 
                                        title="Este profesional cuenta con {{ $result['specialties_count'] }} especialidades médicas habilitadas">
                                        <!-- Heroicons SVG Nativo: Plus (Compacto y elegante) -->
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                        </svg>
                                    </span>
                                @endif
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
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[12px] font-bold bg-slate-50 text-slate-600 border border-slate-200/60">{{ $service->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-[12px] text-slate-400 italic">Consulta general</span>
                                @endif
                            </div>                                    
                        </div>

                        <!-- 🔥 EL GANCHO DE INMEDIATEZ (Muestra: Próximo turno disponible: Hoy a las 10:30 AM) -->
                        @if($result['next_turn'])
                            <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-100/70 text-emerald-800 text-sm font-black rounded-xl shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                Próximo turno disponible: {{ $result['next_turn'] }}
                            </div>
                        @endif
                    </div>
                    
                    <!-- Columna de Botones (Acciones Unificadas e Inteligentes con Bifurcación Semántica) -->
                    <div class="flex flex-col justify-center gap-2.5 min-w-[200px] border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 px-2">
                        <div x-data="{ redirecting: false }" @restore-booking-buttons.window="redirecting = false" class="flex flex-col gap-2 w-full">
                            
                            <!-- BOTÓN ADAPTATIVO: Detecta el tipo de inquilino (Tenant) para cambiar el destino y los parámetros -->
                            <a href="{{ $result['type'] === 'clinic' 
                                            ? route('partner.clinic.public.decision', [
                                                'slug'           => $result['slug'], 
                                                'specialty_slug' => request('specialty'), 
                                                'city'           => request('city')
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
                <div class="text-center p-8 sm:p-12 border border-dashed border-slate-200 bg-white rounded-2xl flex flex-col items-center dark:bg-gray-800 dark:border-gray-700">
                    <!-- SVG Nativo: Magnifying-Glass de Heroicons -->
                    <svg class="w-10 h-10 text-slate-400 mb-3" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                    </svg>
                    
                    <h4 class="font-bold text-slate-900 text-lg mb-1 dark:text-white">
                        No hay especialistas directos asignados en este momento
                    </h4>
                    
                    <p class="text-slate-500 text-sm max-w-md mb-5 dark:text-slate-400">
                        Contamos con médicos generales e institucionales listos para evaluar tu caso de forma inicial y derivarte correctamente.
                    </p>
                    
                    {{-- 🔒 ENLACE ESTRATÉGICO: Filtra directamente por Medicina General y deja la ciudad abierta --}}
                    <a href="{{ url('/search') }}?specialty=medicina-general&city=" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-6 py-3 rounded-full transition shadow-sm focus:ring-4 focus:ring-blue-200">
                        Consultar con Medicina General
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
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
