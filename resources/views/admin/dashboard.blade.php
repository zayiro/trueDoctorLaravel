@php
    $isPatient = $user->role === 'patient';
    $isDoctor = $user->role === 'doctor';
    $isClinic = $user->role === 'clinic';
    $isAdmin = $user->role === 'admin';

    $appointmentRouteSection = route('patient.appointments.index');

    $validationStatus = 'approved';
    if ($isDoctor) {
        $validationStatus = $user->doctor?->validation_status ?? 'missing';
        $appointmentRouteSection = route('partner.appointments.index');
    } elseif ($isClinic) {
        $validationStatus = $user->clinic?->validation_status ?? 'missing';
        $appointmentRouteSection = route('partner.appointments.index');
    }

    $breadcrumbs = [
        ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['name' => $isPatient ? 'Mi Panel de Salud' : 'Consola Analítica de Negocio']
    ];

    $doctorSettings = $user->doctorSettings;
    $hasIndividualPlan = $doctorSettings && $doctorSettings?->plan_id !== null;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    @if ($isAdmin ?? false)
        @include('admin.partials.ai-model-alert')
    @endif    
    @if(session('status') === 'correo-verificado')
        <div class="m-3 mb-5">
            <div class="flex items-center p-4 text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-100 shadow-sm" role="alert">
                <div class="text-sm font-bold">¡Correo confirmado! Tu código promocional de bienvenida ya está disponible.</div>
            </div>
        </div>
    @endif
    @if(isset($promoCode) && auth()->user()?->email_verified_at === null)
        {{-- Código oculto hasta confirmar el correo: no se imprime en el HTML. --}}
        <div class="m-3 mb-5">
            <div class="bg-slate-900 border border-white/10 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold tracking-wider text-slate-400 uppercase">Tu Regalo de Bienvenida</h3>
                    <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">Por redimir</span>
                </div>
                <p class="mt-1 text-2xl font-black text-white tracking-tight">Tienes un código promocional esperándote</p>
                <p class="mt-1 text-md text-slate-400">Confirma tu correo ({{ auth()->user()->email }}) para ver tu código y usarlo en tu próximo análisis.</p>
                @if(session('status') === 'verification-link-sent')
                    <p class="mt-3 text-sm font-semibold text-emerald-400">Te enviamos un enlace de confirmación. Revisa tu bandeja de entrada y la carpeta de spam.</p>
                @endif
                <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                        Enviar enlace de confirmación
                    </button>
                </form>
            </div>
        </div>
    @elseif(isset($promoCode))
    <div class="m-3 mb-5">
        <div class="bg-slate-900 border border-white/10 rounded-2xl p-6 shadow-xl"
            x-data="{ 
                code: '{{ $promoCode?->code ?? 'SIN-CODIGO' }}', 
                copied: false,
                copyToClipboard() {
                    navigator.clipboard.writeText(this.code);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            }">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <!-- Título y Badge de Estado -->
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold tracking-wider text-slate-400 uppercase">Tu Regalo de Bienvenida</h3>
                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                            Activo
                        </span>
                    </div>
                    <!-- Descripción corta del beneficio -->
                    <p class="text-2xl font-black text-white tracking-tight">
                        {{ $promoCode?->type === 'percent' ? intval($promoCode->reward) . '%' : '$' . number_of_format($promoCode->reward) }} de Descuento
                    </p>
                    <p class="text-md text-slate-400">
                        Úsalo en tu próximo análisis de exámenes médicos. No tiene fecha de vencimiento.
                    </p>
                </div>
                
                <!-- Icono Decorativo de Ticket -->
                <div class="p-3 bg-white/5 rounded-xl border border-white/10 text-blue-400 hidden sm:block">
                    <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-3-12h.008v.008H13.5V6Zm0 6h.008v.008H13.5V12Zm0 6h.008v.008H13.5V18ZM6 6h.008v.008H6V6Zm0 6h.008v.008H6V12Zm0 6h.008v.008H6V18Zm16.5-6a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 13.5 3h-3a3.375 3.375 0 0 0-3.375 3.375v1.5A1.125 1.125 0 0 1 6 9H4.5A3.375 3.375 0 0 0 1 12.375v3a3.375 3.375 0 0 0 3.375 3.375H6c.41 0 .796.221 1.004.576l.162.274A3.375 3.375 0 0 0 10.051 21h3.897a3.375 3.375 0 0 0 2.885-1.626l.162-.274c.208-.355.595-.576 1.004-.576h1.5A3.375 3.375 0 0 0 23 15.375v-3Z" />
                    </svg>
                </div>
            </div>

            <!-- Caja del Código e Interacción -->
            <div class="mt-5 flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-xl p-2 pl-4 group hover:border-blue-500/50 transition-all duration-200">
                <span class="font-mono text-lg font-bold tracking-widest text-blue-400 select-all uppercase" x-text="code"></span>
                
                <!-- Botón Copiar -->
                <button @click="copyToClipboard()" 
                        type="button"
                        :class="copied ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 h-9 min-w-[90px] justify-center">
                    
                    <!-- Icono Copiar Estándar -->
                    <svg x-show="!copied" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.346.102.637.311.83.597l.66 1.14a2.25 2.25 0 0 1 .24 1.93l-.403 1.528A2.25 2.25 0 0 1 15.07 12h-6.14a2.25 2.25 0 0 1-2.193-1.638l-.403-1.528a2.25 2.25 0 0 1 .24-1.93l.66-1.14a2.25 2.25 0 0 1 .83-.597M12 13.5v-3m0 3 1.5-1.5m-1.5 1.5L10.5 12" />
                    </svg>
                    
                    <!-- Icono Check de Éxito -->
                    <svg x-show="copied" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="size-3.5" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>

                    <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div>     
        @if($isAdmin || (($isDoctor || $isClinic) && $validationStatus === 'approved'))
            <div class="mb-5">
                @if($user->role !== 'patient' && isset($owner->slug))
                    @if ($user->role === 'doctor')
                    <a href="{{ route('partner.public.profile', ['slug' => $owner->slug]) }}" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 transition-colors">Ver perfil publico</a>
                    @elseif ($user->role === 'clinic')
                    <a href="{{ route('partner.clinic.public.decision', ['slug' => $owner->slug]) }}" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 transition-colors">Ver perfil publico clinico</a>
                    @endif
                @endif
            </div>
            <x-appointment-search-form class="mb-6 p-4 bg-white rounded-lg shadow" />
        @endif       

        <div class="py-10 space-y-8">
            @if($isPatient)
                <!-- CABECERA PRINCIPAL DEL PORTAL DE PACIENTES -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                    <div>
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-1 block">Portal del Paciente</span>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight">{{ ucfirst($user->name) }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Gestiona tus citas médicas y controla tus indicadores generales de bienestar.</p>
                    </div>
                    <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                        ID: <span class="text-emerald-600 font-black">#{{ $owner->identification }}</span>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
    
                    <!-- Encabezado izquierdo -->
                    <div class="space-y-1 shrink-0">
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] block">Perfil de salud</span>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight">Resumen Clínico</h3>
                        <a href="{{ route('patient.clinical-history.index') }}" class="inline-block text-[11px] font-black text-indigo-600 hover:underline uppercase tracking-wider mt-1">
                            Ver historia oficial →
                        </a>
                    </div>

                    <!-- Contadores clínicos a la derecha -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 w-full lg:w-auto flex-1">
                        
                        <!-- Alergias -->
                        <a href="{{ route('patient.allergies.index') }}" class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 hover:bg-rose-50/60 border border-slate-100 hover:border-rose-200 transition">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Alergias</span>
                                <p class="text-xl font-black text-rose-600 leading-none">{{ $healthMetrics['allergies_count'] ?? 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl border border-rose-100/50 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Medicamentos -->
                        <a href="{{ route('patient.medications.index') }}" class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 hover:bg-blue-50/60 border border-slate-100 hover:border-blue-200 transition">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Medicamentos</span>
                                <p class="text-xl font-black text-blue-600 leading-none">{{ $healthMetrics['medications_count'] ?? 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl border border-blue-100/50 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Cirugías -->
                        <a href="{{ route('patient.surgeries.index') }}" class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 hover:bg-emerald-50/60 border border-slate-100 hover:border-emerald-200 transition">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Cirugías</span>
                                <p class="text-xl font-black text-emerald-600 leading-none">{{ $healthMetrics['surgeries_count'] ?? 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100/50 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Antecedentes Familiares -->
                        <a href="{{ route('patient.family-history.index') }}" class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 hover:bg-purple-50/60 border border-slate-100 hover:border-purple-200 transition">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Familiares</span>
                                <p class="text-xl font-black text-purple-600 leading-none">{{ $healthMetrics['family_count'] ?? 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl border border-purple-100/50 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Archivos Médicos -->
                        <a href="{{ route('patient.history.index') }}" class="col-span-2 sm:col-span-1 flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/80 hover:bg-amber-50/60 border border-slate-100 hover:border-amber-200 transition">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Archivos</span>
                                <p class="text-xl font-black text-amber-600 leading-none">{{ $healthMetrics['attachments_count'] ?? 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl border border-amber-100/50 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                            </div>
                        </a>

                    </div>
                </div>

                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Tu BMI</span>
                            <h3 class="text-2xl font-black text-indigo-600 tracking-tight">{{ $healthMetrics['bmi'] ?? 'N/A' }}</h3>
                            <span class="inline-block px-2 py-0.5 text-[9px] font-black rounded uppercase tracking-tighter @if($healthMetrics['bmi_status'] == 'Normal') bg-green-100 text-green-800 @elseif($healthMetrics['bmi_status'] == 'Bajo peso' || $healthMetrics['bmi_status'] == 'Underweight') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">
                                {{ $healthMetrics['bmi_status'] }}
                            </span>
                        </div>
                        <div class="p-3.5 bg-indigo-50 text-indigo-600 rounded-2xl border border-indigo-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Edad calculada</span>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ $healthMetrics['age'] }}</h3>
                        </div>
                        <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Tipo de sangre</span>
                            <h3 class="text-2xl font-black text-red-600 tracking-tight">{{ $healthMetrics['blood_type'] }}</h3>
                        </div>
                        <div class="p-3.5 bg-red-50 text-red-600 rounded-2xl border border-red-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Zona de Residencia</span>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ $owner->residence_zone }}</h3>
                        </div>
                        <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-lg">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">Próximas citas</h4>
                        @forelse($upcomingAppointments as $appointment)
                            @php
                            $appointmentStatusPatient = '';
                            
                            switch ($appointment->status_label) {
                                case 'pending': $appointmentStatusPatient = 'Pendiente';
                                break;
                                case 'confirmed': $appointmentStatusPatient = 'Confirmada';
                                break;
                                case 'cancelled': $appointmentStatusPatient = 'Cancelada';
                                break;
                                case 'completed': $appointmentStatusPatient = 'Completada';
                                break;
                            }
                            @endphp
                            <div class="flex justify-between items-center p-3.5 bg-slate-50 rounded-xl mb-2 border border-slate-100">
                                <div>
                                    <p class="text-[11px] text-slate-400">{{ $appointment->reference }}</p>
                                    <p class="text-xs font-black text-slate-700">Dr. {{ $appointment->doctor->user->name ?? 'Specialist' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }} {{ $appointment->start_time ? \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') : '' }}</p>
                                </div>
                                <span class="px-2 py-1 text-[9px] font-black rounded-lg uppercase bg-indigo-100 text-indigo-800">{{ $appointmentStatusPatient }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-4 text-center">No tienes consultas pendientes.</p>
                        @endforelse
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-lg">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">Consultas asistidas</h4>
                        @forelse($pastAppointments as $appointment)
                            <div class="flex justify-between items-center p-3.5 bg-slate-50 rounded-xl mb-2 border border-slate-100">
                                <div>
                                    <p class="text-md font-bold text-indigo-700">{{ $appointment->reference }}</p>
                                    <p class="text-xs font-bold text-slate-700">Dr. {{ $appointment->doctor->user->name ?? 'Specialist' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</p>
                                </div>
                                <span class="px-2 py-1 text-[9px] font-black rounded-lg uppercase bg-emerald-100 text-emerald-800">COMPLETADA</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-4 text-center">Sin historial de consultas.</p>
                        @endforelse
                    </div>
                </div>
            @else
                
                @if(($isDoctor || $isClinic) && $validationStatus !== 'approved')
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                        <div>
                            <span class="text-base font-black text-amber-600 uppercase tracking-[0.2em] mb-1 block">{{ $validationStatus === 'pending_validation' ? 'Verificación en Curso' : 'Documentación rechazada, intentalo nuevamente!'  }}</span>
                            <h2 class="text-3xl font-black text-slate-800 tracking-tight">¡Hola, {{ $user->name }}!</h2>
                            <p class="text-md text-slate-400 mt-0.5">Para habilitar tu consola analítica y empezar a operar con tu agenda, completa tus soportes legales.</p>
                        </div>
                    </div>
                    <x-verify-docs :type="$user->role" :status="$validationStatus" />
                    
                @else
                    
                    <!-- CABECERA CORPORATIVA PARA ROLES DE NEGOCIO -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                        <div>
                            <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1 block">Control Console</span>
                            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Estadísticas e indicadores</h2>
                        </div>
                        <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                            <span class="text-indigo-600 font-black">{{ $owner?->name ?? $user->name }}</span>
                        </div>
                    </div>

                    @if($user->role === 'admin')
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-900 p-6 rounded-[2rem] text-white">                           
                            <div class="col-span-6 mt-4 border-t border-slate-800 pt-4">
                                <h4 class="text-sm font-black text-slate-400 uppercase tracking-wide mb-2">Visión general del ecosistema</h4>
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Doctores Activos</h5>
                                        <span class="text-2xl font-black text-indigo-400 font-mono">{{ $usersByRole['doctor'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Clínicas Activas</h5>
                                        <span class="text-2xl font-black text-purple-400 font-mono">{{ $usersByRole['clinic'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pacientes</h5>
                                        <span class="text-2xl font-black text-emerald-400 font-mono">{{ $usersByRole['patient'] ?? 0 }}</span>
                                    </div>
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Administradores</h5>
                                        <span class="text-2xl font-black text-amber-400 font-mono">{{ $usersByRole['admin'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                                             
                            <div class="col-span-6 mt-4 border-t border-slate-800 pt-4">
                                <h4 class="text-sm font-black text-slate-400 uppercase tracking-wide mb-2">Tendencias de búsqueda global</h4>
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Especialidades más buscadas</h5>
                                        <ul class="text-sm text-white space-y-1">
                                            @foreach($popularSpecialties as $item)
                                                <li class="flex justify-between items-center">
                                                    <span>{{ $item->specialty_name ?? ucwords(str_replace('-', ' ', $item->specialty)) }}</span>
                                                    <span class="text-xs bg-indigo-600 px-2 py-0.5 rounded-full">({{ $item->total_searches }} {{ $item->total_searches == 1 ? 'búsqueda' : 'búsquedas' }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="flex-1 bg-slate-800 p-4 rounded-lg">
                                        <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Ciudades más buscadas</h5>
                                        <ul class="text-sm text-white space-y-1">
                                            @foreach($popularCities as $item)
                                                <li class="flex justify-between items-center">
                                                    <span>{{ ucwords(str_replace('-', ' ', $item->city)) }}</span>
                                                    <span class="text-xs bg-green-600 px-2 py-0.5 rounded-full">({{ $item->total_searches }} {{ $item->total_searches == 1 ? 'búsqueda' : 'búsquedas' }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($hasIndividualPlan || $user->role !== 'patient')
                    <!-- CUADRÍCULA DE KPIs (kpigrid3) -->
                    @php
                        $kpiTones = ['good' => 'text-emerald-600', 'bad' => 'text-rose-600', 'neutral' => 'text-slate-500'];
                        $kpiValueTones = ['good' => 'text-emerald-600', 'bad' => 'text-rose-600', 'neutral' => 'text-slate-800'];
                    @endphp
                    @if (isset($kpiMoney, $kpiOps))
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider mr-1">Periodo</span>
                        @foreach (($periodOptions ?? []) as $pk => $pl)
                            <a href="{{ request()->fullUrlWithQuery(['periodo' => $pk]) }}"
                               class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors {{ ($periodKey ?? 'mes') === $pk ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200 hover:border-indigo-300' }}">{{ $pl }}</a>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach ($kpiMoney as $k)
                            <div class="min-w-0 h-full bg-white border rounded-[2rem] p-5 shadow-lg border-slate-100" title="{{ $k['hint'] }}">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">{{ $k['label'] }}</span>
                                <h3 class="text-xl 2xl:text-2xl font-black mt-2 whitespace-nowrap {{ $kpiValueTones[$k['tone']] ?? 'text-slate-800' }}">{{ $k['value'] }}</h3>
                                @if (!empty($k['delta']))
                                    <p class="text-[11px] font-bold mt-1 {{ $kpiTones[$k['deltaTone'] ?? 'neutral'] }}">{{ $k['delta'] }} <span class="font-normal text-slate-400">vs. periodo anterior</span></p>
                                @endif
                                @if (!empty($k['sub']))
                                    <p class="text-[11px] text-slate-400 mt-1 leading-snug">{{ $k['sub'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                        @foreach ($kpiOps as $k)
                            @if (!empty($k['link']) && isset($appointmentRouteSection))
                                <a href="{{ $appointmentRouteSection }}" class="block min-w-0 h-full">
                            @endif
                            <div class="min-w-0 h-full bg-white border rounded-[2rem] p-5 shadow-lg border-slate-100 {{ !empty($k['link']) ? 'hover:shadow-xl transition-shadow' : '' }}" title="{{ $k['hint'] }}">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">{{ $k['label'] }}</span>
                                <h3 class="text-xl 2xl:text-2xl font-black mt-2 {{ $kpiValueTones[$k['tone']] ?? 'text-slate-800' }}">{{ $k['value'] }}</h3>
                                @if (!empty($k['delta']))
                                    <p class="text-[11px] font-bold mt-1 {{ $kpiTones[$k['deltaTone'] ?? 'neutral'] }}">{{ $k['delta'] }} <span class="font-normal text-slate-400">vs. anterior</span></p>
                                @endif
                                @if (!empty($k['sub']))
                                    <p class="text-[11px] text-slate-400 mt-1 leading-snug">{{ $k['sub'] }}</p>
                                @endif
                            </div>
                            @if (!empty($k['link']) && isset($appointmentRouteSection))
                                </a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                    <!-- CRÉDITOS DE IA (credits1) -->
                    @if (!empty($aiCredits))
                        @php
                            $creditStyles = [
                                'ok' => ['border-emerald-200', 'text-emerald-600', 'bg-emerald-50', 'Saldo suficiente'],
                                'low' => ['border-amber-300', 'text-amber-600', 'bg-amber-50', 'Saldo bajo: recarga pronto'],
                                'out' => ['border-rose-300', 'text-rose-600', 'bg-rose-50', 'Sin saldo o pago rechazado'],
                                'unknown' => ['border-slate-200', 'text-slate-500', 'bg-slate-50', 'Sin dato todavía'],
                            ];
                        @endphp
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="mb-4">
                                <h3 class="text-sm font-black text-slate-800">Créditos y servicios</h3>
                                <p class="text-[11px] text-slate-400">Para saber a qué cuenta recargar antes de que se caiga un servicio</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4">
                                @foreach ($aiCredits as $c)
                                    @php [$bd, $tx, $bg, $msg] = $creditStyles[$c['status']] ?? $creditStyles['unknown']; @endphp
                                    <div class="min-w-0 rounded-2xl border-2 {{ $bd }} p-4">
                                        {{-- cards-fix: nombre completo, uso y estado apilados --}}
                                        <div>
                                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider block leading-tight break-words">{{ $c['label'] }}</span>
                                            <span class="text-[11px] text-slate-400 block leading-snug mt-0.5">{{ $c['usage'] }}</span>
                                            <span class="inline-block mt-2 text-[10px] font-bold px-2 py-1 rounded-full {{ $bg }} {{ $tx }}">{{ $msg }}</span>
                                        </div>
                                        <p class="text-2xl font-black mt-2 whitespace-nowrap {{ $c['status'] === 'unknown' ? 'text-slate-400' : $tx }}">{{ $c['value'] }}</p>
                                        <p class="text-[11px] text-slate-400 mt-1 leading-snug">{{ $c['detail'] }}@if ($c['stale']) · <span class="text-amber-600 font-bold">dato desactualizado</span>@endif</p>
                                        @if (!is_null($c['days_left']))
                                            <p class="text-[11px] font-bold text-slate-500 mt-1">≈ {{ $c['days_left'] }} {{ $c['days_left'] === 1 ? 'día' : 'días' }} al ritmo actual de gasto</p>
                                        @endif
                                        @if (!empty($c['error']))
                                            <p class="text-[11px] text-slate-400 mt-1 leading-snug" title="{{ $c['error'] }}">ⓘ {{ \Illuminate\Support\Str::limit($c['error'], 90) }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- PRÓXIMOS COBROS (bills1) -->
                    @if (is_array($upcomingBills ?? null))
                        @php
                            $billStyles = [
                                'overdue' => ['border-rose-300', 'text-rose-600', 'bg-rose-50'],
                                'soon' => ['border-amber-300', 'text-amber-600', 'bg-amber-50'],
                                'ok' => ['border-slate-100', 'text-slate-500', 'bg-slate-50'],
                            ];
                        @endphp
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="mb-4">
                                <h3 class="text-sm font-black text-slate-800">Próximos cobros</h3>
                                <p class="text-[11px] text-slate-400">Servicios que se pagan de forma recurrente, con aviso antes de la fecha</p>
                            </div>
                            @if (count($upcomingBills) === 0)
                                <p class="text-sm text-slate-400">Aún no hay cobros registrados. Agrega uno con <span class="font-mono text-xs bg-slate-100 rounded px-1.5 py-0.5">php artisan bills:add "AWS" --amount=50 --cadence=monthly --due=2026-12-05 --auto</span></p>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach ($upcomingBills as $bill)
                                        @php [$bd, $tx, $bg] = $billStyles[$bill['status']] ?? $billStyles['ok']; @endphp
                                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-800 truncate">{{ $bill['name'] }}</p>
                                                <p class="text-[11px] text-slate-400">{{ $bill['cadence'] }} · {{ $bill['amount'] }}@if ($bill['auto']) · se cobra solo @endif</p>
                                                @if (!empty($bill['note']))
                                                    <p class="text-[11px] text-slate-400 truncate" title="{{ $bill['note'] }}">ⓘ {{ \Illuminate\Support\Str::limit($bill['note'], 110) }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $bg }} {{ $tx }}">
                                                    @if ($bill['days'] < 0) Vencido hace {{ abs($bill['days']) }} {{ abs($bill['days']) === 1 ? 'día' : 'días' }}
                                                    @elseif ($bill['days'] === 0) Hoy
                                                    @elseif ($bill['days'] === 1) Mañana
                                                    @else En {{ $bill['days'] }} días @endif
                                                </span>
                                                <p class="text-[11px] text-slate-400 mt-1">{{ $bill['due'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- CÓDIGOS PROMOCIONALES (promos1) -->
                    @if (!empty($promoStats))
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                                <div>
                                    <h3 class="text-sm font-black text-slate-800">Códigos promocionales</h3>
                                    <p class="text-[11px] text-slate-400">Usos y descuentos otorgados · {{ $periodLabel ?? '' }}</p>
                                </div>
                                <div class="text-right" title="{{ $promoStats['hint'] }}">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Descuento otorgado</span>
                                    <span class="text-lg font-black text-slate-800">{{ $promoStats['value'] }}</span>
                                    @if (!empty($promoStats['delta']))
                                        <span class="text-[11px] font-bold text-slate-500 block">{{ $promoStats['delta'] }} <span class="font-normal text-slate-400">vs. anterior</span></span>
                                    @endif
                                </div>
                            </div>

                            @if ($promoStats['uses'] === 0)
                                <p class="text-sm text-slate-400">Ningún código se usó en este periodo.</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                    @foreach ($promoStats['modules'] as $m)
                                        <div class="min-w-0 rounded-2xl border border-slate-100 p-4" title="{{ $m['hint'] }}">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">{{ $m['name'] }}</span>
                                            <p class="text-xl font-black text-slate-800 mt-1 whitespace-nowrap">{{ $m['value'] }}</p>
                                            <div class="h-1.5 bg-slate-100 rounded-full mt-2 overflow-hidden">
                                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, $m['share']) }}%"></div>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1.5">{{ $m['n'] }} {{ $m['n'] === 1 ? 'uso' : 'usos' }}@if ($m['share'] > 0) · {{ $m['share'] }}% del descuento @endif</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead>
                                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                                <th class="py-2 pr-4">Código</th>
                                                <th class="py-2 pr-4">Descuento</th>
                                                <th class="py-2 pr-4">Usos en el periodo</th>
                                                <th class="py-2 pr-4">Valor otorgado</th>
                                                <th class="py-2 pr-4">Módulo</th>
                                                <th class="py-2">Usos totales / tope</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($promoStats['top'] as $t)
                                                <tr>
                                                    <td class="py-2 pr-4 font-black text-slate-800 whitespace-nowrap">{{ $t['code'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-600 whitespace-nowrap">{{ $t['kind'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-600">{{ $t['n'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-600 whitespace-nowrap">{{ $t['value'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-500">{{ $t['modules'] }}</td>
                                                    <td class="py-2 whitespace-nowrap {{ $t['exhausted'] ? 'text-rose-600 font-bold' : 'text-slate-500' }}">{{ $t['stock'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if (!empty($promoStats['orphan']))
                                <p class="text-[11px] text-slate-400 mt-3">Además, {{ $promoStats['orphan']['n'] }} {{ $promoStats['orphan']['n'] === 1 ? 'uso' : 'usos' }} de códigos que ya no existen ({{ $promoStats['orphan']['codes'] }}); no se suman arriba.</p>
                            @endif

                            @foreach ($promoStats['warnings'] as $w)
                                <p class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 mt-3">⚠ {{ $w }}</p>
                            @endforeach
                        </div>
                    @endif

                    <!-- CONVERSIÓN DE RESERVA A PAGO (conv1) -->
                    @if (!empty($funnel))
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                                <div>
                                    <h3 class="text-sm font-black text-slate-800">Conversión de reserva a pago</h3>
                                    <p class="text-[11px] text-slate-400">Reservas creadas · {{ $periodLabel ?? '' }} · no cuenta las gratuitas ni las que siguen pendientes</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Conversión</span>
                                    <span class="text-lg font-black text-slate-800">{{ $funnel['conv'] === null ? '—' : rtrim(rtrim(number_format($funnel['conv'], 1, '.', ''), '0'), '.') . '%' }}</span>
                                    @if (!empty($funnel['delta']))
                                        <span class="text-[11px] font-bold block {{ ['good' => 'text-emerald-600', 'bad' => 'text-rose-600', 'neutral' => 'text-slate-500'][$funnel['deltaTone'] ?? 'neutral'] }}">{{ $funnel['delta'] }} <span class="font-normal text-slate-400">vs. anterior</span></span>
                                    @endif
                                </div>
                            </div>

                            @if ($funnel['needPay'] === 0)
                                <p class="text-sm text-slate-400">No hubo reservas que requirieran pago en este periodo.@if ($funnel['free'] > 0) ({{ $funnel['free'] }} {{ $funnel['free'] === 1 ? 'reserva gratuita' : 'reservas gratuitas' }}.)@endif</p>
                            @else
                                <div class="flex h-3 rounded-full overflow-hidden bg-slate-100 mb-4">
                                    @foreach ($funnel['steps'] as $s)
                                        @if ($s['share'] > 0)
                                            <div title="{{ $s['name'] }}: {{ $s['n'] }}" class="h-full {{ ['paid' => 'bg-emerald-500', 'expired' => 'bg-amber-400', 'cancelled' => 'bg-rose-400', 'pending' => 'bg-slate-300'][$s['key']] }}" style="width: {{ $s['share'] }}%"></div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach ($funnel['steps'] as $s)
                                        <div class="min-w-0 rounded-2xl border border-slate-100 p-4" title="{{ $s['hint'] }}">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">{{ $s['name'] }}</span>
                                            <p class="text-xl font-black text-slate-800 mt-1">{{ $s['n'] }}</p>
                                            <p class="text-[11px] text-slate-400 mt-1">{{ $s['share'] }}% · {{ $s['value'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($funnel['lostRaw'] > 0)
                                    <p class="text-[12px] text-slate-500 mt-3" title="{{ $funnel['lostHint'] }}">Reservas perdidas sin pago: <span class="font-black text-slate-700">{{ $funnel['lost'] }}</span> en valor de lista.</p>
                                @endif
                            @endif

                            <div class="mt-3 space-y-1 text-[11px] text-slate-400">
                                @if ($funnel['free'] > 0)
                                    <p>{{ $funnel['free'] }} de {{ $funnel['total'] }} reservas fueron gratuitas (precio 0 o código del 100%) y no entran en la conversión.</p>
                                @endif
                                @if (!empty($funnel['byType']))
                                    <p>Por modalidad:
                                        @foreach ($funnel['byType'] as $t)
                                            {{ $t['name'] }} {{ $t['conv'] === null ? 'sin resolver' : rtrim(rtrim(number_format($t['conv'], 1, '.', ''), '0'), '.') . '%' }} ({{ $t['n'] }}){{ !$loop->last ? ' ·' : '' }}
                                        @endforeach
                                    </p>
                                @endif
                                @if ($funnel['attempts'] > 0)
                                    <p>Intentos de pago por Wompi: {{ $funnel['attempts'] }} · {{ $funnel['attApproved'] }} aprobados · {{ $funnel['attFail'] }} rechazados o con error · {{ $funnel['attExpired'] }} expirados.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- CANAL Y REPROGRAMACIONES (chan1) -->
                    @if (!empty($chanStats))
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="mb-4">
                                <h3 class="text-sm font-black text-slate-800">Canal de reserva y reprogramaciones</h3>
                                <p class="text-[11px] text-slate-400">Reservas creadas · {{ $periodLabel ?? '' }}</p>
                            </div>

                            @if ($chanStats['total'] === 0)
                                <p class="text-sm text-slate-400">No hubo reservas en este periodo.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead>
                                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                                <th class="py-2 pr-4">Canal</th>
                                                <th class="py-2 pr-4">Reservas</th>
                                                <th class="py-2 pr-4">Gratuitas</th>
                                                <th class="py-2 pr-4">Expiradas sin pago</th>
                                                <th class="py-2">Conversión a pago</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($chanStats['rows'] as $r)
                                                <tr>
                                                    <td class="py-2 pr-4 font-black text-slate-800">{{ $r['name'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-600">{{ $r['n'] }} <span class="text-[11px] text-slate-400">({{ round($r['n'] / $chanStats['total'] * 100) }}%)</span></td>
                                                    <td class="py-2 pr-4 text-slate-600">{{ $r['free'] }}</td>
                                                    <td class="py-2 pr-4 text-slate-600">{{ $r['expired'] }}</td>
                                                    <td class="py-2 text-slate-600 whitespace-nowrap">{{ $r['conv'] === null ? '—' : rtrim(rtrim(number_format($r['conv'], 1, '.', ''), '0'), '.') . '%' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="mt-4 rounded-2xl border border-slate-100 p-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Reprogramaciones</span>
                                <p class="text-xl font-black text-slate-800 mt-1">
                                    {{ $chanStats['resch']['reservas'] }} {{ $chanStats['resch']['reservas'] === 1 ? 'reserva reprogramada' : 'reservas reprogramadas' }}
                                    @if ($chanStats['resch']['rate'] !== null)
                                        <span class="text-sm font-bold text-slate-500">· {{ rtrim(rtrim(number_format($chanStats['resch']['rate'], 1, '.', ''), '0'), '.') }}% de {{ $chanStats['resch']['base'] }}</span>
                                    @endif
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    {{ $chanStats['resch']['total'] }} {{ $chanStats['resch']['total'] === 1 ? 'reprogramación registrada' : 'reprogramaciones registradas' }} en total (sin contar reservas expiradas)
                                    @if (!empty($chanStats['resch']['delta'])) · {{ $chanStats['resch']['delta'] }} vs. anterior @endif
                                    @if (!empty($chanStats['resch']['max'])) · {{ $chanStats['resch']['maxed'] }} {{ $chanStats['resch']['maxed'] === 1 ? 'llegó' : 'llegaron' }} al tope de {{ $chanStats['resch']['max'] }} @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- TICKET PROMEDIO POR MÓDULO (ticket1) -->
                    @if (!empty($ticketStats))
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                                <div>
                                    <h3 class="text-sm font-black text-slate-800">Ticket promedio por módulo</h3>
                                    <p class="text-[11px] text-slate-400">Pagos aprobados · {{ $periodLabel ?? '' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Ticket general</span>
                                    <span class="text-lg font-black text-slate-800">{{ $ticketStats['avgAll'] }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $ticketStats['n'] }} {{ $ticketStats['n'] === 1 ? 'pago' : 'pagos' }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                            <th class="py-2 pr-4">Módulo</th>
                                            <th class="py-2 pr-4">Pagos</th>
                                            <th class="py-2 pr-4">Ticket cobrado</th>
                                            <th class="py-2 pr-4">Sin código promo</th>
                                            <th class="py-2">Valor de lista</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($ticketStats['rows'] as $t)
                                            <tr title="{{ $t['hint'] }}">
                                                <td class="py-2 pr-4 font-black text-slate-800">{{ $t['name'] }}</td>
                                                <td class="py-2 pr-4 text-slate-600">{{ $t['n'] }}</td>
                                                <td class="py-2 pr-4 text-slate-800 font-bold whitespace-nowrap">{{ $t['avgText'] }}
                                                    @if (!empty($t['delta']))
                                                        <span class="text-[11px] font-bold text-slate-500">{{ $t['delta'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-slate-600 whitespace-nowrap">{{ $t['avgNoPromoText'] }} <span class="text-[11px] text-slate-400">@if ($t['nNoPromo'] > 0)({{ $t['nNoPromo'] }})@endif</span></td>
                                                <td class="py-2 text-slate-600 whitespace-nowrap">{{ $t['avgListText'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3">Ticket cobrado: lo que pagó el paciente en promedio. Sin código promo: solo pagos sin descuento. Valor de lista: lo que habría pagado sin código. Los cambios (▲▼) comparan con el periodo anterior.</p>
                        </div>
                    @endif

                    <!-- SUSCRIPCIONES DE PLANES (subs1) -->
                    @if (!empty($subsStats))
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                                <div>
                                    <h3 class="text-sm font-black text-slate-800">Suscripciones de planes</h3>
                                    <p class="text-[11px] text-slate-400">Lo que pagan los médicos por su plan · {{ $periodLabel ?? '' }}</p>
                                </div>
                                <div class="text-right" title="{{ $subsStats['hint'] }}">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Cobrado en el periodo</span>
                                    <span class="text-lg font-black text-slate-800">{{ $subsStats['value'] }}</span>
                                    @if (!empty($subsStats['delta']))
                                        <span class="text-[11px] font-bold text-slate-500 block">{{ $subsStats['delta'] }} <span class="font-normal text-slate-400">vs. anterior</span></span>
                                    @endif
                                </div>
                            </div>

                            @if (($subsStats['review'] ?? 0) > 0)
                                <div class="mb-4 p-3 rounded-2xl border border-amber-100 bg-amber-50 text-xs font-medium text-amber-800">
                                    {{ $subsStats['review'] }} {{ $subsStats['review'] === 1 ? 'pago de plan está en revisión' : 'pagos de plan están en revisión' }}: el monto de la pasarela no coincidió y no se activó ningún plan. Revísalos con el médico y con Wompi.
                                </div>
                            @endif

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">Pagos de plan</span>
                                    <p class="text-xl font-black text-slate-800 mt-1">{{ $subsStats['n'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">{{ $subsStats['new'] }} {{ $subsStats['new'] === 1 ? 'nuevo' : 'nuevos' }} · {{ $subsStats['renewals'] }} {{ $subsStats['renewals'] === 1 ? 'renovación' : 'renovaciones' }}</p>
                                </div>
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">Ticket promedio</span>
                                    <p class="text-xl font-black text-slate-800 mt-1 whitespace-nowrap">{{ $subsStats['ticket'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">por pago de plan</p>
                                </div>
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4" title="{{ $subsStats['mrrHint'] }}">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">Ingreso mensual recurrente</span>
                                    <p class="text-xl font-black text-slate-800 mt-1 whitespace-nowrap">{{ $subsStats['mrr'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">de los planes vigentes hoy</p>
                                </div>
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">Planes pagos activos hoy</span>
                                    <p class="text-xl font-black text-slate-800 mt-1">{{ $subsStats['active'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        @forelse ($subsStats['byPlan'] as $planName => $cnt){{ $planName }}: {{ $cnt }}{{ !$loop->last ? ' · ' : '' }}@empty sin planes pagos vigentes @endforelse
                                    </p>
                                </div>
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4" title="Médicos cuyo plan venció hace menos de {{ $subsStats['graceDays'] }} días y todavía conservan sus beneficios. Si no renuevan, pasan al plan gratuito.">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">En días de gracia</span>
                                    <p class="text-xl font-black text-slate-800 mt-1">{{ $subsStats['grace'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">{{ $subsStats['lapsed'] }} {{ $subsStats['lapsed'] === 1 ? 'bajó' : 'bajaron' }} al gratuito en el periodo</p>
                                </div>
                                <div class="min-w-0 rounded-2xl border border-slate-100 p-4" title="Médicos con un plan de pago asignado por OpenDoctor, sin suscripción cobrada.">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block truncate">Planes sin cobro</span>
                                    <p class="text-xl font-black text-slate-800 mt-1">{{ $subsStats['manual'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">asignados por OpenDoctor</p>
                                </div>
                            </div>

                            @if (!empty($subsStats['expiring']))
                                <div class="mt-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-2">Vencen en los próximos 7 días</span>
                                    <ul class="text-sm text-slate-600 space-y-1">
                                        @foreach ($subsStats['expiring'] as $e)
                                            <li>{{ $e['who'] }} · {{ $e['plan'] }} · <span class="font-bold text-slate-700">{{ $e['date'] }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- CUADRÍCULA DE GRÁFICOS -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- 📊 SECCIÓN: HISTORIAL DE FACTURACIÓN CONSOLIDADA -->
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-indigo-500 p-2 rounded-xl shadow-lg shadow-indigo-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Rendimiento Financiero</h4>
                                    <p class="text-xs text-slate-400">Valor de las consultas a precio de lista frente al dinero realmente cobrado o devengado, últimos 5 meses.</p>
                                </div>
                            </div>

                            <!-- Contenedor responsivo para el Canvas del gráfico -->
                            <div class="relative w-full h-72">
                                <canvas id="financialHistoryChart"></canvas>
                            </div>
                        </div>

                        <!-- 📈 SECCIÓN: HISTÓRICO DE CONSULTAS SEPARADAS POR VIRTUAL/PRESENCIAL (NUEVO) -->
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-purple-500 p-2 rounded-xl shadow-lg shadow-purple-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6M5 12a7 7 0 1114 0 7 7 0 01-14 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Histórico de Consultas</h4>
                                    <p class="text-xs text-slate-400">Evolución de consultas virtuales y presenciales (últimos 5 meses).</p>
                                </div>
                            </div>

                            <!-- Contenedor responsivo para el Canvas del gráfico de línea -->
                            <div class="relative w-full h-72">
                                <canvas id="historicalChart"></canvas>
                            </div>
                        </div>

                        <!-- 🏢 SECCIÓN: DISTRIBUCIÓN FINANCIERA POR SEDES -->
                        {{-- dashhide: la tarjeta solo se dibuja para doctor/clínica aprobados --}}
                        @if(($isDoctor || $isClinic) && $validationStatus === 'approved')
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-emerald-500 p-2 rounded-xl shadow-lg shadow-emerald-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v12m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Ingresos por Consultorio</h4>
                                    <p class="text-xs text-slate-400">Distribución de rentabilidad según el volumen de atenciones físicas o virtuales.</p>
                                </div>
                            </div>

                            <!-- Contenedor responsivo para el Canvas de la Dona -->
                            <div class="relative w-full h-64 flex items-center justify-center">
                                <canvas id="locationDistributionChart"></canvas>
                            </div>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Consultas de Hoy</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-2">{{ $appointmentsToday }}</h3>
                    </div>
                    @endif
                @endif

            @endif

        </div>
    </div>

@if(!$isPatient)    
    <script src="{{ asset('js/chart/chart.umd.js') }}"></script>  
    <script>        
        document.addEventListener("DOMContentLoaded", function() {
            // ========== GRÁFICO DE BARRAS: RENDIMIENTO FINANCIERO ==========
            const ctx = document.getElementById('financialHistoryChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Valor de consultas (precio de lista)',
                        data: {!! json_encode($chartData) !!},
                        backgroundColor: 'rgba(79, 70, 229, 0.85)',
                        borderColor: 'rgb(79, 70, 229)',
                        borderWidth: 1,
                        borderRadius: 8,
                    }, {
                        label: {!! json_encode($chartMoney2Label ?? 'Cobrado') !!},
                        data: {!! json_encode($chartMoney2 ?? []) !!},
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { padding: 12, font: { size: 11, weight: 'bold' } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': $' + new Intl.NumberFormat('es-CO').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) { return '$' + new Intl.NumberFormat('es-CO').format(value); },
                                font: { size: 10, weight: 'bold' }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: 'bold' } }
                        }
                    }
                }
            });

            // ========== GRÁFICO DE LÍNEA: HISTÓRICO DE CONSULTAS ==========
            const historicalCtx = document.getElementById('historicalChart').getContext('2d');
            new Chart(historicalCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [
                        {
                            label: 'Virtuales',
                            data: {!! json_encode($chartDataVirtual) !!},
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#3b82f6',
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Presenciales',
                            data: {!! json_encode($chartDataPresencial) !!},
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#10b981',
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'top',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], color: '#f1f5f9' },
                            ticks: {
                                precision: 0,
                                font: { size: 10, weight: 'bold' }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: 'bold' } }
                        }
                    }
                }
            });

            @if(($isDoctor || $isClinic) && $validationStatus === 'approved')
            // ========== GRÁFICO DE DONA: DISTRIBUCIÓN POR SEDES ==========
            const locationCtx = document.getElementById('locationDistributionChart').getContext('2d');
            
            new Chart(locationCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($locationLabels) !!},
                    datasets: [{
                        data: {!! json_encode($locationRevenueData) !!},
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.85)',  
                            'rgba(16, 185, 129, 0.85)', 
                            'rgba(245, 158, 11, 0.85)'  
                        ],
                        borderColor: [
                            'rgb(79, 70, 229)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)'
                        ],
                        borderWidth: 2,
                        cutout: '70%',
                        radius: '90%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { size: 11, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': $' + new Intl.NumberFormat('es-CO').format(context.raw);
                                }
                            }
                        }
                    }
                }
            });
            @endif
        });
    </script>
@endif
</x-admin-layout>