@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Nómina de Especialistas']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Contenedor principal con Alpine.js para controlar el intercambio de pestañas --}}
    <div class="max-w-6xl mx-auto py-8 px-4" x-data="{ activeTab: '{{ old('action_type', 'invite') }}' }">
        
        <!-- ALERTAS DEL SISTEMA -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                <button type="button" class="ms-auto bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="alert-error" class="flex items-center p-4 mb-6 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="text-sm font-bold">{{ session('error') }}</div>
                <button type="button" class="ms-auto bg-red-50 text-red-500 rounded-lg p-1.5 hover:bg-red-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-error').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        <!-- SECCIÓN DE AGREGAR MÉDICO CON DISEÑO DE PESTAÑAS (TABS) -->
        <div class="bg-white border rounded-[2.5rem] p-6 md:p-8 shadow-sm border-slate-100 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-slate-50 pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight">Vincular Médico Especialista</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Suma profesionales a tu equipo médico bajo la modalidad que requieras.</p>
                    </div>
                </div>

                {{-- SELECTOR DE PESTAÑAS --}}
                <div class="flex p-1 bg-slate-100 rounded-2xl sm:w-auto w-full">
                    <button type="button" 
                            @click="activeTab = 'invite'"
                            :class="activeTab === 'invite' ? 'bg-white text-slate-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
                            class="flex-1 sm:flex-none px-4 py-2.5 text-xs rounded-xl transition-all duration-200">
                        Por Identificación
                    </button>
                    <button type="button" 
                            @click="activeTab = 'register_direct'"
                            :class="activeTab === 'register_direct' ? 'bg-white text-slate-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
                            class="flex-1 sm:flex-none px-4 py-2.5 text-xs rounded-xl transition-all duration-200">
                        Registrar Nuevo Staff
                    </button>
                </div>
            </div>

            {{-- CONTENEDORES DE FORMULARIOS --}}
            <div>
                <!-- FORMULARIO 1: INVITACIÓN POR CÉDULA -->
                <div x-show="activeTab === 'invite'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-98" x-transition:enter-end="opacity-100 transform scale-100">
                    <p class="text-xs text-slate-400 mb-3 font-medium">Busca e incorpora un especialista que ya posea una cuenta independiente activa en el SaaS.</p>
                    <form action="{{ route('partner.clinic_doctors.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="hidden" name="action_type" value="invite">
                        
                        <div class="flex-1 relative">
                            <input type="text" name="identification" value="{{ old('action_type') === 'invite' ? old('identification') : '' }}" required
                                placeholder="Ingresa la cédula de ciudadanía o identificación del médico" 
                                class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('identification') border-red-400 @enderror">
                            @error('identification')
                                <p class="text-xs text-red-600 mt-1 font-bold px-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 transition-all whitespace-nowrap">
                            Enviar Solicitud
                        </button>
                    </form>
                </div>

                <!-- FORMULARIO 2: REGISTRO DIRECTO DESDE CERO -->
                <div x-show="activeTab === 'register_direct'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-98" x-transition:enter-end="opacity-100 transform scale-100">
                    <p class="text-xs text-slate-400 mb-4 font-medium">Crea una cuenta exclusiva para un médico de tu staff interno. Se le enviará un email para activar sus accesos.</p>
                    
                    <form action="{{ route('partner.clinic_doctors.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        <input type="hidden" name="action_type" value="register_direct">

                        <div class="flex flex-col">
                            <input type="text" name="name" value="{{ old('action_type') === 'register_direct' ? old('name') : '' }}" required
                                placeholder="Nombre completo del médico" 
                                class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner @error('name') border-red-400 @enderror">
                            @error('name') <p class="text-[10px] text-red-600 mt-1 font-bold px-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col">
                            <input type="email" name="email" value="{{ old('action_type') === 'register_direct' ? old('email') : '' }}" required
                                placeholder="Correo electrónico institucional" 
                                class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner @error('email') border-red-400 @enderror">
                            @error('email') <p class="text-[10px] text-red-600 mt-1 font-bold px-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <input type="text" name="identification" value="{{ old('action_type') === 'register_direct' ? old('identification') : '' }}" required
                                    placeholder="Número de cédula/ID" 
                                    class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner @error('identification') border-red-400 @enderror">
                                @error('identification') <p class="text-[10px] text-red-600 mt-1 font-bold px-2">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-6 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-100 transition-all whitespace-nowrap">
                                Dar de Alta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- LISTADO DE LA NÓMINA CORPORATIVA -->
        <div class="space-y-4">
            <div class="flex items-center justify-between mb-4 px-2">
                <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Cuerpo Médico Actual</h4>
                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                    {{ $doctors->count() }} Profesional(es)
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($doctors as $doctor)
                    @php
                        $status = $doctor->pivot->status;
                    @endphp
                    <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 flex flex-col justify-between gap-4 transition-all hover:shadow-md">
                        <div class="flex items-start gap-4">
                            <!-- Foto de Perfil / Fallback Avatar Jetstream -->
                            <img class="h-12 w-12 rounded-2xl object-cover border border-slate-100 shadow-sm flex-shrink-0" 
                                 src="{{ $doctor->user->profile_photo_url }}" 
                                 alt="{{ $doctor->user->name }}">
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-extrabold text-slate-800 truncate leading-snug">
                                    {{ $doctor->user->name }}
                                </h4>
                                <span class="text-[11px] text-slate-400 font-medium block mt-0.5">
                                    CC: {{ $doctor->identification }}
                                </span>
                                @if($doctor->medical_license)
                                    <span class="text-[11px] text-indigo-600 font-semibold block mt-0.5">
                                        Reg. Médico: {{ $doctor->medical_license }}
                                    </span>
                                @endif
                                
                                <!-- Especialidades del Médico -->
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @forelse($doctor->specialties as $spec)
                                        <span class="text-[9px] font-bold bg-slate-50 text-slate-500 px-2 py-0.5 rounded-md border border-slate-100/70">
                                            {{ $spec->name }}
                                        </span>
                                    @empty
                                        <span class="text-[9px] font-bold bg-amber-50 text-amber-600 px-2 py-0.5 rounded-md border border-amber-100/50">
                                            Sin especialidades asignadas
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Barra Inferior: Estado y Controles Operativos -->
                        <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-1">
                            <!-- Badge de Estado Dinámico -->
                            <div>
                                @if($status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Activo
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-100 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Invitado Pendiente
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-50 text-red-700 border border-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </div>

                            <!-- Botones Formulario de Acción -->
                            <div class="flex items-center gap-1.5">
                                {{-- El interruptor de activar/desactivar solo se muestra para doctores ya aprobados --}}
                                @if($status !== 'pending')
                                    <form action="{{ route('partner.clinic_doctors.toggle', $doctor) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center px-3 py-2 border rounded-xl text-xs font-bold transition-all outline-none
                                                {{ $status === 'approved' 
                                                    ? 'border-amber-100 text-amber-700 bg-amber-50 hover:bg-amber-100' 
                                                    : 'border-emerald-100 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                            {{ $status === 'approved' ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif

                                <!-- Formulario Eliminar o Cancelar Invitación -->
                                <form action="{{ route('partner.clinic_doctors.destroy', $doctor) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('{{ $status === 'pending' ? '¿Deseas retirar la invitación enviada a este médico?' : '¿Estás seguro de remover a este médico de tu nómina corporativa?' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center p-2 rounded-xl text-xs font-bold border border-rose-100 text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors outline-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-16v1a3 3 0 003 3h10M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-1 md:col-span-2 bg-white border border-dashed border-slate-200 rounded-[2rem] p-12 text-center text-slate-400">
                        <p class="text-sm font-medium">No cuentas con profesionales inscritos en tu nómina médica actualmente.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-admin-layout>
