@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Medicaciones',
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

    <div class="py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Medicamentos Actuales</h2>
                <p class="text-sm text-gray-500">Gestiona tus tratamientos y dosis diarias.</p>
            </div>
            <button onclick="resetAndOpenModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm">
                + Añadir Medicamento
            </button>
        </div>

        <ul class="space-y-4">
            @forelse($medications as $med)
                <li class="bg-white border rounded-xl p-5 shadow-sm {{ !$med->active ? 'opacity-60 bg-gray-50' : 'border-indigo-100' }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold text-gray-900">{{ $med->name }}</h3>
                                @if($med->active)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold uppercase rounded-full">Activo</span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-[10px] font-bold uppercase rounded-full">Suspendido</span>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mt-3 text-sm">
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19.423 15.641a2 2 0 000-2.828L14.474 7.865a2 2 0 00-2.828 0l-4.95 4.95a2 2 0 000 2.828l4.95 4.95a2 2 0 002.828 0l4.95-4.95z"></path></svg>
                                    <strong>Dosis:</strong>&nbsp;{{ $med->dosage ?? 'No especificada' }}
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <strong>Frecuencia:</strong>&nbsp;{{ $med->frequency ?? 'No especificada' }}
                                </div>
                            </div>

                            @if($med->notes)
                                <p class="mt-3 text-xs text-gray-500 bg-gray-100 p-2 rounded-lg">
                                    <strong>Nota:</strong> {{ $med->notes }}
                                </p>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            <!-- Botón Editar -->
                            <button onclick="editMedication({{ $med }})" class="p-2 text-gray-400 hover:text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="text-center py-12 border-2 border-dashed rounded-xl text-gray-500">
                    No tienes medicamentos registrados.
                </li>
            @endforelse
        </ul>
    </div>

    <div id="modalMedicamento" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-80 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-bold">Añadir Medicamento</h3>
                <button onclick="closeModal('modalMedicamento')" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <form id="medicationForm" action="{{ route('patient.medications.store', $patient) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dosis</label>
                            <input type="text" name="dosage" placeholder="Ej: 500mg" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Frecuencia</label>
                            <input type="text" name="frequency" placeholder="Ej: Cada 8h" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select name="active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1">Activo</option>
                            <option value="0">Suspendido</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('modalMedicamento')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            
            // Limpiar el formulario al cerrar
            document.getElementById('medicationForm').reset();
            document.getElementById('formMethod').value = 'POST';
        }

        function resetAndOpenModal() {
            const form = document.getElementById('medicationForm');
            form.reset();
            
            // Volvemos a la ruta de "Crear" (Store)
            form.action = "{{ route('patient.medications.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('modalTitle').innerText = 'Añadir Medicamento';
            
            openModal('modalMedicamento');
        }

        function editMedication(medication) {
            document.getElementById('modalTitle').innerText = 'Editar Medicamento';
            const form = document.getElementById('medicationForm');

            // Cambiamos a la ruta de "Actualizar" (Update)
            // Usamos el ID del paciente que ya tenemos en Blade y el ID del medicamento de JS             
            form.action = `/patient/medications/${medication.id}`;
            
            document.getElementById('formMethod').value = 'PUT';

            // Rellenar campos
            form.querySelector('input[name="name"]').value = medication.name;
            form.querySelector('input[name="dosage"]').value = medication.dosage || '';
            form.querySelector('input[name="frequency"]').value = medication.frequency || '';
            form.querySelector('textarea[name="notes"]').value = medication.notes || '';
            form.querySelector('select[name="active"]').value = medication.active ? "1" : "0";

            openModal('modalMedicamento');
        }        
    </script>
</x-admin-layout>