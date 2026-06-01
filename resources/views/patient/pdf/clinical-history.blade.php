<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Header del Paciente -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">            
            <div class="text-left flex-1 space-y-1">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    {{ now()->format('Y-m-d') }}
                </div>
                <h1 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                    {{ $patient->user->name ?? __('Paciente sin nombre') }}
                </h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" /></svg>
                        {{ $patient->identification }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        {{ $patient->user->email }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.58b.51-.77.07-1.54.03-2.31A14.95 14.95 0 0 0 17.25 21c.77.02 1.53-.42 2.31-.03 1.17-.6 1.69-1.93 1.23-3.19l-.83-2.29a2.25 2.25 0 0 0-2.6-1.37l-3 1.2c-.13.05-.27.08-.41.08a8.9 8.9 0 0 1-4.48-4.48c0-.14-.03-.28-.08-.41l1.2-3a2.25 2.25 0 0 0-1.38-2.6l-2.3-.83ZM17.25 21a.75.75 0 0 1-.75-.75V5.25a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75v15a.75.75 0 0 1-.75.75h-3Z" /></svg>
                        {{ $patient->phone ?? __('Sin teléfono') }}
                    </span>
                </div>
            </div>
            
            <div class="w-full md:w-auto">
                <a href="{{ route('patients.download-history', $patient->id) }}" class="inline-flex items-center justify-center w-full md:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 rounded-xl transition duration-200 shadow-sm dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg class="w-4 h-4 me-2" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v6.59l1.95-2.1a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 1 1 1.1-1.02l1.95 2.1V3.75A.75.75 0 0 1 10 3ZM4 16a1 1 0 0 1 1-1h10a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Z" clip-rule="evenodd" /></svg>
                    {{ __('Descargar Historial Clínico') }}
                </a>
            </div>
        </div>
    </div>

    @php
    $gender = match ($patient->gender) {
        'male' => __('Masculino'),
        'female' => __('Femenino'),
        default => __('No especificado'),
    };
    @endphp

    <!-- Cuerpo del Perfil -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información Básica -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 h-fit">
            <h3 class="font-bold text-gray-800 dark:text-white mb-4 border-b dark:border-gray-700 pb-2">
                {{ __('Datos del Paciente') }}
            </h3>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Localización:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->city?->name && $patient->department?->name ? $patient->city->name . ', ' . $patient->department->name : __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Género:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $gender }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Edad:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->age ? $patient->age . ' años' : __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Cumpleaños:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->birth_date ? \Illuminate\Support\Carbon::parse($patient->birth_date)->format('Y-m-d') : __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('RH:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->blood_type ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Estatura:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->height ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Peso:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->weight ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('IMC:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->getImcAttribute() ? $patient->getImcAttribute() ." ". $patient->getImcStatusAttribute() : __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Seguro médico:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->insurance->name ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Ocupación:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->occupation ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Zona de residencia:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->residence_zone ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Estado civil:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->civil_status ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Etnia:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->ethnicity ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Tipo de Afiliación:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->affiliation_type ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Tipo de régimen:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->regime_type ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Nivel de sisben:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->sisben_level ?? __('No especificado') }}</span></li>
                
                <li class="flex justify-between items-center border-t dark:border-gray-700 pt-2 mt-2"><span class="text-gray-400">{{ __('Contacto Emergencia:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->emergency_contact_name ? ucfirst($patient->emergency_contact_name) : __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Teléfono Contacto:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->emergency_contact_phone ?? __('No especificado') }}</span></li>
                <li class="flex justify-between items-center"><span class="text-gray-400">{{ __('Relación:') }}</span> <span class="font-bold text-gray-700 dark:text-gray-300">{{ $patient->emergency_contact_relationship ?? __('No especificado') }}</span></li>                
            </ul>
        </div>
        <!-- Secciones Clínicas Consolidadas -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <!-- Bloque condiciones permanentes -->
                <h3 class="font-bold text-gray-800 dark:text-white border-b dark:border-gray-700 pb-2 mb-4">
                    {{ __('Condiciones permanentes') }}
                </h3>            
                <div class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-2xl">                
                    {{ empty(trim($patient->permanent_conditions)) ? __('No hay condiciones permanentes registradas.') : $patient->permanent_conditions }}
                </div>

                <!-- Bloque alergias -->
                <h3 class="font-bold text-gray-800 dark:text-white mt-8 border-b dark:border-gray-700 pb-2 mb-4">
                    {{ __('Alergias') }}
                </h3>            
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">                
                    @forelse($patient->allergies as $allergy)
                        @php
                        $severityBadge = match ($allergy->severity) {
                            'severe' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'moderate' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                            default => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        };
                        @endphp
                        <div class="p-4 rounded-2xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 dark:text-white text-sm">{{ $allergy->name }}</span>
                                <span class="text-xs font-semibold uppercase px-2.5 py-0.5 rounded-full {{ $severityBadge }}">
                                    {{ __($allergy->severity) }}
                                </span>
                            </div>
                            @if($allergy->reaction)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic border-t dark:border-gray-700 pt-1.5">
                                    <strong>{{ __('Reacción:') }}</strong> {{ $allergy->reaction }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic col-span-2">{{ __('No hay alergias registradas.') }}</p>
                    @endforelse            
                </div>

                <!-- Bloque medicamentos -->
                <h3 class="font-bold text-gray-800 dark:text-white mt-8 border-b dark:border-gray-700 pb-2 mb-4">
                    {{ __('Medicamentos actuales') }}
                </h3>            
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($patient->medications as $medication)
                        <div class="p-4 rounded-2xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30 {{ !$medication->active ? 'opacity-60' : '' }}">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 dark:text-white text-sm {{ !$medication->active ? 'line-through text-gray-400' : '' }}">{{ $medication->name }}</span>
                                @if($medication->dosage)
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $medication->dosage }}
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-1 border-t dark:border-gray-700 pt-1.5">
                                <p><strong>{{ __('Frecuencia:') }}</strong> {{ $medication->frequency ?? __('No especificada') }}</p>
                                @if($medication->notes)
                                    <p class="italic"><strong>{{ __('Indicaciones:') }}</strong> {{ $medication->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic col-span-2">{{ __('No hay medicamentos registrados.') }}</p>
                    @endforelse
                </div>

                <!-- Bloque cirugías -->
                <h3 class="font-bold text-gray-800 dark:text-white mt-8 border-b dark:border-gray-700 pb-2 mb-4">
                    {{ __('Cirugías Previas') }}
                </h3>            
                <div class="space-y-4">
                    @forelse($patient->surgeries as $surgery)
                        <div class="p-4 rounded-2xl border {{ $surgery->anesthesia_complications ? 'border-red-200 bg-red-50/30 dark:border-red-900/50 dark:bg-red-950/10' : 'border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30' }}">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 dark:text-white text-sm">{{ $surgery->name }}</span>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    {{ $surgery->year ?? __('Año no reg.') }}
                                </span>
                            </div>
                            
                            @if ($surgery->observations || $surgery->anesthesia_complications)
                                <div class="mt-2 text-xs space-y-1 border-t dark:border-gray-700 pt-1.5 text-gray-600 dark:text-gray-400">
                                    @if($surgery->observations)
                                        <p><strong>{{ __('Observaciones:') }}</strong> {{ $surgery->observations }}</p>
                                    @endif
                                    @if ($surgery->anesthesia_complications)
                                        <p class="text-red-700 dark:text-red-400 font-medium bg-red-100/50 dark:bg-red-950/40 p-2 rounded-xl mt-1">
                                            ⚠️ <strong>{{ __('Complicación anestésica:') }}</strong> {{ $surgery->anesthesia_details }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('No hay cirugías registradas.') }}</p>
                    @endforelse
                </div>
                <!-- Bloque antecedentes familiares-->
                <h3 class="font-bold text-gray-800 dark:text-white mt-8 border-b dark:border-gray-700 pb-2 mb-4">
                    {{ __('Antecedentes Familiares') }}
                </h3>            
                <div class="space-y-3">
                    @forelse($patient->familyHistories as $history)
                        <div class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/20">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 dark:text-white text-sm">{{ $history->condition }}</span>
                                <span class="text-xs font-bold uppercase px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-400">
                                    {{ $history->relationship }}
                                </span>
                            </div>
                            @if($history->notes)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic border-t dark:border-gray-700 pt-1.5">{{ $history->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('No hay antecedentes familiares registrados.') }}</p>
                    @endforelse
                </div>                        
            </div>

            <!-- Bloque histórico de la evolución médica (patient_histories) -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 border-b dark:border-gray-700 pb-2">
                    {{ __('Historial de Citas y Evoluciones Médicas') }}
                </h3>
                <div class="divide-y divide-gray-100 dark:divide-gray-700 space-y-4">
                    @forelse($patient->histories as $history)
                        @php
                        $statusClass = match ($history->entry_type) {
                            'emergency' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'follow_up' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                            'consultation' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                        @endphp
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between pt-4 gap-2 first:pt-0">
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 dark:text-white text-sm">
                                    {{ $history->reason_for_consultation }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ $history->created_at->translatedFormat('d \d\e F, Y') }} — {{ $history->created_at->format('h:i A') }}
                                </p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 text-xs">
                                    @if($history->symptoms)
                                        <div>
                                            <span class="font-semibold text-gray-400 block">{{ __('Síntomas:') }}</span>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $history->symptoms }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-semibold text-gray-400 block">{{ __('Diagnóstico:') }}</span>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ $history->diagnosis }}</p>
                                    </div>
                                </div>

                                @if($history->treatment_plan)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 bg-gray-50 dark:bg-gray-700/50 p-2.5 rounded-xl italic border-l-2 border-blue-500">
                                        <strong>{{ __('Plan de Conducta:') }}</strong> {{ $history->treatment_plan }}
                                    </p>
                                @endif
                                
                                <p class="text-[11px] text-gray-400 mt-2">
                                    {{ __('Registrado por:') }} Dr(a). {{ $history->doctor->user->name ?? __('Médico del Staff') }}
                                </p>
                            </div>
                            <span class="text-xs font-semibold uppercase px-2.5 py-1 rounded-full w-fit {{ $statusClass }}">
                                {{ __($history->entry_type) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic pt-2">{{ __('No hay consultas previas registradas.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>            
    </div>        
</div>
