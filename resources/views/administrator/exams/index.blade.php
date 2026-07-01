@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Lista de Examenes Médicos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">
        <!-- Alertas de Sistema -->
        @if (session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Exámenes Médicos</h2>
                <p class="text-sm text-gray-500">Historial de análisis procesados.</p>
            </div>
            
            <form method="GET" action="{{ route('exams.index') }}" class="flex gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Buscar por email..." 
                    class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 w-64"
                >
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">
                    Filtrar
                </button>
            </form>
        </div>
        <div class="space-y-4">
            @forelse($exams as $exam)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    
                    <!-- Datos del Examen -->
                    <div class="flex-1 min-w-0">
                        <!-- Especialidad y Fecha -->
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">
                                {{ $exam->ai_response['especialidad_slug'] ?? 'Medicina General' }}
                            </span>
                            <span class="hidden sm:inline text-gray-300">•</span>
                            <span class="text-xs text-gray-400">
                                {{ $exam->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <!-- Nombre del Examen -->
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate mb-2">
                            {{ $exam->ai_response['nombre_examen'] ?? 'Examen sin procesar' }}
                        </h3>

                        <!-- Meta Datos en Grid Responsivo -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-600">
                            <div class="truncate">
                                Paciente: <span class="font-medium text-gray-800">{{ $exam->customer_email }}</span>
                            </div>
                            <div>
                                ID: <span class="font-medium text-gray-800">{{ $exam->id }}</span>
                            </div>
                            <div class="sm:col-span-2 truncate">
                                Token: <span class="font-mono text-xs bg-gray-50 border border-gray-100 px-1.5 py-0.5 rounded text-gray-800">{{ $exam->access_token }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 lg:justify-end whitespace-nowrap pt-3 lg:pt-0 border-t border-gray-100 lg:border-t-0">
                        <form action="{{ route('administrator.exams.resend', $exam) }}" method="POST" onsubmit="return confirm('¿Reenviar este examen?')" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto text-xs bg-indigo-600 text-white font-semibold px-4 py-2 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition text-center justify-center flex items-center">
                                Reenviar
                            </button>
                        </form>

                        @if ($exam->payment_status === 'pending')
                        <form action="{{ route('administrator.exams.toggle', $exam) }}" method="POST" onsubmit="return confirm('¿Deseas alterar el estado de pago del examen?');" class="w-full sm:w-auto">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full sm:w-auto py-2 px-4 text-xs font-semibold rounded-lg bg-indigo-100 text-slate-700 hover:bg-slate-200 transition text-center justify-center flex items-center">
                                Activar pago
                            </button>
                        </form>
                        @endif
                    </div>

                </div>

            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">
                    No hay exámenes registrados.
                </div>
            @endforelse

            <div class="mt-4">
                {{ $exams->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>