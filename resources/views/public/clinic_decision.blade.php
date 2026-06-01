<x-guest-layout>
    <div class="max-w-5xl mx-auto px-4 py-10 mt-5" x-data="{ currentTab: 'immediacy' }">
        
        <!-- ENCABEZADO INSTITUCIONAL DE LA CLÍNICA -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50 p-6 rounded-[2rem] border border-slate-100 shadow-xs">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-600 text-white rounded-2xl shadow-md">
                    {{-- Heroicon: BuildingOffice2 --}}
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $clinic->name }}</h1>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1 flex items-center gap-1">
                        {{-- Heroicon: MapPin --}}
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        Sede Actual: {{ $address->name }} • {{ $address->address }}
                    </p>
                </div>
            </div>
        </div>

        <!-- CONMUTADOR DE CAMINO COMERCIAL (TABS) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8 bg-slate-100 p-1.5 rounded-2xl">
            <button @click="currentTab = 'immediacy'" :class="currentTab === 'immediacy' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="py-3 px-4 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 flex items-center justify-center gap-2">
                {{-- Heroicon: Bolt --}}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                Atención Inmediata (Turno más rápido)
            </button>
            
            <button @click="currentTab = 'preference'" :class="currentTab === 'preference' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="py-3 px-4 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 flex items-center justify-center gap-2">
                {{-- Heroicon: Users --}}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-2.533-3.076m-10.5-.228a4.125 4.125 0 00-2.533 3.076 9.317 9.317 0 004.121.952 9.317 9.317 0 002.625-.372M12 13.5c2.071 0 3.75-1.679 3.75-3.75S14.071 6 12 6s-3.75 1.679-3.75 3.75 1.679 3.75 3.75 3.75zm6 2.028c1.78.343 3 1.932 3 3.722v.33M6.5 15.528c-1.78.343-3 1.932-3 3.722v.33" /></svg>
                Seleccionar Especialista (Preferencia)
            </button>
        </div>
        <!-- CONTENEDOR CENTRAL DE OPCIONES -->
        <div class="bg-white border border-slate-100 p-6 rounded-[2rem] shadow-sm">
            
            <!-- CAMINO A: ENFOQUE EN INMEDIATEZ -->
            <div x-show="currentTab === 'immediacy'" x-transition:enter="transition ease-out duration-200" class="space-y-4">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-800 tracking-tight">Citas rápidas unificadas disponibles</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Selecciona el bloque horario ideal para ti. El SaaS asignará de forma automatizada tu consulta médica al profesional disponible de turno.</p>
                </div>

                @if(count($unifiedSlots) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($unifiedSlots as $slot)
                            <form action="{{ route('appointments.step-two') }}" method="POST">
                                @csrf
                                <input type="hidden" name="address_id" value="{{ $address->id }}">
                                <input type="hidden" name="doctor_id" value="{{ $slot['doctor_id'] }}">
                                <input type="hidden" name="date" value="{{ $slot['date'] }}">
                                <input type="hidden" name="hour" value="{{ $slot['start_time'] }}">
                                <input type="hidden" name="clinic_id" value="{{ $clinic->id }}">
                                
                                <button type="submit" class="w-full p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200/60 rounded-xl text-center text-slate-700 hover:text-indigo-600 transition group flex flex-col items-center justify-center">
                                    <p class="text-[10px] font-black uppercase text-slate-400 group-hover:text-indigo-400 leading-none">{{ $slot['date_human'] }}</p>
                                    <p class="text-sm font-black mt-1 leading-none">{{ $slot['time_human'] }}</p>
                                </button>
                            </form>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 text-xs font-medium">
                        No hay turnos disponibles para asignación rápida esta semana en este consultorio. Intenta seleccionando un especialista de nuestro personal.
                    </div>
                @endif
            </div>
            <!-- CAMINO B: ENFOQUE EN PREFERENCIA (STAFF CLÍNICO) -->
            <div x-show="currentTab === 'preference'" x-transition:enter="transition ease-out duration-200" class="space-y-4" style="display: none;">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-800 tracking-tight">Especialistas adscritos a esta sede</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Explora el cuadro médico de la sede institucional y reserva directamente con tu profesional de confianza.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($clinic->doctors as $doc)
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between gap-4 shadow-2xs">
                            <div>
                                <h4 class="font-black text-slate-800 text-sm">
                                    {{ $doc->gender === 'female' ? 'Dra. ' : 'Dr. ' }}{{ ucfirst($doc->user->name) }}
                                </h4>
                                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-wide mt-0.5 leading-none">
                                    {{ $doc->specialties->first()->name ?? 'Especialista' }}
                                </p>
                            </div>
                            
                            <!-- Enlace directo al perfil unificado arrastrando las variables de co-propiedad -->
                            <a href="{{ url('/medical-partner/' . $doc->slug . '?address_id=' . $address->id . '&from_clinic=' . $clinic->id) }}" class="px-3 py-2 bg-white hover:bg-indigo-600 border border-slate-200 text-slate-700 hover:text-white font-bold text-[10px] uppercase tracking-wider rounded-xl transition shadow-xs flex-shrink-0">
                                Ver Agenda
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

        </div> <!-- Cierre del div del contenedor central -->
    </div> <!-- Cierre del x-data de Alpine.js -->
</x-guest-layout>
