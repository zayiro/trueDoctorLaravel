@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Cirugias',
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
    
    <div class="max-w-4xl mx-auto py-10 px-4">        
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Antecedentes Quirúrgicos</h1>
                <p class="text-sm text-gray-500">Registro de intervenciones pasadas y observaciones médicas.</p>                
            </div>                        
            <button onclick="openModal('modalCirugia')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold text-sm transition">
                + Registrar Cirugía
            </button>
        </div>

        <div class="text-sm text-gray-500 mt-3 mb-8">
            Para registrar una cirugía en tus antecedentes quirúrgicos, debes asegurar que los documentos generados por el centro hospitalario sean incorporados a tu registro oficial. La historia clínica es el documento fundamental que registra cronológicamente las condiciones de salud, actos médicos y procedimientos quirúrgicos
        </div>

        <div class="space-y-4">
            @forelse($surgeries as $surgery)
                <div class="bg-white border rounded-xl p-5 shadow-sm hover:border-blue-200 transition">
                    <div class="flex flex-col md:flex-row justify-between gap-4">
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-bold text-gray-900">{{ $surgery->name }}</h3>
                                <span class="text-sm font-medium text-gray-400">({{ $surgery->year ?? 'Año no registrado' }})</span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4 italic">
                                {{ $surgery->observations ?? 'Sin observaciones adicionales.' }}
                            </p>

                            <!-- Alerta de Anestesia -->
                            @if($surgery->anesthesia_complications)
                                <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-lg">
                                    <div class="flex items-center gap-2 text-red-700 mb-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"></path></svg>
                                        <span class="font-bold text-xs uppercase">Complicación con Anestesia</span>
                                    </div>
                                    <p class="text-xs text-red-600">{{ $surgery->anesthesia_details }}</p>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-green-600 text-xs font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                    Sin complicaciones de anestesia
                                </div>
                            @endif
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-start gap-2">
                            <a href="{{ route('patient.surgeries.edit', $surgery) }}" class="p-2 text-gray-400 hover:text-blue-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <form action="{{ route('patient.surgeries.destroy', $surgery->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('¿Estás seguro de que deseas eliminar esta cirugía? Esta acción no se puede deshacer.')"
                                        class="p-2 text-red-600 hover:text-red-800 font-bold text-sm flex items-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>                                    
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-gray-50 border-2 border-dashed rounded-2xl py-12 text-center">
                    <p class="text-gray-500">No se han registrado cirugías en el sistema.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div id="modalCirugia" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-80 flex items-center justify-center p-4 backdrop-blur-sm">        
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Registrar Cirugía</h3>
                <button onclick="closeModal('modalCirugia')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            
            <form action="{{ route('patient.surgeries.store') }}" method="POST">
                @csrf
                
                <div id="methodField"></div> <!-- Aquí inyectaremos el @method('PUT') cuando sea edición -->
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nombre de la Cirugía</label>
                        <input type="text" name="name" required class="w-full border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <!-- Año -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Año aproximado</label>
                        <input type="number" name="year" placeholder="Ej: 2022" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Observaciones</label>
                        <textarea name="observations" rows="2" class="w-full border-gray-300 rounded-xl text-sm"></textarea>
                    </div>

                    <!-- Sección Anestesia (Estilo alerta) -->
                    <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="anesthesia_complications" value="1" onchange="toggleAnesthesia(this)" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span class="ml-2 text-sm font-bold text-red-700">¿Hubo complicaciones?</span>
                        </label>
                        
                        <div id="anesthesia_details_box" class="hidden mt-3">
                            <textarea name="anesthesia_details" placeholder="Describe la reacción o problema..." class="w-full border-red-200 rounded-lg focus:ring-red-500 text-xs py-2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-2">
                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                        Guardar Cirugía
                    </button>
                    <button type="button" onclick="closeModal('modalCirugia')" class="w-full py-2 text-sm text-gray-500 font-semibold hover:text-gray-700">
                        Cerrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        function toggleAnesthesia(checkbox) {
            const box = document.getElementById('anesthesia_details_box');
            box.classList.toggle('hidden', !checkbox.checked);
        }
    </script>
</x-admin-layout>    
