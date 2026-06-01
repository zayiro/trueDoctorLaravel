@php
$breadcrumbs = [
    [
        'name' => __('Dashboard'),
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => __('Medicaciones'),
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Mensajes de Éxito Controlados (Estilo Flowbite) --}}
    @if (session('success'))
        <div class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-xl bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 me-3" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            <div>
                <span class="font-bold">{{ __('¡Excelente!') }}</span> {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Errores de Validación de Formularios (Estilo Flowbite) --}}
    @if ($errors->any())
        <div class="flex flex-col p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
            <div class="flex items-center mb-2">
                <svg class="flex-shrink-0 inline w-5 h-5 me-3" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span class="font-bold">{{ __('Por favor, corrige los siguientes errores:') }}</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="py-6">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Medicamentos Actuales') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Gestiona tus tratamientos y dosis diarias.') }}</p>
            </div>
            <button onclick="resetAndOpenModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 rounded-xl transition duration-200 shadow-sm dark:bg-blue-500 dark:hover:bg-blue-600 w-fit">
                <svg class="w-4 h-4 me-2" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                {{ __('Añadir Medicamento') }}
            </button>
        </div>
        <!-- Listado de Medicamentos -->
        <ul class="space-y-4">
            @forelse($medications as $med)
                <li class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 shadow-sm transition duration-200 hover:shadow-md {{ !$med->active ? 'opacity-60 bg-gray-50 dark:bg-gray-900/50' : 'border-blue-100 dark:border-blue-900/30' }}">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white {{ !$med->active ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                    {{ $med->name }}
                                </h3>
                                @if($med->active)
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900/30 dark:text-green-400 uppercase tracking-wide text-[10px]">{{ __('Activo') }}</span>
                                @else
                                    <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wide text-[10px]">{{ __('Suspendido') }}</span>
                                @endif
                            </div>
                            
                            <!-- Detalles Técnicos -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 me-2 text-gray-400" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor"><path d="M7 2a2 2 0 0 0-2 2v1.172a2 2 0 0 0 .586 1.414l4.414 4.414a2 2 0 0 1 .586 1.414v1.172a2 2 0 0 0 .586 1.414l1 1A2 2 0 0 0 13.56 15H15a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H7Z"/></svg>
                                    <span class="text-gray-400 me-1">{{ __('Dosis:') }}</span> <strong class="text-gray-700 dark:text-gray-200">{{ $med->dosage ?? __('No especificada') }}</strong>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 me-2 text-gray-400" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" /></svg>
                                    <span class="text-gray-400 me-1">{{ __('Frecuencia:') }}</span> <strong class="text-gray-700 dark:text-gray-200">{{ $med->frequency ?? __('No especificada') }}</strong>
                                </div>
                            </div>

                            @if($med->notes)
                                <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700/50 italic">
                                    <strong>{{ __('Nota médica:') }}</strong> {{ $med->notes }}
                                </div>
                            @endif
                        </div>

                        <!-- Panel de Acciones -->
                        <div class="flex gap-1">
                            <button onclick="editMedication({{ $med }})" class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50" title="{{ __('Editar Medicamento') }}">
                                <svg class="w-5 h-5" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <!-- Estado Vacío -->
                <li class="flex flex-col items-center justify-center text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800">
                    <svg class="w-10 h-10 mb-3 text-gray-300 dark:text-gray-600" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.732 2.11 1.704m-7.41 0A2.251 2.251 0 0 1 12 2.25c.38 0 .73.094 1.039.262M9 5.25v13.5A2.25 2.25 0 0 0 11.25 21h4.5A2.25 2.25 0 0 0 18 18.75V5.25M9 5.25h9" /></svg>
                    <span class="text-sm italic font-medium">{{ __('No tienes medicamentos registrados en el sistema.') }}</span>
                </li>
            @endforelse
        </ul>
    </div>
    <!-- Modal de Registro/Edición de Medicamentos -->
    <div id="modalMedicamento" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/80 p-4 backdrop-blur-sm flex items-center justify-center" aria-labelledby="modalTitle" role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 border border-gray-100 dark:border-gray-700 transition-all">
            <!-- Cabecera del Modal -->
            <div class="flex justify-between items-center mb-5">
                <h3 id="modalTitle" class="text-xl font-black text-gray-900 dark:text-white">
                    {{ __('Añadir Medicamento') }}
                </h3>
                <button type="button" onclick="closeModal('modalMedicamento')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg p-1.5 inline-flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 transition" aria-label="{{ __('Cerrar modal') }}">
                    <svg class="w-5 h-5" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulario Operativo -->
            <form id="medicationForm" action="{{ route('patient.medications.store', $patient->id) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="space-y-4">
                    <!-- Nombre del Medicamento -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Nombre del fármaco') }}</label>
                        <input type="text" name="name" required class="block w-full rounded-xl border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm dark:bg-gray-700 dark:text-white" placeholder="{{ __('Ej: Metformina') }}">
                    </div>

                    <!-- Grilla: Dosis y Frecuencia -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Dosis') }}</label>
                            <input type="text" name="dosage" placeholder="Ej: 500mg" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Frecuencia') }}</label>
                            <input type="text" name="frequency" placeholder="Ej: Cada 8h" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <!-- Notas Clínicas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Indicaciones / Notas adicionales') }}</label>
                        <textarea name="notes" rows="2" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm dark:bg-gray-700 dark:text-white" placeholder="{{ __('Ej: Tomar después de las comidas.') }}"></textarea>
                    </div>

                    <!-- Estado Operativo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Estado del tratamiento') }}</label>
                        <select name="active" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm dark:bg-gray-700 dark:text-white">
                            <option value="1">{{ __('Activo (Tratamiento vigente)') }}</option>
                            <option value="0">{{ __('Suspendido / Historial') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Barra de Acciones del Formulario -->
                <div class="mt-6 flex justify-end gap-3 border-t dark:border-gray-700 pt-4">
                    <button type="button" onclick="closeModal('modalMedicamento')" class="px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit" class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm dark:bg-blue-500 dark:hover:bg-blue-600">
                        {{ __('Guardar Cambios') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts de Control Dinámico de Interfaz -->
    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                document.getElementById('medicationForm').reset();
                document.getElementById('formMethod').value = 'POST';
            }
        }

        function resetAndOpenModal() {
            const form = document.getElementById('medicationForm');
            if (form) {
                form.reset();
                form.action = "{{ route('patient.medications.store', $patient->id) }}";
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('modalTitle').innerText = "{{ __('Añadir Medicamento') }}";
                openModal('modalMedicamento');
            }
        }

        function editMedication(medication) {
            const form = document.getElementById('medicationForm');
            if (form) {
                document.getElementById('modalTitle').innerText = "{{ __('Editar Medicamento') }}";
                form.action = `/patient/medications/${medication.id}`;
                document.getElementById('formMethod').value = 'PUT';

                form.querySelector('input[name="name"]').value = medication.name || '';
                form.querySelector('input[name="dosage"]').value = medication.dosage || '';
                form.querySelector('input[name="frequency"]').value = medication.frequency || '';
                form.querySelector('textarea[name="notes"]').value = medication.notes || '';
                form.querySelector('select[name="active"]').value = medication.active ? "1" : "0";

                openModal('modalMedicamento');
            }
        }        
    </script>
</x-admin-layout>
