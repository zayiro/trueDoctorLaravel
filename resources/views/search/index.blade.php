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
                        <p class="text-sm text-slate-600 leading-relaxed font-medium dark:text-gray-300">
                            No encontramos <span class="font-extrabold text-amber-600 dark:text-amber-400">{{ $expertName }}</span> con atención física en <span class="font-black text-slate-900 dark:text-white underline decoration-amber-400 decoration-2">{{ $targetCity->name }}</span>, pero estos especialistas están disponibles en otras ubicaciones de manera virtual o presencial.
                        </p>
                    </div>
                </div>

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
        
        @if(session('info'))
            <div class="p-4 mb-6 text-sm text-blue-800 rounded-2xl bg-blue-50 border border-blue-200/60 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-900 flex items-center gap-3 shadow-sm animate-fade-in" role="alert">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063 1.06l-.041.02a.75.75 0 01-1.063-1.06zm0-3.75a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z" />
                </svg>
                <div>
                    <span class="font-bold">Aviso del sistema:</span> {{ session('info') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div x-data Bled="{ show: true }" x-show="show" x-transition class="flex items-center justify-between p-4 mt-5 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="flex items-center">
                    <svg class="flex-shrink-0 w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
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
                        <!-- Heroicon: MagnifyingGlass -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                        </svg>
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
                        <!-- Heroicon: MapPin -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-0.5 sm:gap-2">
                        <span class="text-sm font-black text-slate-700 uppercase tracking-wider">Filtrar por ubicación:</span>
                        <span class="text-sm text-slate-500 font-medium">Elige una ciudad arriba para descubrir consultorios físicos o clínicas más cercanas.</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- CONTENEDOR GRID DE TARJETAS -->
        <div x-data="{ redirecting: false }" class="space-y-6">
            @forelse($results as $result)
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-6 hover:shadow-md transition duration-200">
                    <!-- Foto / Icono Lateral Izquierdo -->
                    <div class="flex-shrink-0 text-center">
                        @if($result['user'] && $result['user']->profile_photo_path)
                            <img src="{{ \Storage::url($result['user']->profile_photo_path) }}" class="w-24 h-24 rounded-2xl mx-auto mb-3 object-cover border-2 border-indigo-50 shadow-inner">
                        @else
                           <div class="w-24 h-24 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-2xl mx-auto mb-3">
                                @if($result['type'] === 'clinic')
                                    <!-- Heroicon: BuildingOffice2 -->
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                @else
                                    <!-- Heroicon: User -->
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                    </svg>
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
                            <!-- 🏛️ CARD DE CLÍNICA -->
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $result['title'] }}</h3>
                            <p class="text-sm font-bold text-indigo-600 mt-1 uppercase tracking-wide">{{ $result['badge_text'] }}</p>
                        @else
                            <!-- 🩺 CARD DE DOCTOR -->
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $result['title'] }}</h3>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[12px] font-black uppercase">
                                    @foreach($result['languages'] as $lang)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500">
                                            <img 
                                                src="{{ asset('images/flags/' . strtoupper($result['country_code']) . '.svg') }}"
                                                width="20"
                                                height="14"
                                                loading="lazy"
                                                alt="{{ $result['countryName'] }}"
                                                class="rounded-sm shadow-xs object-cover">
                                        </span>
                                    @endforeach
                                </span>

                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900">
                                    {{ $result['badge_text'] }}
                                </span>

                                <!-- 🧬 INDICADOR PREMIUM MULTI-ESPECIALIDAD -->
                                @if(($result['specialties_count'] ?? 0) > 1)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 animate-pulse dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900" 
                                        title="Este profesional cuenta con {{ $result['specialties_count'] }} especialidades médicas habilitadas">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                        @endif
                        
                        {{-- 🌐 IDIOMAS DEL ESPECIALISTA --}}
                        @if(!empty($result['languages'] ?? []))
                            <div class="flex items-center gap-1.5 flex-wrap mt-1.5">
                                <span class="text-sm text-slate-400">Idiomas de atención</span>
                                @foreach($result['languages'] as $lang)
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500">                                        
                                        {{ $lang['name'] }}
                                    </span>
                                    @if(!$loop->last)
                                        <span class="text-slate-300 text-xs">·</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Estrellas de Reputación Comunes -->
                        <div class="flex items-center space-x-1 mt-2.5">
                            @php $ratingScore = round($result['rating'] ?? 0); @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $ratingScore ? 'text-yellow-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://w3.org" aria-hidden="true">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                            <span class="ml-2 text-xs font-bold text-slate-400">Garantía OpenDoctorOnline</span>
                        </div>

                        <!-- Detalles de la Sede y Dirección Organizada por Canal -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-4">
                            <div class="text-[10px] font-black uppercase text-slate-400 tracking-widest">
                                <strong>Citas Disponibles</strong> 
                                @if($hasPhysical && $hasVirtual)
                                    <p>En Consultorio o Por Teleconsulta</p>
                                @elseif($hasPhysical)
                                    <p>Solo en Consultorio</p>
                                @elseif($hasVirtual)
                                    <p>Solo por Teleconsulta(virtuales)</p>
                                @endif
                            </div>

                            @php
                                // Extraemos todas las sedes asociadas al modelo Eloquent de forma segura
                                $allAddresses = $result['model']->addresses ?? collect();
                                $virtualAddresses = $allAddresses->where('type', 'virtual');
                                $physicalAddresses = $allAddresses->where('type', 'physical');
                                
                                // Inyectamos localmente el AvailabilityService para resolver turnos en Blade
                                $availabilityService = app(\App\Services\AvailabilityService::class);
                                
                                // Preparamos la matriz de doctores según el tipo de registro
                                $doctorIds = $result['type'] === 'clinic' 
                                    ? $result['model']->doctors->pluck('id')->toArray() 
                                    : [$result['id']];
                                    
                                $clinicId = $result['type'] === 'clinic' ? $result['id'] : null;
                            @endphp

                            <!-- 🖥️ CANAL VIRTUAL (TELEMEDICINA FIRST) -->
                            @foreach($virtualAddresses as $vAddr)
                                @php
                                    $vTurn = $availabilityService->getNextAvailableTurn($doctorIds, $vAddr->id, $clinicId);
                                    $vTurnText = $vTurn ? ucfirst($vTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles';
                                @endphp
                                <div class="bg-indigo-50/40 border border-indigo-100/70 rounded-2xl p-3.5 space-y-2">
                                    <div class="flex items-start gap-2 text-xs text-indigo-950">
                                        <!-- Heroicon: ComputerDesktop -->
                                        <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                        </svg>
                                        <div>
                                            <h5 class="font-extrabold text-indigo-900 text-xs uppercase tracking-wide">Consulta Virtual</h5>
                                            <p class="font-medium text-slate-500 text-[11px] mt-0.5">{{ $vAddr->name }} • Plataforma Online</p>
                                        </div>
                                    </div>

                                    <!-- Catálogo de servicios de la sede virtual -->
                                    <div class="flex flex-wrap gap-1 pl-6">
                                        @forelse($vAddr->services->where('active', true) as $service)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-white text-indigo-700 border border-indigo-100 shadow-3xs">{{ $service->name }}</span>
                                        @empty
                                            <span class="text-[11px] text-slate-400 italic">Videoconsulta habilitada</span>
                                        @endforelse
                                    </div>                                    
                                </div>
                            @endforeach

                            <!-- 🏥 CANAL PRESENCIAL (SEDES FÍSICAS SUBSECUENTES CON DESPLIEGUE CONTROLADO) -->
                            @if($physicalAddresses->isNotEmpty())
                                <!-- Inicializamos Alpine.js controlando si mostramos el catálogo oculto -->
                                <div x-data="{ expanded: false }" class="space-y-3">
                                    
                                    @foreach($physicalAddresses as $index => $pAddr)
                                        @php
                                            $pTurn = $availabilityService->getNextAvailableTurn($doctorIds, $pAddr->id, $clinicId);
                                            $pTurnText = $pTurn ? ucfirst($pTurn->isoFormat('dddd D [de] MMMM — h:mm A')) : 'Sin turnos próximos disponibles';
                                        @endphp

                                        <!-- Las primeras 2 sedes se muestran siempre. De la tercera en adelante dependen de 'expanded' -->
                                        <div 
                                            @if($index >= 2) 
                                                x-show="expanded" 
                                                x-collapse
                                                x-cloak
                                            @endif
                                            class="bg-slate-50/60 border border-slate-200/60 rounded-2xl p-3.5 space-y-2 transition-all duration-300"
                                        >
                                            <div class="flex items-start gap-2 text-xs text-slate-800">
                                                <!-- Heroicon: MapPin -->
                                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                                </svg>
                                                <div>
                                                    <h5 class="font-extrabold text-slate-700 text-xs uppercase tracking-wide">Consulta Presencial</h5>
                                                    <p class="font-medium text-slate-500 text-[11px] mt-0.5">{{ $pAddr->name }} • <span class="font-bold text-slate-700">{{ $pAddr->address }} - {{ $pAddr->city->name ?? '' }}</span></p>
                                                </div>
                                            </div>

                                            <!-- Catálogo de servicios de la sede física -->
                                            <div class="flex flex-wrap gap-1 pl-6">
                                                @forelse($pAddr->services->where('active', true) as $service)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-white text-slate-600 border border-slate-200/80 shadow-3xs">{{ $service->name }}</span>
                                                @empty
                                                    <span class="text-[11px] text-slate-400 italic">Atención general presencial</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- 👁️ BOTÓN DISPARADOR DINÁMICO (Solo aparece si el especialista supera las 2 sedes físicas) -->
                                    @if($physicalAddresses->count() > 2)
                                        <div class="pt-1">
                                            <button 
                                                @click="expanded = !expanded" 
                                                type="button"
                                                class="inline-flex items-center gap-2 text-[11px] font-black text-indigo-600 bg-indigo-50 hover:bg-indigo-100/80 px-3 py-2 rounded-xl border border-indigo-100 transition-all select-none focus:outline-none"
                                            >
                                                <!-- Icono dinámico Ojo abierto / Ojo cerrado -->
                                                <svg x-show="!expanded" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <svg x-show="expanded" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" style="display: none;" x-cloak>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                                
                                                <span x-text="expanded ? 'Ocultar sucursales adicionales' : 'Ver {{ $physicalAddresses->count() - 2 }} sedes físicas más'">
                                                    Ver {{ $physicalAddresses->count() - 2 }} sedes físicas más
                                                </span>
                                            </button>
                                        </div>
                                    @endif

                                </div>
                            @endif
                        </div>

                        <!-- 🔥 EL GANCHO DE INMEDIATEZ EN TIEMPO REAL -->
                        @if($result['next_turn'])
                            <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 {{ str_contains($result['next_turn'], 'Sin turnos') ? 'bg-slate-50 border-slate-200 text-slate-500' : 'bg-emerald-50 border-emerald-100/70 text-emerald-800' }} text-sm font-black rounded-xl shadow-sm">
                                <span class="w-2 h-2 rounded-full {{ str_contains($result['next_turn'], 'Sin turnos') ? 'bg-slate-400' : 'bg-emerald-500 animate-pulse' }} inline-block"></span>
                                @if(str_contains($result['next_turn'], 'Sin turnos'))
                                    {{ $result['next_turn'] }}
                                @else
                                    Próximo turno disponible: {{ $result['next_turn'] }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <!-- Columna de Botones (Acciones Unificadas e Inteligentes) -->
                    <div class="flex flex-col justify-center gap-2.5 min-w-[200px] border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 px-2 dark:border-gray-700">
                        <div x-data="{ redirecting: false }" @restore-booking-buttons.window="redirecting = false" class="flex flex-col gap-2 w-full">
                            
                            <!-- BOTÓN ADAPTATIVO: Enrutamiento quirúrgico Multi-Tenant -->
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
                                @click="
                                    redirecting = true;
                                    if (typeof gtag === 'function') {
                                        const isClinic = '{{ $result['type'] }}' === 'clinic';
                                        if (isClinic) {
                                            gtag('event', 'click_view_clinic_specialists', {
                                                'clinic_name': '{{ addslashes($result['name'] ?? $result['title'] ?? 'Clínica sin nombre') }}',
                                                'search_city': '{{ request('city', 'No especificada') }}'
                                            });
                                        } else {
                                            gtag('event', 'click_schedule_appointment', {
                                                'doctor_name': '{{ addslashes($result['name'] ?? $result['title'] ?? 'Médico sin nombre') }}',
                                                'specialty': '{{ request('specialty', 'No especificada') }}'
                                            });
                                        }
                                    }
                                "
                                :class="redirecting ? 'opacity-80 cursor-not-allowed bg-blue-700 pointer-events-none transform-none' : 'bg-blue-600 hover:bg-blue-700 active:scale-[0.99] hover:-translate-y-0.5'"
                                class="w-full sm:w-auto text-white font-black text-[11px] uppercase tracking-wider text-center py-3.5 px-6 rounded-xl shadow-md shadow-blue-500/10 transition-all transform flex items-center justify-center gap-2.5 min-h-[46px] select-none">
                                
                                <!-- Spinner reactivo de Alpine.js -->
                                <svg x-show="redirecting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;" x-cloak xmlns="http://w3.org">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Icono de Calendario Vectorial Purificado -->
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
            <div class="flex flex-col items-center text-center py-12 px-6 gap-6 bg-white rounded-3xl border border-slate-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                
                <div class="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center dark:bg-indigo-950/40">
                    <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                </div>

                <div class="max-w-md space-y-2">
                    <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">
                        Especialista no disponible aún en OpenDoctorOnline
                    </h4>
                    <p class="text-sm text-slate-500 leading-relaxed dark:text-gray-400">
                        Estamos en pleno lanzamiento. El especialista que buscas aún está configurando su agenda, pero puedes agendar con un médico general disponible ahora mismo.
                    </p>
                </div>

                <div class="flex flex-wrap justify-center gap-3">
                    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 dark:bg-emerald-950/30 dark:border-emerald-900">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                        </svg>
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Telemedicina inmediata disponible</span>
                    </div>
                    <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-2.5 dark:bg-indigo-950/30 dark:border-indigo-900">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">Médicos generales listos ahora</span>
                    </div>
                </div>

                <a href="{{ url('/search') }}?specialty=medicina-general" 
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-black px-6 py-3 rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:scale-[0.99]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    Consultar con medicina general
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>

                <p class="text-xs text-slate-400 dark:text-gray-500">
                    Evaluación inicial y derivación al especialista que necesitas
                </p>
            </div>
            @endforelse
            
            @push('scripts')
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof gtag === 'function' && {{ $results->count() }} > 0) {
                    const items = [
                        @foreach($results as $result)
                        {
                            item_id: '{{ $result['id'] }}',
                            item_name: '{{ addslashes($result['name'] ?? $result['title'] ?? 'Sin nombre') }}',
                            item_category: '{{ $result['type'] === 'clinic' ? 'clinic' : 'doctor' }}',
                        },
                        @endforeach
                    ];

                    gtag('event', 'view_item_list', {
                        item_list_id: 'search_results',
                        item_list_name: 'Search Results',
                        items: items
                    });
                }
            });
            </script>
            @endpush
        </div> <!-- Cierra el contenedor de las tarjetas (.space-y-6) -->

        <!-- ENLACES DE PAGINACIÓN ADAPTADOS -->
        <div class="mt-10">
            {{ $results->links() }}
        </div>

    </div> <!-- Cierra el contenedor interno principal (.max-w-7xl.mx-auto) -->
</div> <!-- Cierra el div del min-h-screen de resultados -->
</x-guest-layout>
