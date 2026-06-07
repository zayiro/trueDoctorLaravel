@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Nómina de Especialistas']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Contenedor principal con Alpine.js centralizado para controlar pestañas, carga global y lista dinámica --}}
    <div class="max-w-6xl mx-auto py-8 px-4" 
         x-data="{ 
            activeTab: '{{ old('action_type', 'invite') }}', 
            loading: false,
            doctorsList: [ { id: Date.now(), selectedSpecialties: [] } ],
            addDoctor() { this.doctorsList.push({ id: Date.now(), selectedSpecialties: [] }); },
            removeDoctor(index) { if (this.doctorsList.length > 1) { this.doctorsList.splice(index, 1); } }
         }">
        <!-- ALERTAS TRADICIONALES DEL SISTEMA -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
                <x-heroicon-s-check-circle class="flex-shrink-0 w-5 h-5 text-green-500" />
                <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                <button type="button" class="ms-auto bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="alert-error" class="flex items-center p-4 mb-6 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <x-heroicon-s-x-circle class="flex-shrink-0 w-5 h-5 text-red-500" />
                <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                <button type="button" class="ms-auto bg-red-50 text-red-500 rounded-lg p-1.5 hover:bg-red-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-error').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        <!-- REPORTE DE DOCTORES YA REGISTRADOS PREVIAMENTE (OMITIDOS) -->
        @if (session('skipped_doctors'))
            <div class="mb-6 p-5 rounded-2xl bg-amber-50 border border-amber-200 shadow-sm animate-fade-in" id="alert-skipped">
                <div class="flex items-center gap-2 text-amber-800 font-black text-sm mb-2">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500" />
                    <span>Aviso: Algunos especialistas ya existían en el sistema</span>
                </div>
                <p class="text-xs text-amber-700 mb-3 font-medium">Los siguientes registros fueron omitidos de forma segura para no duplicar la base de datos global de OpenDoctor:</p>
                
                <ul class="space-y-1.5 border-t border-amber-200/60 pt-2.5 mb-3">
                    @foreach(session('skipped_doctors') as $skipped)
                        <li class="flex items-center justify-between text-xs text-amber-900 bg-white/50 px-3 py-1.5 rounded-xl border border-amber-100">
                            <span class="font-bold">{{ $skipped['name'] }} <span class="font-medium text-amber-600">({{ $skipped['email'] }})</span></span>
                            <span class="text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md">
                                {{ $skipped['reason'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                <div class="flex justify-end">
                    <button type="button" @click="document.getElementById('alert-skipped').remove()" class="text-xs font-black text-amber-700 hover:text-amber-900 uppercase tracking-wider">
                        Entendido
                    </button>
                </div>
            </div>
        @endif
        <!-- SECCIÓN DE AGREGAR MÉDICO CON DISEÑO DE PESTAÑAS (TABS) -->
        <div class="bg-white border rounded-[2.5rem] p-6 md:p-8 shadow-sm border-slate-100 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-slate-50 pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-100">
                        <x-heroicon-o-user-plus class="w-5 h-5" />
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

            <div>
                <!-- FORMULARIO 1: INVITACIÓN POR CÉDULA -->
                <div x-show="activeTab === 'invite'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-98" x-transition:enter-end="opacity-100 transform scale-100">
                    <p class="text-xs text-slate-400 mb-3 font-medium">Busca e incorpora un especialista que ya posea una cuenta independiente activa en el SaaS.</p>
                    <form action="{{ route('partner.clinic.doctors.store') }}" method="POST" @submit="loading = true" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="hidden" name="action_type" value="invite">
                        
                        <div class="flex-1 relative">
                            <input type="text" name="identification" value="{{ old('action_type') === 'invite' ? old('identification') : '' }}" required
                                placeholder="Ingresa la identificación del médico" 
                                class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('identification') border-red-400 @enderror">
                            @error('identification')
                                <p class="text-xs text-red-600 mt-1 font-bold px-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" :disabled="loading" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 transition-all whitespace-nowrap flex items-center justify-center gap-2">
                            <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Procesando...' : 'Enviar Solicitud'"></span>
                        </button>
                    </form>
                </div>
                <!-- FORMULARIO 2: REGISTRO DIRECTO DESDE CERO (LISTA DINÁMICA MULTI-REGISTRO) -->
                <div x-show="activeTab === 'register_direct'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-98" x-transition:enter-end="opacity-100 transform scale-100">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <p class="text-xs text-slate-400 font-medium">Crea cuentas exclusivas para los médicos de tu staff interno. Se les enviará un email individual para activar sus accesos.</p>
                        </div>
                        <button type="button" @click="addDoctor()" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-600 text-white font-black px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-sm transition-all whitespace-nowrap self-end sm:self-auto">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Agregar otro doctor
                        </button>
                    </div>
                    
                    <form action="{{ route('partner.clinic.doctors.store') }}" method="POST" @submit="loading = true" class="space-y-6">
                        @csrf
                        <input type="hidden" name="action_type" value="register_direct">

                        <!-- Bucle de Alpine.js que genera cada bloque de doctor -->
                        <template x-for="(doc, index) in doctorsList" :key="doc.id">
                            <div class="bg-slate-50/50 border border-slate-100 rounded-3xl p-5 md:p-6 space-y-4 relative">
                                
                                <!-- Encabezado de fila con contador y opción de remover -->
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span class="text-xs font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                                        <span class="w-5 h-5 flex items-center justify-center bg-slate-200 text-slate-700 rounded-md text-[10px]" x-text="index + 1"></span>
                                        Datos del Especialista
                                    </span>
                                    <button type="button" x-show="doctorsList.length > 1" @click="removeDoctor(index)" class="text-xs font-bold text-rose-500 hover:text-rose-700 flex items-center gap-1 transition-colors">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                        Remover
                                    </button>
                                </div>

                                <!-- Inputs Fila 1: Nombre, Email, Identificación con Labels Condicionales -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Nombre completo del médico" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <input type="text" :name="`doctors[${index}][name]`" required placeholder="Ej: Zayr Andrés Ocampo Muñoz" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                    </div>

                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Correo electrónico institucional" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <input type="email" :name="`doctors[${index}][email]`" required placeholder="doctor@example.com" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                    </div>

                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Número de cédula / ID" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <input type="text" :name="`doctors[${index}][identification]`" required placeholder="Cédula de ciudadanía" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                    </div>
                                </div>

                                <!-- Inputs Fila 2: Tarjeta Profesional, Género, Celular Blindado con Labels Condicionales -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Tarjeta profesional / ReTHUS" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <input type="text" :name="`doctors[${index}][medical_license]`" required placeholder="Registro médico oficial" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                    </div>

                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Género" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <select :name="`doctors[${index}][gender]`" required class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-inner">
                                            <option value="" disabled selected>Selecciona el género</option>
                                            <option value="male">Masculino</option>
                                            <option value="female">Femenino</option>
                                            <option value="other">Otro</option>
                                        </select>
                                    </div>

                                    <div class="flex flex-col">
                                        <template x-if="index === 0">
                                            <x-label value="Número celular de notificación" class="mb-1 text-slate-500 font-bold text-xs" />
                                        </template>
                                        <div class="flex rounded-2xl border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-emerald-500 overflow-hidden shadow-inner">
                                            <select :name="`doctors[${index}][country_code]`" required class="bg-slate-50 text-slate-600 text-xs border-0 border-r border-slate-200 focus:ring-0 px-5 cursor-pointer">
                                                <option value="+57" selected>🇨🇴 +57</option>
                                                <option value="+54">🇦🇷 +54</option>
                                                <option value="+591">🇧🇴 +591</option>
                                                <option value="+55">🇧🇷 +55</option>
                                                <option value="+56">🇨🇱 +56</option>
                                                <option value="+593">🇪🇨 +593</option>
                                            </select>
                                            <input type="tel" :name="`doctors[${index}][phone]`" required maxlength="10" pattern="[0-9]{10}" placeholder="3001234567" class="w-full border-0 focus:ring-0 p-4 text-sm text-slate-800 rounded-r-2xl">
                                        </div>
                                    </div>
                                </div>

                                <!-- SELECTOR DE ESPECIALIDADES CON ALPINE -->
                                <div class="mt-2 relative" x-data="specialtiesSelect()" @click.away="open = false">
                                    <select name="specialties[]" multiple class="hidden">
                                        <template x-for="id in selected" :key="id">
                                            <option :value="id" selected></option>
                                        </template>
                                    </select>
                                    
                                    <x-label for="med-search" value="{{ __('Especialidades (Puedes elegir una o varias)') }}" />
                                    <div class="w-full min-h-[50px] flex flex-wrap gap-2 items-center rounded-xl border border-slate-200 p-2 bg-white focus-within:ring-2 focus-within:ring-blue-500 cursor-text"
                                        @click="document.getElementById('med-search').focus(); open = true">
                                        
                                        <template x-for="item in selectedLabels()" :key="item.id">
                                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full font-medium border border-blue-100">
                                                <span x-text="item.name"></span>
                                                <button type="button" @click.stop="toggle(item.id)" class="text-blue-500 hover:text-blue-800 font-bold ml-1">&times;</button>
                                            </span>
                                        </template>

                                        <input x-ref.searchInput
                                            id="med-search"
                                            type="text" 
                                            x-model="search" 
                                            @focus="open = true"
                                            placeholder="Buscar especialidades..." 
                                            class="flex-1 min-w-[150px] outline-none border-none p-1 text-sm text-slate-700 focus:ring-0">
                                    </div>

                                    <div x-show="open && filteredOptions().length > 0" 
                                        x-transition
                                        class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                        style="display: none;">
                                        
                                        <template x-for="option in filteredOptions()" :key="option.id">
                                            <div @click="toggle(option.id); search = ''; document.getElementById('med-search').focus();"
                                                class="px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors"
                                                x-text="option.name">
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <!-- Botón de Envío Masivo -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" :disabled="loading" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-8 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-100 transition-all flex items-center justify-center gap-2 w-full sm:w-auto">
                                <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loading ? 'Procesando nómina...' : 'Registrar y Activar Especialistas'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- Cierre de la sección de pestañas -->
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
                    @php $status = $doctor->pivot->status; @endphp
                    <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 flex flex-col justify-between gap-4 transition-all hover:shadow-md">
                        <div class="flex items-start gap-4">
                            <img class="h-12 w-12 rounded-2xl object-cover border border-slate-100 shadow-sm flex-shrink-0" 
                                 src="{{ $doctor->user->profile_photo_url }}" alt="{{ $doctor->user->name }}">
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-extrabold text-slate-800 truncate leading-snug">{{ ucfirst($doctor->user->name) }}</h4>
                                <span class="text-[11px] text-slate-400 font-medium block mt-0.5">CC: {{ $doctor->identification }}</span>
                                @if($doctor->medical_license)
                                    <span class="text-[11px] text-indigo-600 font-semibold block mt-0.5">Reg. Médico: {{ $doctor->medical_license }}</span>
                                @endif
                                
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @forelse($doctor->specialties as $spec)
                                        <span class="text-[11px] font-bold bg-slate-50 text-slate-500 px-2 py-0.5 rounded-md border border-slate-100/70">{{ $spec->name }}</span>
                                    @empty
                                        <span class="text-[11px] font-bold bg-amber-50 text-amber-600 px-2 py-0.5 rounded-md border border-amber-100/50">Sin especialidades asignadas</span>
                                    @endforelse
                               </div>
                            </div>
                        </div>

                        <!-- Barra Inferior: Estado y Controles Operativos -->
                        <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-1">
                            <div>
                                @if($status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>Activo
                                    </span>
                                @elseif($status === 'pending')
                                    <div class="flex flex-col gap-2">
                                        <span class="inline-flex items-center w-max px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-100 animate-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>Invitado Pendiente
                                        </span>
                                        <form action="{{ route('partner.clinic.doctors.resend-invitation', $doctor) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                                <x-heroicon-s-paper-airplane class="w-3.5 h-3.5 mr-1.5 text-white" />Reenviar Acceso
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-red-50 text-red-700 border border-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>Inactivo
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5">
                                @if($status !== 'pending')
                                    <form action="{{ route('partner.clinic.doctors.toggle', $doctor) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex items-center justify-center px-3 py-2 border rounded-xl text-xs font-bold transition-all {{ $status === 'approved' ? 'border-amber-100 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-100 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                            {{ $status === 'approved' ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('partner.clinic.doctors.destroy', $doctor) }}" method="POST" class="inline" onsubmit="return confirm('{{ $status === 'pending' ? '¿Deseas retirar la invitación?' : '¿Estás seguro de remover a este médico?' }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center p-2 rounded-xl text-xs font-bold border border-rose-100 text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors">
                                        <x-heroicon-o-trash class="w-4 h-4" />
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

    </div> <!-- Cierre del x-data de Alpine.js principal -->
    <script>
document.addEventListener('alpine:init', () => {
    Alpine.data('specialtiesSelect', () => ({
        open: false,
        search: '',
        selected: {{ json_encode(old('specialties', [])) }},
        options: @json($specialties->map(fn($s) => ['id' => $s->id, 'name' => $s->name])),
        
        toggle(id) {
            id = parseInt(id);
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(item => item !== id);
            } else {
                this.selected.push(id);
            }
        },
        filteredOptions() {
            return this.options.filter(option => 
                option.name.toLowerCase().includes(this.search.toLowerCase()) && 
                !this.selected.includes(option.id)
            );
        },
        selectedLabels() {
            return this.options.filter(option => this.selected.includes(option.id));
        }
    }));
});
</script>
</x-admin-layout>
