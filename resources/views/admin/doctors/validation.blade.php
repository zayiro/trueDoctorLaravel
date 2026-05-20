@extends('layouts.admin') {{-- Ajusta a tu layout de administración actual --}}

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-950">Validación de Profesionales</h1>
        <p class="text-sm text-slate-500">Revisa y aprueba las credenciales de los médicos registrados en OpenDoctor.</p>
    </div>

    @if (session('success'))
        <div class="p-4 mb-6 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <th class="p-4">Médico</th>
                    <th class="p-4">Identificación / Licencia</th>
                    <th class="p-4">Documentos Adjuntos</th>
                    <th class="p-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                @forelse ($doctors as $doctor)
                    <tr>
                        <!-- Datos básicos -->
                        <td class="p-4">
                            <div class="font-semibold text-slate-900">{{ $doctor->user->name ?? 'Nombre no disponible' }}</div>
                            <div class="text-xs text-slate-400">{{ $doctor->user->email ?? '' }}</div>
                        </td>
                        
                        <!-- Cédula / Licencia médica -->
                        <td class="p-4">
                            <div class="text-xs font-mono bg-slate-100 text-slate-700 px-2 py-1 rounded inline-block mb-1">
                                CC: {{ $doctor->identification }}
                            </div>
                            @if($doctor->medical_license)
                                <div class="text-xs text-slate-500 block">Licencia: {{ $doctor->medical_license }}</div>
                            @endif
                        </td>

                        <!-- Enlaces seguros a los documentos -->
                        <td class="p-4 space-y-1">
                            <a href="{{ route('admin.doctors.document.view', [$doctor, 'cedula']) }}" target="_blank" 
                               class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                🪪 Ver Cédula de Identidad
                            </a>
                            <br>
                            <a href="{{ route('admin.doctors.document.view', [$doctor, 'tarjeta']) }}" target="_blank" 
                               class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                📜 Ver Tarjeta Profesional
                            </a>
                        </td>

                        <!-- Botones de Acción -->
                        <td class="p-4 text-right">
                            <div class="inline-flex items-center gap-x-2">
                                <!-- Botón Aprobar -->
                                <form action="{{ route('admin.doctors.validation.update', $doctor) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                        Aprobar
                                    </button>
                                </form>

                                <!-- Botón Rechazar -->
                                <form action="{{ route('admin.doctors.validation.update', $doctor) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas rechazar este médico? Se eliminarán sus archivos para que pueda subirlos de nuevo.')">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition">
                                        Rechazar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-400">
                            No hay médicos pendientes de validación en este momento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación de Laravel -->
    <div class="mt-4">
        {{ $doctors->links() }}
    </div>
</div>
@endsection
