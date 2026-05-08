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

        <!-- Cuerpo del Perfil -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Información Básica -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Datos Clínicos</h3>
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between"><span class="text-gray-400">Genero:</span> <span class="font-bold text-gray-700">Masculino</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Edad:</span> <span class="font-bold text-gray-700">28 años</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Cumpleaños:</span> <span class="font-bold text-gray-700">29 de abril</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">RH:</span> <span class="font-bold text-gray-700">O+</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Estatura:</span> <span class="font-bold text-gray-700">190 cm</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Peso:</span> <span class="font-bold text-gray-700">69 Kg</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Seguro médico:</span> <span class="font-bold text-gray-700">Eps sanitas</span></li>
                    <li>
                        @if(auth()->user()->canDo('can_export_history'))
                            <button>Descargar Historia Clínica PDF</button>
                        @endif
                    </li>
                </ul>
            </div>

            <!-- Historial de Citas -->
            <div class="md:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Últimas Consultas</h3>
                <div class="space-y-4">
                    @forelse($patient->appointments as $history)
                        <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $history->service->name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $history->start_time }}</p>
                            </div>
                            <span class="text-xs font-bold text-blue-600">{{ $history->status }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay registros previos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
