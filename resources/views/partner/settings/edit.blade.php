@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Editar Configuración',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-8 px-4" x-data="{ acceptsPayments: {{ $settings->accepts_online_payments ? 'true' : 'false' }}, requiresApproval: {{ $settings->requires_approval ? 'true' : 'false' }}, allowCancellation: {{ $settings->allow_patient_cancellation ? 'true' : 'false' }} }">
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <span class="sr-only">Éxito</span>
                <div class="ms-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="sr-only">Cerrar</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>
                            <div style="background-color: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #f87171;">
                                {{ $error }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
                
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Configuración de la Consulta</h1>
            <p class="text-sm text-gray-600">Personaliza las reglas de tu agenda, políticas de cancelación y notificaciones.</p>
        </div>
        
        <form action="{{ route('partner.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- SECCIÓN 1: Pagos -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">💳 Pagos y Facturación</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="font-medium text-gray-700 block">Activar Pagos Online</label>
                            <span class="text-xs text-gray-500">Exige el pago de la consulta inmediatamente después de seleccionar el horario.</span>
                        </div>
                        <button type="button" 
                            @click="acceptsPayments = !acceptsPayments; if(acceptsPayments) requiresApproval = false" 
                            :class="acceptsPayments ? 'bg-blue-600' : 'bg-gray-200'" 
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                            
                            <input type="hidden" name="accepts_online_payments" :value="acceptsPayments ? 1 : 0">
                            <span :class="acceptsPayments ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="acceptsPayments" x-transition>
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700">Divisa base</label>
                            <select id="currency" name="currency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="COP" {{ $settings->currency == 'COP' ? 'selected' : '' }} selected>COP - Peso Colombiano</option>
                                <option value="USD" {{ $settings->currency == 'USD' ? 'selected' : '' }} disabled>USD - Dólar Americano</option>
                                <option value="MXN" {{ $settings->currency == 'MXN' ? 'selected' : '' }} disabled>MXN - Peso Mexicano</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>            

            <!-- SECCIÓN 2: Reglas de Citas -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">📅 Control de Agenda</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Anticipación mínima para reservar (Horas)</label>
                        <input type="number" name="min_notice_hours" value="{{ $settings->min_notice_hours }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-xs text-gray-500">Bloquea horas demasiado cercanas a la hora actual.</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Visibilidad futura del calendario (Días)</label>
                        <input type="number" name="max_advance_days" value="{{ $settings->max_advance_days }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-xs text-gray-500">Límite de días hacia adelante que verá el paciente.</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tiempo de descanso entre citas (Minutos)</label>
                        <input type="number" name="buffer_time_minutes" value="{{ $settings->buffer_time_minutes }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-xs text-gray-500">Coloca un colchón de tiempo libre entre pacientes.</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Límite máximo de citas diarias</label>
                        <input type="number" name="max_appointments_per_day" value="{{ $settings->max_appointments_per_day }}" placeholder="Sin límite" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-xs text-gray-500">Cierra automáticamente el día al alcanzar esta cifra.</span>
                    </div>

                    <div class="form-group">
                        <label for="virtual_meeting_platform" class="block text-sm font-medium text-gray-700">Plataforma de Reunión Virtual</label>
                        <select id="virtual_meeting_platform" name="virtual_meeting_platform" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="" disabled>Seleccione una opción</option>
                            <option value="internal" {{ old('virtual_meeting_platform', $settings->virtual_meeting_platform ?? '') == 'internal' ? 'selected' : '' }}>Plataforma Interna</option>
                            <option value="zoom" {{ old('virtual_meeting_platform', $settings->virtual_meeting_platform ?? '') == 'zoom' ? 'selected' : '' }}>Zoom</option>
                            <option value="meet" {{ old('virtual_meeting_platform', $settings->virtual_meeting_platform ?? '') == 'meet' ? 'selected' : '' }}>Google Meet</option>
                            <option value="teams" {{ old('virtual_meeting_platform', $settings->virtual_meeting_platform ?? '') == 'teams' ? 'selected' : '' }}>Microsoft Teams</option>
                        </select>
                    </div>
                </div>

                <!-- Bloque de Aprobación Manual -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-700 block">Requiere aprobación manual</label>
                        <span class="text-xs text-gray-500">Las citas ingresarán en estado 'pendiente' hasta que las confirmes en tu panel.</span>
                    </div>
                    
                    <!-- Switch con exclusión mutua interactiva -->
                    <button type="button" 
                        @click="requiresApproval = !requiresApproval; if(requiresApproval) acceptsPayments = false" 
                        :class="requiresApproval ? 'bg-blue-600' : 'bg-gray-200'" 
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                        
                        <input type="hidden" name="requires_approval" :value="requiresApproval ? 1 : 0">
                        <span :class="requiresApproval ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 3: Cancelaciones -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">🛡️ Políticas de Cancelación</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="font-medium text-gray-700 block">Permitir cancelación autónoma al paciente</label>
                            <span class="text-xs text-gray-500">El paciente puede anular la cita desde su panel sin llamarte por teléfono.</span>
                        </div>
                        <button type="button" @click="allowCancellation = !allowCancellation" :class="allowCancellation ? 'bg-blue-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                            <input type="hidden" name="allow_patient_cancellation" :value="allowCancellation ? 1 : 0">
                            <span :class="allowCancellation ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="allowCancellation" x-transition>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tiempo de aviso previo requerido (Horas)</label>
                            <input type="number" name="cancellation_notice_hours" value="{{ $settings->cancellation_notice_hours }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-xs text-gray-500">Límite de tiempo antes de la cita para poder cancelar (ej: 24h).</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTÓN DE ENVÍO -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition-colors">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>    

