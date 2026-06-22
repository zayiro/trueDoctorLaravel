<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700">Tipo de registro</label>
        <select name="entry_type" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="consultation" {{ old('entry_type') == 'consultation' ? 'selected' : '' }}>Consulta</option>
            <option value="follow_up" {{ old('entry_type') == 'follow_up' ? 'selected' : '' }}>Seguimiento</option>
            <option value="emergency" {{ old('entry_type') == 'emergency' ? 'selected' : '' }}>Urgencia</option>
            <option value="note" {{ old('entry_type') == 'note' ? 'selected' : '' }}>Nota</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Motivo de la consulta</label>
        <input type="text" name="reason_for_consultation" required value="{{ old('reason_for_consultation') }}"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Síntomas</label>
        <textarea name="symptoms" rows="3"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('symptoms') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Diagnóstico</label>
        <textarea name="diagnosis" rows="3" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('diagnosis') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Plan de Tratamiento / Medicación</label>
        <textarea name="treatment_plan" rows="4" placeholder="Escribe aquí las indicaciones o medicamentos..."
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('treatment_plan') }}</textarea>
    </div>

    {{-- Medicamento nuevo (opcional) -> inserta en patient_medications, tabla aparte --}}
    <div class="border border-dashed border-gray-300 rounded-xl p-4 bg-gray-50/50" x-data="{ addMedication: {{ old('medication_name') ? 'true' : 'false' }} }">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" x-model="addMedication" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Agregar medicamento nuevo a la ficha del paciente</span>
        </label>

        <div x-show="addMedication" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Nombre del medicamento</label>
                <input type="text" name="medication_name" value="{{ old('medication_name') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Dosis</label>
                <input type="text" name="medication_dosage" placeholder="Ej: 500mg" value="{{ old('medication_dosage') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Frecuencia</label>
                <input type="text" name="medication_frequency" placeholder="Ej: Cada 12 horas" value="{{ old('medication_frequency') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Notas</label>
                <input type="text" name="medication_notes" placeholder="Ej: Tomar después del desayuno" value="{{ old('medication_notes') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

</div>
<input type="hidden" name="appointment_id" value="{{ $appointmentId }}">