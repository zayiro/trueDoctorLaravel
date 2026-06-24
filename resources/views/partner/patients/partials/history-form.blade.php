{{-- resources/views/partner/patients/partials/history-form.blade.php --}}

{{-- ── Badge IA (activado por fillForm en show.blade.php) ── --}}
<div class="space-y-6">
<div id="ai-assisted-badge" style="display:none"
     class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Campos prellenados por IA — revisa antes de guardar
</div>
{{-- ── METADATOS (no encriptados) ── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tipo de registro</label>
        <select name="entry_type" required
            class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="consultation" {{ old('entry_type') == 'consultation' ? 'selected' : '' }}>Consulta</option>
            <option value="follow_up"    {{ old('entry_type') == 'follow_up'    ? 'selected' : '' }}>Seguimiento</option>
            <option value="emergency"    {{ old('entry_type') == 'emergency'    ? 'selected' : '' }}>Urgencia</option>
            <option value="note"         {{ old('entry_type') == 'note'         ? 'selected' : '' }}>Nota</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Código CIE-10</label>
        <input type="text" name="cie10_code" id="cie10_code"
            value="{{ old('cie10_code') }}"
            placeholder="Ej: J30.1"
            class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
            <span class="text-gray-400 text-xs">Puede dejarlo vacío si no está seguro, o corregirlo si la IA sugirió uno incorrecto</span>
    </div>
    <div class="flex flex-col items-end">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none pb-2">
            <input type="hidden"   name="ai_assisted" value="0">
            <input type="checkbox" name="ai_assisted" id="ai_assisted_checkbox" value="1"
                {{ old('ai_assisted') ? 'checked' : '' }}
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <div>
                <span class="text-sm text-slate-600">Asistido por IA</span>
                <p class="text-xs text-slate-400">
                    Marcado automáticamente cuando la IA redactó la nota a partir de la grabación.
                </p>
            </div>
        </label>
    </div>
</div>
{{-- ── SOAP ── --}}
<div class="space-y-4">
    <div class="flex items-center gap-3">
        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Historia Clínica — Formato SOAP</span>
        <div class="flex-1 h-px bg-slate-100"></div>
        <span class="text-xs text-slate-400 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Encriptado AES-256
        </span>
    </div>

    {{-- S — Subjetivo --}}
    <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-black flex items-center justify-center flex-shrink-0">S</span>
            <div>
                <p class="text-xs font-semibold text-slate-700">Subjetivo</p>
                <p class="text-xs text-slate-400">Motivo de consulta y síntomas referidos por el paciente</p>
            </div>
        </div>
        <textarea id="soap_subjective" name="soap_subjective" rows="3" required
            placeholder="Paciente refiere..."
            class="block w-full border-0 text-sm text-slate-700 placeholder-slate-300 focus:ring-0 p-4 resize-none">{{ old('soap_subjective') }}</textarea>
    </div>
    {{-- O — Objetivo --}}
    <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-red-600 text-white text-xs font-black flex items-center justify-center flex-shrink-0">O</span>
            <div>
                <p class="text-xs font-semibold text-slate-700">Objetivo</p>
                <p class="text-xs text-slate-400">Signos vitales, examen físico y resultados de laboratorio</p>
            </div>
        </div>
        <textarea id="soap_objective" name="soap_objective" rows="3"
            placeholder="TA: 120/80 mmHg · FC: 72 lpm · FR: 16 rpm · T: 36.5°C..."
            class="block w-full border-0 text-sm text-slate-700 placeholder-slate-300 focus:ring-0 p-4 resize-none">{{ old('soap_objective') }}</textarea>
    </div>

    {{-- A — Análisis --}}
    <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-amber-500 text-white text-xs font-black flex items-center justify-center flex-shrink-0">A</span>
            <div>
                <p class="text-xs font-semibold text-slate-700">Análisis / Diagnóstico</p>
                <p class="text-xs text-slate-400">Impresión diagnóstica e interpretación clínica</p>
            </div>
        </div>
        <textarea id="soap_assessment" name="soap_assessment" rows="3" required
            placeholder="Diagnóstico principal: ..."
            class="block w-full border-0 text-sm text-slate-700 placeholder-slate-300 focus:ring-0 p-4 resize-none">{{ old('soap_assessment') }}</textarea>
    </div>
    {{-- P — Plan --}}
    <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-black flex items-center justify-center flex-shrink-0">P</span>
            <div>
                <p class="text-xs font-semibold text-slate-700">Plan</p>
                <p class="text-xs text-slate-400">Tratamiento, medicamentos, remisiones y fecha de control</p>
            </div>
        </div>
        <textarea id="soap_plan" name="soap_plan" rows="4" required
            placeholder="1. Medicamento — dosis — frecuencia&#10;2. Remisión a...&#10;3. Control en..."
            class="block w-full border-0 text-sm text-slate-700 placeholder-slate-300 focus:ring-0 p-4 resize-none">{{ old('soap_plan') }}</textarea>
    </div>

</div>{{-- fin .space-y-4 SOAP --}}
{{-- ── MEDICAMENTO NUEVO (opcional) ── --}}
<div x-data="{ addMedication: {{ old('medication_name') ? 'true' : 'false' }} }"
     class="border border-dashed border-slate-200 rounded-2xl p-5 bg-slate-50/50">

    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
        <input type="checkbox" x-model="addMedication" id="medication_toggle"
            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm font-medium text-slate-700">Agregar medicamento a la ficha del paciente</span>
    </label>

    <div x-show="addMedication" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Nombre del medicamento</label>
            <input type="text" name="medication_name" id="medication_name"
                value="{{ old('medication_name') }}"
                class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Dosis</label>
            <input type="text" name="medication_dosage" id="medication_dosage"
                placeholder="Ej: 500mg" value="{{ old('medication_dosage') }}"
                class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Frecuencia</label>
            <input type="text" name="medication_frequency" id="medication_frequency"
                placeholder="Ej: Cada 12 horas" value="{{ old('medication_frequency') }}"
                class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Notas</label>
            <input type="text" name="medication_notes" id="medication_notes"
                placeholder="Ej: Tomar después del desayuno" value="{{ old('medication_notes') }}"
                class="block w-full border-slate-200 rounded-xl shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>
</div>

<input type="hidden" name="appointment_id" value="{{ $appointmentId ?? '' }}">

</div>{{-- fin .space-y-6 principal --}}