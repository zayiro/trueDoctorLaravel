@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Paciente',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div>
        <!-- Botón Volver -->
        <a href="{{ route('partner.appointments.index') }}" class="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-2 mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver a la agenda
        </a>

        <!-- Header del Paciente -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-gray-100 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-center">
                <div class="h-24 w-24 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl font-black">
                    {{ substr($patient->user->name, 0, 1) }}
                </div>
                <div class="text-center md:text-left flex-1">
                    <h1 class="text-3xl font-black text-gray-800 text-left">{{ $patient->user->name }}</h1>
                    <p class="text-gray-500 font-medium text-left">ID {{ $patient->identification }}</p>
                    <div class="flex flex-wrap gap-3 mt-4 justify-center md:justify-start">
                        <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600">📧 {{ $patient->user->email }}</span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600">📱 {{ $patient->phone ?? 'Sin teléfono' }}</span>                        
                    </div>
                </div>
            </div>
        </div>

        @php
        switch ($patient->gender) {
            case 'male': $gender = 'Masculino';
            break;
            case 'female': $gender = 'Femenino';
            break;
            default: $gender = 'No especificado';
        }
        @endphp

        <!-- Cuerpo del Perfil -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Información Básica -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Datos del Paciente</h3>
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between"><span class="text-gray-400">Localización:</span> <span class="font-bold text-gray-700">{{ $patient->city->name && $patient->department->name ? $patient->city->name . ', ' . $patient->department->name : 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Genero:</span> <span class="font-bold text-gray-700">{{ $gender }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Edad:</span> <span class="font-bold text-gray-700">{{ $patient->age ? $patient->age . ' años' : 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Cumpleaños:</span> <span class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($patient->birth_date)->format('Y-m-d') ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">RH:</span> <span class="font-bold text-gray-700">{{ $patient->blood_type ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Estatura:</span> <span class="font-bold text-gray-700">{{ $patient->height ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Peso:</span> <span class="font-bold text-gray-700">{{ $patient->weight ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">IMC:</span> <span class="font-bold text-gray-700">{{ $patient->getImcAttribute() ? $patient->getImcAttribute() ." ". $patient->getImcStatusAttribute() : 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Seguro médico:</span> <span class="font-bold text-gray-700">{{ $patient->insurance->name ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Ocupación:</span> <span class="font-bold text-gray-700">{{ $patient->occupation ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Zona de residencia:</span> <span class="font-bold text-gray-700">{{ $patient->residence_zone ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Estado civil:</span> <span class="font-bold text-gray-700">{{ $patient->civil_status ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Etnia:</span> <span class="font-bold text-gray-700">{{ $patient->ethnicity ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Tipo de Afiliación:</span> <span class="font-bold text-gray-700">{{ $patient->affiliation_type ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Tipo de regimen:</span> <span class="font-bold text-gray-700">{{ $patient->regime_type ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Nivel de sisben:</span> <span class="font-bold text-gray-700">{{ $patient->sisben_level ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Contacto:</span> <span class="font-bold text-gray-700">{{ ucfirst($patient->emergency_contact_name) ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Teléfono:</span> <span class="font-bold text-gray-700">{{ $patient->emergency_contact_phone ?? 'No especificado' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Relación:</span> <span class="font-bold text-gray-700">{{ $patient->emergency_contact_relationship ?? 'No especificado' }}</span></li>
                    <li>
                        @if($plan->can_export_history)
                        <a href="{{ route('patient.pdf.clinical-history', $patient) }}" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 inset-ring inset-ring-blue-700/10 underline">
                            Descargar Historia Clínica PDF
                        </a>
                        @endif
                    </li>
                </ul>
            </div>

            <div class="md:col-span-2 bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                <!-- Bloque condiciones permanentes -->
                <h3 class="text-gray-900 font-bold border-b pb-2 mb-3">Condiciones permanentes</h3>            
                <div class="space-y-3 text-sm">                
                    <div class="p-3 mb-2 rounded-lg border bg-gray-50 border-gray-200">
                        {{ $patient->permanent_conditions === '' ? 'No hay condiciones permanentes registradas.' : $patient->permanent_conditions }}
                    </div>            
                </div>

                <!-- Bloque alergias -->
                <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 mt-8">Alergias</h3>            
                <div class="space-y-3 text-sm">                
                    @forelse($patient->allergies as $allergy)
                    @php
                    switch ($allergy->severity) {
                        case 'mild': $severity = 'Leve';
                        break;
                        case 'moderate': $severity = 'Moderada';
                        break;
                        case 'severe': $severity = 'Severa';
                        break;
                        default: $severity = 'No especificado';
                    }
                    @endphp
                        <div class="p-3 mb-2 rounded-lg border @if($allergy->severity == 'severe') bg-red-50 border-red-200 @else bg-gray-50 border-gray-200 @endif">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">{{ $allergy->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full @if($allergy->severity == 'severe') bg-red-200 text-red-800 @else bg-gray-200 text-gray-700 @endif">
                                    {{ $severity }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $allergy->reaction }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay alergias registradas.</p>
                    @endforelse            
                </div>

                <!-- Bloque medicamentos -->
                <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 mt-8">Medicamentos</h3>            
                <div class="space-y-3 text-sm">
                    @forelse($patient->medications as $medication)
                        <div class="p-3 mb-2 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">{{ $medication->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-blue-200 text-blue-800">
                                    {{ $medication->dosage }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $medication->frequency }}</p>
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $medication->notes }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay medicamentos registrados.</p>
                    @endforelse
                </div>

                <!-- Bloque cirugias -->
                <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 mt-8">Cirugías</h3>            
                <div class="space-y-3 text-sm">
                    @forelse($patient->surgeries as $surgery)
                        <div class="p-3 mb-2 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">{{ $surgery->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-green-200 text-green-800">
                                    {{ \Carbon\Carbon::parse($surgery->date)->format('Y-m-d') }}
                                </span>
                            </div>
                            @if ($surgery->observations)
                                <p class="text-xs text-gray-600 mt-1 italic">{{ $surgery->observations }}</p>                            
                            @endif

                            @if ($surgery->anesthesia_complications)
                            <p class="text-xs text-gray-600 mt-1 italic"><span class="font-bold">Complicaciones anestésicas:</span> {{ $surgery->anesthesia_details }}</p>
                            @endif
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $surgery->notes }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay cirugías registradas.</p>
                    @endforelse
                </div>

                <!-- Bloque antecedentes familiares-->
                <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 mt-8">Antecedentes familiares</h3>            
                <div class="space-y-3 text-sm">
                    @forelse($patient->familyHistories as $history)
                        <div class="p-3 mb-2 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">{{ $history->condition }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-purple-200 text-purple-800">
                                    {{ $history->relationship }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $history->notes }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay antecedentes familiares registrados.</p>
                    @endforelse
                </div>                                
            </div>            
        </div>    
        
        <!-- Bloque últimas consultas -->
        <div class="md:col-span-2 bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Últimas Consultas</h3>
            <div class="space-y-4">
                @forelse($patient->appointments as $history)
                    @php
                    switch ($history->status) {
                        case 'confirmed': $status = 'Confirmada';
                        break;
                        case 'pending': $status = 'Pendiente';
                        break;
                        case 'cancelled': $status = 'Cancelada';
                        break;
                        case 'completed': $status = 'Completada';
                        break;
                    }
                    @endphp
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition">
                        <div>
                            <p class="font-bold text-gray-800">{{ $history->service->name }}</p>
                            <p class="text-sm text-gray-400">{{ $history->notes }}</p>
                            <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($history->date)->translatedFormat('d \d\e F, Y') }} a las {{ $history->start_time }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $history->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $history->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $history->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $history->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">No hay registros previos.</p>
                @endforelse
            </div>
        </div>

        <div class="md:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Nota de Evolución Actual</h3>
            <div class="space-y-4">
                <form action="#" method="POST">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    
                    <!-- Si entramos desde una cita, capturamos el ID. Si no, será null -->
                    <input type="hidden" name="appointment_id" value="{{ $appointment->id ?? '' }}">
    
                    <!-- Aquí incluyes tus campos de diagnóstico, tratamiento, etc. -->
                    @include('partner.patients.partials.history-form')

                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded mt-3">
                        Guardar Nota
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
