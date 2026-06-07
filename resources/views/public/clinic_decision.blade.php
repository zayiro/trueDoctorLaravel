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
                    
                    <div class="flex flex-wrap gap-1.5 mt-0.5">
                        <span class="px-2 py-0.5 bg-blue-50/60 text-blue-600 text-[10px] font-bold rounded-md uppercase tracking-wide border border-blue-100/40">
                            Especialistas adscritos en <span class="decoration-2 decoration-indigo-500/20">{{ $specialty->name }}</span>
                        </span>
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

                    <!-- SECCIÓN DINÁMICA: DIRECCIONES DE ATENCIÓN (DESPLEGABLE UX) -->
                    <div class="mt-4 pt-3 border-t border-slate-50">
                        <div class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Direcciones de Atención Habilitadas</div>
                        
                        <!-- Listado colapsable de sedes de la clínica -->
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50/60 p-3 rounded-2xl border border-slate-100/50">
                            @foreach($clinicAddresses as $addr)
                                <div class="flex items-start gap-2 text-xs text-slate-600 p-1.5 hover:bg-white rounded-xl transition duration-150">
                                    <svg class="w-3.5 h-3.5 mt-0.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-800 block truncate">{{ $addr->name ?? 'Consultorios de la Clínica' }}</span>
                                        <span class="text-[11px] text-slate-500 block truncate">{{ $addr->address ?? $addr->street }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>

            </div>

            <!-- FILA INFERIOR: CANAL DIRECTO (Reaseguración UX de Ancho Completo) -->
            <div class="mt-4 pt-4 border-t border-slate-100/80 w-full">
                <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100/60 sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider shrink-0">
                        {{-- Heroicon: ShieldCheck --}}
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

        </div>

        <div class="bg-white rounded-[2rem] mt-6 shadow-sm border border-slate-100 flex flex-col gap-6 shadow-md transition duration-200">
            <div class="bg-white p-4 rounded-2xl border border-slate-100/60 sm:items-center">
                <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider shrink-0">
                    {{-- Heroicon: ShieldCheck --}}
                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                    </svg>
                    Selecciona un especialista
                </div>
            </div>
        </div>



        <!-- SECCIÓN: CUERPO MÉDICO EN FILAS DE DOS TARJETAS (NUEVO DISEÑO UX APILADO) -->
        <div class="mx-auto mt-5 gap-2">            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($results as $result)                
                    <!-- Tarjeta con Estructura Apilada: Información Superior, Acción Inferior -->
                    <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition duration-200">
                        
                        <!-- FILA SUPERIOR: FOTO + CUERPO DE INFORMACIÓN -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            
                            <!-- A. Foto / Icono Lateral Izquierdo -->
                            <div class="flex-shrink-0 text-center mx-auto md:mx-0">
                                <div class="w-24 h-24 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-center shadow-inner overflow-hidden ring-4 ring-slate-50">
                                    @if($result['user'] && $result['user']->profile_photo_path)
                                        <img src="{{ \Storage::url($result['user']->profile_photo_path) }}" class="w-full h-full object-cover">
                                    @else
                                    <div class="w-12 h-12 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-2xl mx-auto mb-3">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                        </div>
                                    @endif
                                </div>


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

                            <!-- B. Cuerpo de Información Centralizado -->
                            <div class="flex-1 min-w-0">                       
                                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ $result['title'] }}</h3>
                                <div class="flex flex-wrap gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 bg-blue-50/60 text-blue-600 text-[10px] font-bold rounded-md uppercase tracking-wide border border-blue-100/40">
                                        {{ $result['badge_text'] }}
                                    </span>
                                </div>

                                <!-- Estrellas de Reputación Comunes -->
                                <div class="flex items-center space-x-1 mt-2">
                                    @php $ratingScore = round($result['rating'] ?? 0); @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3.5 h-3.5 {{ $i <= $ratingScore ? 'text-yellow-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    @endfor
                                    <span class="ml-1.5 text-[10px] font-bold text-slate-400">Garantizado</span>
                                </div>

                                <!-- Detalles de la Sede y Dirección -->
                                <div class="mt-3 pt-2.5 border-t border-slate-50">
                                    <div class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Dirección de Atención</div>
                                    <div class="flex items-start gap-1.5 text-[11px] text-slate-600 mt-1">
                                        <svg class="w-3.5 h-3.5 mt-0.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        <div class="truncate"><span class="font-semibold text-slate-700">{{ $result['subtitle'] }}</span></div>
                                    </div>
                                </div>

                                <!-- Catálogo Rápido de Servicios Vinculados de la Sede -->
                                <div class="flex flex-wrap gap-1 mt-2 mb-2">
                                    @php 
                                        $currentAddress = \App\Models\Address::find($result['address_id']);
                                        $addressServices = $currentAddress ? $currentAddress->services()->where('services.active', true)->take(2)->get() : collect();
                                    @endphp
                                    @if($addressServices->isNotEmpty())
                                        @foreach($addressServices as $service)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-200/60">{{ $service->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-[9px] text-slate-400 italic">Consulta clínica</span>
                                    @endif
                                </div>

                                <!-- 🔥 EL GANCHO DE INMEDIATEZ NATIVO REPLICADO -->
                                @if($result['next_turn'])
                                    <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 bg-emerald-50 border border-emerald-100/70 text-emerald-800 text-[10px] font-black rounded-lg shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                        Disponible: {{ ucfirst($result['next_turn']) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- FILA INFERIOR: BOTÓN ACCTIONABLE DE ANCHO COMPLETO -->
                        <div class="mt-5 pt-4 border-t border-slate-100/70 w-full">
                            <a href="{{ route('partner.public.profile', [
                                            'slug'       => $result['slug'], 
                                            'clinic_id'  => $clinic->id, 
                                            'address_id' => $result['address_id'], 
                                            'specialty'  => $specialty->slug
                                        ]) }}" 
                                @click="activeSpinnerId = 'doc-{{ $result['id'] }}'"
                                :class="activeSpinnerId === 'doc-{{ $result['id'] }}' ? 'opacity-80 cursor-not-allowed bg-blue-700 pointer-events-none transform-none' : 'bg-blue-600 hover:bg-blue-700'"
                                class="w-full text-white font-black text-[11px] uppercase tracking-wider text-center py-3.5 px-6 rounded-xl shadow-md shadow-blue-500/10 transition-all transform flex items-center justify-center gap-2.5 min-h-[46px] select-none">
                                
                                <!-- Spinner de carga aislado por ID -->
                                <svg x-show="activeSpinnerId === 'doc-{{ $result['id'] }}'" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Icono de Calendario Vectorial -->
                                <svg x-show="activeSpinnerId !== 'doc-{{ $result['id'] }}'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>

                                <span class="mt-1" x-text="activeSpinnerId === 'doc-{{ $result['id'] }}' ? 'Abriendo agenda...' : 'Agendar Cita'">
                                    Agendar Cita
                                </span>
                            </a>
                        </div>

                    </div>
                @empty
                    <!-- Empty state molecular cuadrícula -->
                    <div class="col-span-full bg-white rounded-[2rem] p-12 text-center border border-dashed border-slate-200">
                        <p class="text-xs font-semibold text-slate-400">No se registran médicos activos para esta especialidad en esta clínica actualmente.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>
</x-guest-layout>
