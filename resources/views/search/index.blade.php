<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        @if(session('info'))
            <div class="p-4 mb-6 text-sm text-blue-800 rounded-2xl bg-blue-50 border border-blue-200/60 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-900 flex items-center gap-3 shadow-sm animate-fade-in" role="alert">
                {{-- Icono de Información (Flowbite Icons) --}}
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 5a1 1 0 1 1 2 0v2a1 1 0 1 1-2 0V5Zm1.5 10a1 1 0 1 1-2 0v-4a1 1 0 1 1 2 0v4Z"/>
                </svg>
                <div>
                    <span class="font-bold">Aviso del sistema:</span> {{ session('info') }}
                </div>
            </div>
        @endif

        <!-- CONTROL DE ALERTAS TRANSACCIONALES CON ALPINE.JS -->
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between p-4 mt-5 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="flex items-center">
                    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                </div>
                <button type="button" @click="show = false" class="text-red-400 hover:text-red-600 font-black text-sm px-2">×</button>
            </div>
        @endif

        <!-- BARRA DE FILTROS NATIVA (INMUNE A LIVEWIRE) -->
        <form x-data="{ loading: false }" 
            x-on:submit="loading = true"
            action="{{ route('search') }}" 
            method="GET" 
            class="bg-white mt-8 p-4 rounded-[1.5rem] shadow-md flex flex-wrap items-center gap-4 mb-8 border border-slate-100">
            
            <div class="flex-1 min-w-[200px]">
                <label for="specialty" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Especialidad</label>
                <select name="specialty" id="specialty" class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 text-sm">
                    <option value="">¿Qué especialidad buscas?</option>
                    @foreach($specialties as $s)
                        <option value="{{ $s->slug }}" {{ request('specialty') == $s->slug ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px] border-l border-slate-100 pl-4">
                <label for="city" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">¿Dónde?</label>
                <select name="city" id="city" class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 text-sm">
                    <option value="">Todas las ciudades</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->slug }}" {{ request('city') == $city->slug ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>           

            <!-- Botón Buscar Dinámico con Alpine.js -->
            <div class="pt-5">
                <button type="submit" 
                        :disabled="loading"
                        :class="loading ? 'opacity-75 cursor-not-allowed bg-indigo-500 shadow-none' : 'bg-indigo-600 hover:bg-indigo-700'"
                        class="text-white px-8 py-3 rounded-2xl font-bold text-sm transition shadow-md shadow-indigo-100 uppercase tracking-wider flex items-center justify-center gap-2 min-w-[140px]">
                    
                    <!-- Icono Spinner SVG Animado (Solo visible al cargar) -->
                    <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <!-- Texto Dinámico -->
                    <span x-text="loading ? 'Buscando...' : 'Buscar'">Buscar</span>
                </button>
            </div>
        </form>

        <!-- AVISO PREVENTIVO DE LOCALIZACIÓN -->
        @if((request()->missing('city') || empty(request('city'))) && !request()->filled('symptom'))
            <div class="bg-slate-50 border border-teal-200 rounded-2xl text-teal-900 px-5 py-4 shadow-sm mb-8 flex items-center gap-4">
                <div class="text-teal-500 bg-white p-2 rounded-xl shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="font-black text-sm text-teal-900">Filtrar por ubicación <span class="text-xs text-teal-500 font-medium">(Opcional)</span></p>
                    <p class="text-xs text-teal-600 mt-0.5">Elige una ciudad en la barra de arriba para encontrar consultorios físicos más cercanos.</p>
                </div>
            </div>
        @endif

        <!-- CONTENEDOR MAESTRO DE RESULTADOS -->
        <div class="min-h-screen mt-6">
            <div class="max-w-7xl mx-auto">
                
                <h2 class="text-2xl font-black text-slate-800 mb-8 tracking-tight">                    
                    @if($doctors->total() === 1)
                        1 especialista encontrado
                    @elseif($doctors->total() > 1)
                        {{ $doctors->total() }} especialistas encontrados
                    @endif
                </h2>

                <div class="space-y-6">
                    @forelse($doctors as $doctor)
                        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row gap-6 hover:shadow-md transition duration-200">
                            
                            <!-- Foto del Especialista y Badge de Membresía -->
                            <div class="flex-shrink-0 text-center">
                                @if($doctor->user->profile_photo_url)
                                    <img src="{{ $doctor->user->profile_photo_url }}" class="w-24 h-24 rounded-2xl mx-auto mb-3 object-cover border-2 border-indigo-50 shadow-inner">
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-md border border-indigo-100/30 tracking-wider">
                                        {{ $doctor->plan_price > 0 ? 'Premium' : 'Profesional' }}
                                    </span>
                                @else
                                   <div class="w-24 h-24 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-2xl mx-auto mb-3">
                                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Información de Cabecera del Resultado -->
                            <div class="flex-1">                                
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $doctor->gender === 'male' ? 'Dr. ' . ucfirst($doctor->user->name) : 'Dra. ' . ucfirst($doctor->user->name) }}</h3>   
                                
                                @if($doctor->specialties->isNotEmpty())                             
                                    <div class="flex flex-wrap gap-1.5 mt-2 mb-4">
                                        @foreach($doctor->specialties as $spec)
                                            <span class="px-2.5 py-1 bg-blue-50/60 text-blue-600 text-[10px] font-bold rounded-lg uppercase tracking-wide border border-blue-100/40">
                                                {{ $spec->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <!-- REPUTACIÓN EN ESTRELLAS DEL ESPECIALISTA -->
                                <div class="flex items-center space-x-1">
                                    @php $ratingScore = round($doctor->rating ?? 0); @endphp
                                    
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $ratingScore ? 'text-yellow-400' : 'text-slate-200' }}" 
                                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://w3.org">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    @endfor

                                    <span class="ml-2 text-xs font-bold text-slate-500">
                                        ({{ $doctor->reviews_count ?? 0 }} opiniones)
                                    </span>
                                </div>
                                
                                <!-- DETALLE DE CONSULTORIOS Y SEDES VINCULADAS -->
                                @if($doctor->addresses->isNotEmpty())
                                <div class="mt-4 pt-3 border-t border-slate-50">
                                    <div class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Sedes de Atención</div>
                                    @foreach($doctor->addresses as $address)
                                        <div class="flex items-start gap-2 text-xs text-slate-600 mt-1.5">
                                            <svg class="w-3.5 h-3.5 mt-0.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            <span class="font-bold text-slate-700">{{ $address->name }}:</span>
                                            <span class="font-medium text-slate-500">{{ $address->address }} {{ $address->type === 'virtual' ? '' : ',' . ($address->city->name ?? '') }}</span>
                                        </div>
                                        
                                        <!-- Mapeo rápido de los 3 principales servicios prestados en esta sede -->
                                        <div class="flex flex-wrap gap-1 mt-1.5 mb-4 pl-5">
                                            @if($address->services->isNotEmpty())
                                                @foreach($address->services->take(3) as $service)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200/60">
                                                        {{ $service->name }}
                                                    </span>
                                                @endforeach
                                                @if($address->services->count() > 3)
                                                    <span class="text-[10px] text-slate-400 pt-0.5 font-bold">+{{ $address->services->count() - 3 }} más</span>
                                                @endif
                                            @else
                                                <span class="text-[10px] text-slate-400 italic">Consulta general</span>
                                            @endif
                                        </div>                                    
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            
                            <!-- COLUMNA ACCIONES Y RESERVA DIRECTA -->                            
                            <div class="flex flex-col justify-center gap-2.5 min-w-[180px] border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 px-2">
                                @if($doctor->addresses->isNotEmpty())
                                    <div x-data="{ redireccionando: false }" class="flex flex-col gap-1">
                                        <a href="{{ route('partner.public.profile', ['slug' => $doctor->slug]) }}" 
                                            @click="redireccionando = true"
                                            :class="redireccionando ? 'opacity-75 cursor-not-allowed bg-indigo-500 pointer-events-none transform-none' : 'bg-indigo-600 hover:bg-indigo-700 hover:-translate-y-0.5'"
                                            class="text-white font-bold text-xs uppercase tracking-wider text-center py-3 px-4 rounded-xl shadow-md shadow-indigo-100 transition-all transform flex items-center justify-center gap-2 min-h-[40px]">
                                            
                                            <!-- Icono Spinner SVG Animado (Solo visible al hacer clic) -->
                                            <svg x-show="redireccionando" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>

                                            <!-- Texto Dinámico -->
                                            <span x-text="redireccionando ? 'Analizando...' : 'Reservar Cita'">Reservar Cita</span>
                                        </a>
                                        
                                        <p class="text-[9px] text-center text-slate-400 font-bold uppercase tracking-wider mt-1">Citas Médicas en Línea</p>
                                    </div>
                                @else
                                    <span class="px-2 py-2 bg-amber-50 text-amber-700 text-[10px] font-black text-center rounded-xl uppercase tracking-wide border border-amber-100">
                                        Agenda por configurar
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <!-- ESTADO VACÍO EN CASO DE NO COINCIDIR FILTROS -->
                        <div class="text-center py-20 bg-white rounded-[2rem] border-2 border-dashed border-slate-200 p-6 shadow-inner">
                            <div class="mx-auto w-12 h-12 text-slate-300 mb-3 flex items-center justify-center bg-slate-50 border border-slate-100 rounded-full">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h4 class="text-base font-black text-slate-800 tracking-tight">Sin resultados exactos</h4>
                            <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto leading-relaxed">No encontramos especialistas con esos criterios de búsqueda en este momento.</p>
                            <a href="{{ route('search') }}" class="text-indigo-600 font-bold text-xs uppercase tracking-wider mt-3 inline-block hover:text-indigo-800">Limpiar todos los filtros</a>
                        </div>
                    @endforelse
                </div>

                <!-- ENLACES DE PAGINACIÓN ADAPTADOS -->
                <div class="mt-10">
                    {{ $doctors->links() }}
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
