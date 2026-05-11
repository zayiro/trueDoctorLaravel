@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis citas',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Mensajes de Éxito --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <p class="font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Errores de Validación (Formularios) --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center mb-2">
                <span class="mr-2">❌</span>
                <p class="font-bold">Por favor, corrige los siguientes errores:</p>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="py-8 px-4 max-w-5xl mx-auto">
        <!-- Encabezado y Filtro -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Mis Citas</h2>
                <p class="text-gray-500 text-sm">Listado cronológico de tus consultas médicas</p>
            </div>
            
            <form action="{{ route('patient.appointments.index') }}" method="GET">
                <select name="status" onchange="this.form.submit()" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pendiente</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>✅ Confirmada</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>🏁 Completada</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Cancelada</option>
                </select>
            </form>
        </div>

        <!-- Lista de Citas -->
        <ul class="space-y-4">
            @forelse($appointments as $appointment)
                @php
                switch ($appointment->status) {
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
                <li class="bg-white border border-gray-200 rounded-lg shadow-sm hover:border-blue-300 transition-colors">
                    <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        
                        <!-- Lado Izquierdo: Fecha y Hora -->
                        <div class="flex items-center gap-4 min-w-[150px]">
                            <div class="bg-blue-50 text-blue-700 rounded-lg p-3 text-center flex-shrink-0">
                                <span class="block text-xs uppercase font-bold">{{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('M') }}</span>
                                <span class="block text-xl font-black">{{ \Carbon\Carbon::parse($appointment->date)->format('d') }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->duration }} min</p>
                            </div>
                        </div>

                        <!-- Centro: Información Médica -->
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-900">
                                {{ $appointment->service->name }}
                            </h4>
                            <div class="gap-x-4 gap-y-1 mt-1 text-sm text-gray-600">
                                <span class="flex items-center font-bold">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    {{ $appointment->doctor->user->name }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    {{ $appointment->address->type === 'virtual' ? 'Telemedicina' : $appointment->address->name .", ". $appointment->address->address }}
                                </span>
                            </div>
                        </div>

                        <!-- Lado Derecho: Estado y Acciones -->
                        <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $appointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $appointment->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ ucfirst($status) }}
                            </span>
                            
                            @if($appointment->status === 'confirmed' && $appointment->meeting_link)
                                <a href="{{ $appointment->meeting_link }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">
                                    Entrar a cita →
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="py-12 text-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <p class="text-gray-500 font-medium">No tienes citas registradas con este filtro.</p>
                </li>
            @endforelse
        </ul>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $appointments->links() }}
        </div>
    </div>

    {{ $appointments->links() }} <!-- Paginación -->
</x-admin-layout>
