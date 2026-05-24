@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Nómina de Especialistas']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto py-8 px-4">
        
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
            <div class="flex items-center p-4 mb-6 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="text-sm font-bold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- SECCIÓN DE RECLUTAMIENTO POR CÉDULA -->
        <div class="bg-white border rounded-[2.5rem] p-6 md:p-8 shadow-sm border-slate-100 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Vincular Nuevo Médico Especialista</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Busca e incorpora profesionales de la salud introduciendo su documento de identidad.</p>
                </div>
            </div>

            <form action="{{ route('partner.clinic_doctors.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <div class="flex-1 relative">
                    <input type="text" name="identification" value="{{ old('identification') }}" required
                        placeholder="Ingresa la cédula de ciudadanía o identificación del médico" 
                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 transition-all whitespace-nowrap">
                    Agregar a la Nómina
                </button>
            </form>
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
                        $isActive = $doctor->pivot->status === 'approved';
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
                                    @foreach($doctor->specialties as $spec)
                                        <span class="text-[9px] font-bold bg-slate-50 text-slate-500 px-2 py-0.5 rounded-md border border-slate-100/70">
                                            {{ $spec->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Barra Inferior: Estado y Controles Operativos -->
                        <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-1">
                            <!-- Badge de Estado en la Nómina -->
                            <div>
                                @if($isActive)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Activo
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
                                <!-- Interruptor Activar/Desactivar Turnos en la Clínica -->
                                <form action="{{ route('partner.clinic_doctors.toggle', $doctor) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center p-2 rounded-xl text-xs font-bold border transition-colors
                                            {{ $isActive 
                                                ? 'border-amber-200 text-amber-700 bg-amber-50/50 hover:bg-amber-100/70' 
                                                : 'border-emerald-200 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100/70' 
                                            }}"
                                            title="{{ $isActive ? 'Suspender de la nómina' : 'Reactivar en la nómina' }}">
                                        @if($isActive)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                </form>

                                <!-- Remover Profesional por Completo de la Institución -->
                                <form action="{{ route('partner.clinic_doctors.destroy', $doctor) }}" method="POST" onsubmit="return confirm('¿Estás seguro de dar de baja a este especialista? Se removerá permanentemente de tu nómina institucional.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center p-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl border border-red-200/40 transition-colors" title="Desvincular definitivamente">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- Fallback: Estado vacío de la nómina corporativa -->
                    <div class="col-span-full text-center py-16 bg-white rounded-[2rem] border-2 border-dashed border-slate-200 p-6">
                        <div class="mx-auto w-12 h-12 text-slate-300 mb-3">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-12 h-12 mx-auto text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h4 class="text-base font-bold text-slate-800">Tu cuerpo médico está vacío</h4>
                        <p class="text-gray-500 text-sm mt-1 max-w-md mx-auto">Digita la cédula de los médicos que atienden en tu centro médico para agregarlos a tu equipo y empezar a organizar sus consultorios y turnos.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
