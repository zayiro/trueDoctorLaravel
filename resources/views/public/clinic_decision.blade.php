<x-guest-layout>
    <!-- Contenedor Base de Alta Conversión Unificado en Rejilla de Doble Columna -->
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5" 
         x-data="{ activeSpinnerId: null }">
        
        <!-- ENCABEZADO INSTITUCIONAL DE LA CLÍNICA (SaaS Corporativo de Dos Columnas) -->
        <div class="bg-white rounded-[2rem] p-6 mt-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition duration-200">
            
            <!-- FILA SUPERIOR: DOS COLUMNAS (Foto e Información) -->
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <!-- Columna 1: Foto / Logo Lateral Izquierdo con Ranquin -->
                <div class="flex-shrink-0 text-center mx-auto md:mx-0">
                    <div class="w-24 h-24 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-center shadow-inner overflow-hidden ring-4 ring-slate-50">
                        @if(isset($clinic->user->profile_photo_path) && $clinic->user->profile_photo_path)
                            <img src="{{ asset('storage/' . $clinic->user->profile_photo_path) }}" alt="{{ $clinic->name }}" class="w-full h-full object-cover">
                        @else
                            {{-- Heroicon: BuildingOffice2 --}}
                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        @endif
                    </div>
                    
                    <!-- SISTEMA MOLECULAR DE REPUTACIÓN DE LA CLÍNICA -->
                    <div class="mt-3 flex flex-col items-center justify-center gap-1">
                        <div class="flex items-center space-x-0.5 justify-center">
                            @php $clinicRating = round($clinic->rating ?? 5); @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-3.5 h-3.5 {{ $i <= $clinicRating ? 'text-yellow-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>
                <!-- Columna 2: Cuerpo de Información Centralizado (Metadata del Tenant) -->
                <div class="flex-1 min-w-0 w-full">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $clinic->name }}</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Verificado
                        </span>
                    </div>
                    
                    <!-- CONTEXTUALIZACIÓN DE ESPECIALIDADES DE LA CLÍNICA -->
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @if($showingAllStaffFallback)
                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded-md uppercase tracking-wide border border-amber-100 flex items-center gap-1">
                                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 11.517 1.28l-.04.02-1.062.532a.75.75 0 00-.362.63v1.11a.75.75 0 001.5 0v-.855l.808-.404a2.25 2.25 0 001.252-2.011 2.25 2.25 0 00-4.5 0 .75.75 0 001.5 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5h.008v.008H12v-.008z" />
                                </svg>
                                Mostrando Staff Médico Completo
                            </span>
                        @else
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md uppercase tracking-wide border border-blue-100">
                                Especialistas adscritos en: <span class="text-slate-900">{{ $specialty->name }}</span>
                            </span>
                        @endif
                    </div>
                    <!-- Rejilla de Datos Clínico-Legales e Infraestructura -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-50 text-xs">
                        <div>
                            <div class="font-black uppercase text-slate-400 tracking-wider">Identificación</div>
                            <div class="font-bold text-slate-700 mt-0.5">NIT {{ $clinic->nit ?? 'No registrado' }}</div>
                        </div>
                        <div>
                            <div class="font-black uppercase text-slate-400 tracking-wider">Código REPS</div>
                            <div class="font-bold text-slate-700 mt-0.5">{{ $clinic->reps_code ?? 'Habilitado' }}</div>
                        </div>
                        <div>
                            <div class="font-black uppercase text-slate-400 tracking-wider">Sedes Habilitadas</div>
                            <div class="font-bold text-slate-700 mt-0.5">{{ $clinicAddresses->count() }} {{ $clinicAddresses->count() === 1 ? 'Sede' : 'Sedes' }}</div>
                        </div>
                        <div>
                            <div class="font-black uppercase text-slate-400 tracking-wider">Staff Disponible</div>
                            <div class="font-bold text-slate-700 mt-0.5">{{ count($results) }} {{ count($results) === 1 ? 'Especialista' : 'Especialistas' }}</div>
                        </div>
                    </div>
                    <!-- SECCIÓN DINÁMICA: DIRECCIONES DE ATENCIÓN -->
                    <div class="mt-4 pt-3 border-t border-slate-50">
                        <div class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Direcciones de Atención Habilitadas</div>
                        
                        <!-- Listado de sedes de la clínica -->
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50/60 p-3 rounded-2xl border border-slate-100/50">
                            @foreach($clinicAddresses as $addr)
                                <div class="flex items-start gap-2 text-xs text-slate-600 p-1.5 hover:bg-white rounded-xl transition duration-150">
                                    <svg class="w-3.5 h-3.5 mt-0.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-800 block truncate">{{ $addr->name ?? 'Consultorios de la Clínica' }}</span>
                                        <span class="text-[11px] text-slate-500 block truncate">{{ $addr->address_line ?? $addr->address }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div> <!-- Cierre de Columna 2 -->

            </div> <!-- Cierre de Fila Superior -->
            <!-- FILA INFERIOR: CANAL DIRECTO (Reaseguración UX de Ancho Completo) -->
            <div class="mt-4 pt-4 border-t border-slate-100/80 w-full">
                <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100/60 sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider shrink-0">
                        <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                        Canal Directo de Admisión
                    </div>
                    <p class="leading-relaxed text-sm text-slate-500 font-medium md:max-w-2xl mt-2">
                        El espacio que elijas quedará asegurado de inmediato.
                    </p>
                </div>
            </div>

        </div> <!-- Cierre del Encabezado de la Clínica -->
        <!-- ALERTA DE FALLBACK: SE ACTIVA SI NO HUBO MÉDICOS EN LA ESPECIALIDAD SOLICITADA -->
        @if($showingAllStaffFallback && (request()->filled('specialty') || isset($specialtySlug)))
            <div class="p-4 mt-6 text-sm text-amber-800 rounded-[1.5rem] bg-amber-50/80 border border-amber-200 flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div>
                    <span class="font-black uppercase tracking-wider block text-xs mb-0.5 text-amber-900">Nota Institucional</span>
                    No registramos turnos disponibles para la especialidad solicitada actualmente. Para garantizar tu atención, ponemos a tu disposición nuestro <span class="font-bold">Staff Médico Habilitado Completo</span>.
                </div>
            </div>
        @endif

        @if (!empty($results))
        <div class="bg-white rounded-2xl mt-6 p-4 border border-slate-100 flex items-center shadow-sm">
            <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider shrink-0">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
                Selecciona un especialista del Staff
            </div>
        </div>
        @endif
        
        <!-- SECCIÓN: CUERPO MÉDICO EN FILAS DE DOS TARJETAS (DISEÑO UX APILADO SIMÉTRICO) -->
<div class="mx-auto mt-5">            
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($results as $result)                
            <!-- Tarjeta con Estructura Apilada: Información Superior, Acción Inferior -->
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition duration-200">
                
                <!-- FILA SUPERIOR: FOTO + CUERPO DE INFORMACIÓN -->
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    
                    <!-- A. Foto / Icono Lateral Izquierdo -->
                    <div class="flex-shrink-0 text-center mx-auto sm:mx-0 w-24">
                        <div class="w-24 h-24 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-center shadow-inner overflow-hidden ring-4 ring-slate-50">
                            @if($result['user'] && $result['user']->profile_photo_path)
                                <img src="{{ asset('storage/' . $result['user']->profile_photo_path) }}" alt="{{ $result['title'] }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                </svg>
                            @endif
                        </div>

                        <!-- Sistema de Reputación Propio del Doctor -->
                        <div class="mt-3 flex items-center space-x-0.5 justify-center">
                            @php $doctorRating = round($result['rating'] ?? 5); @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-3 h-3 {{ $i <= $doctorRating ? 'text-yellow-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                    </div> <!-- Fin Foto / Icono -->
                    <!-- B. Cuerpo de Información Centralizado -->
                    <div class="flex-1 min-w-0 w-full mt-3 sm:mt-0">                       
                        <h3 class="text-xl font-black text-slate-900 tracking-tight truncate">{{ $result['title'] }}</h3>
                        
                        <div class="flex flex-wrap gap-1.5 mt-1">
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md uppercase tracking-wide border border-blue-100">
                                {{ $result['badge_text'] }}
                            </span>
                        </div>

                        <!-- Detalles de la Sede y Dirección -->
                        <div class="mt-3 pt-2 border-t border-slate-100">
                            <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Dirección de Atención</div>
                            <div class="flex items-start gap-1.5 text-[11px] text-slate-600">
                                <svg class="w-3.5 h-3.5 mt-0.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                <div class="truncate">
                                    <span class="font-semibold text-slate-700 block truncate">{{ $result['subtitle'] }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Catálogo Rápido de Servicios Institucionales -->
                        <div class="mt-2">
                            @php 
                                $addressModel = $clinicAddresses->firstWhere('id', $result['address_id']);
                                $addressServices = $addressModel && $addressModel->relationLoaded('services') 
                                    ? $addressModel->services->where('active', true)->take(2) 
                                    : collect();
                            @endphp
                            <div class="flex flex-wrap gap-1">
                                @forelse($addressServices as $service)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-200/60">
                                        {{ $service->name }}
                                    </span>
                                @empty
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-50/50 text-slate-400 border border-dotted border-slate-200">
                                        Consulta institucional
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Gancho de Inmediatez Nativo (Realtime Pulse) -->
                        @if(isset($result['next_turn']) && $result['next_turn'])
                            <div class="mt-3 inline-flex items-center gap-2 px-2 py-0.5 bg-emerald-50 border border-emerald-100/70 text-emerald-800 text-[10px] font-black rounded-lg shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block {{ str_contains($result['next_turn'], 'Sin turnos') ? '' : 'animate-pulse' }}"></span>
                                {{ str_contains($result['next_turn'], 'Sin turnos') ? $result['next_turn'] : 'Disponible: ' . ucfirst($result['next_turn']) }}
                            </div>
                        @endif
                    </div> <!-- Cierre B. Cuerpo de Información -->

                </div> <!-- Cierre Fila Superior (Foto + Info) -->
                <!-- FILA INFERIOR: BOTÓN ACCIONABLE DE ANCHO COMPLETO -->
                <div class="mt-5 pt-4 border-t border-slate-100/70 w-full">
                    <a href="{{ route('partner.public.profile', [
                                    'slug'       => $result['slug'], 
                                    'clinic_id'  => $clinic->id, 
                                    'address_id' => $result['address_id'], 
                                    'specialty'  => $specialty ? $specialty->slug : 'general'
                                ]) }}" 
                        @click="activeSpinnerId = 'doc-{{ $result['id'] }}'"
                        :class="activeSpinnerId === 'doc-{{ $result['id'] }}' ? 'opacity-80 cursor-not-allowed bg-blue-700 pointer-events-none transform-none' : 'bg-blue-600 hover:bg-blue-700'"
                        class="w-full text-white font-black text-[11px] uppercase tracking-wider text-center py-3.5 px-6 rounded-xl shadow-md shadow-blue-500/10 transition-all transform flex items-center justify-center gap-2.5 min-h-[46px] select-none">
                        
                        <!-- Spinner de carga -->
                        <svg x-show="activeSpinnerId === 'doc-{{ $result['id'] }}'" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <!-- Icono de Calendario Vectorial -->
                        <svg x-show="activeSpinnerId !== 'doc-{{ $result['id'] }}'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>

                        <span class="mt-0.5" x-text="activeSpinnerId === 'doc-{{ $result['id'] }}' ? 'Abriendo agenda...' : 'Agendar Cita'">
                            Agendar Cita
                        </span>
                    </a>
                </div>

            </div> <!-- Cierre de la tarjeta médica individual -->
        @empty
            <!-- Estado vacío institucional reactivo al contexto -->
            <div class="col-span-full bg-white rounded-[2rem] p-12 text-center border border-dashed border-slate-200">
                <div class="w-12 h-12 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m0 0-.003-.031a3 3 0 0 1 4.682-2.72.641.641 0 0 0 .603-.078 8.154 8.154 0 0 1 1.44-.44M6 18.719c-1.399-.142-2.742-.551-3.93-1.183a1.125 1.125 0 0 1-.611-1.025c0-1.078.618-2.03 1.547-2.455a11.986 11.986 0 0 1 4.258-1.205m0 0a8.03 8.03 0 0 1 1.439-.078m12.18 2.445c.83-.308 1.432-1.101 1.432-2.023 0-.616-.33-1.172-.857-1.482A11.801 11.801 0 0 0 15.5 11.5M12 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5.25 2.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM4.5 11.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">No se encontraron profesionales</p>
                <p class="text-slate-400 text-[11px] mt-1 max-w-sm mx-auto">
                    @if($showingAllStaffFallback)
                        La institución no registra médicos activos en su nómina actualmente.
                    @else
                        No registramos médicos disponibles para la especialidad solicitada en esta clínica en este momento.
                    @endif
                </p>
            </div>
        @endforelse
    </div> <!-- Cierre grid-cols-1 md:grid-cols-2 -->
</div> <!-- Cierre de la sección contenedor del cuerpo médico -->


    </div> <!-- Cierre absoluto del contenedor principal max-w-7xl x-data -->
</x-guest-layout>
