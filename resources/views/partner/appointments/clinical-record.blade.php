@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agenda Médica', 'href' => route('partner.appointments.index')],
    ['name' => 'Historia Clínica • ' . $appointment->reference],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

        {{-- Alertas de sesión y errores --}}
        @if (session('success'))
            <div class="p-4 mb-6 text-sm font-bold text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-sm">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-6 text-sm font-bold text-rose-800 rounded-2xl bg-rose-50 border border-rose-200 shadow-sm">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-6 text-sm text-rose-800 rounded-2xl bg-rose-50 border border-rose-200">
                <p class="font-bold mb-1">Revisa los siguientes campos:</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Cabecera de la Consulta --}}
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="text-xs font-black text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-lg font-mono">
                        Ref: {{ $appointment->reference }}
                    </span>
                    <span class="text-xs font-bold text-slate-500">
                        {{ $appointment->starts_at->format('d/m/Y • g:i A') }}
                    </span>
                </div>
                <h1 class="text-2xl font-black text-slate-900">
                    {{ $appointment->patient->user->name ?? 'Paciente' }}
                </h1>
                <p class="text-sm text-slate-500 font-medium">
                    Servicio: <span class="text-slate-700 font-bold">{{ $appointment->service->name ?? 'Consulta Médica' }}</span>
                </p>
            </div>

            <div class="flex flex-col items-start md:items-end gap-2">
                @if($isWithinSoapWindow)
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                        🟢 Ventana SOAP activa hasta {{ $appointment->soap_deadline->format('g:i A') }}
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                        🔒 Ventana SOAP cerrada • Modo Notas Extemporáneas ({{ $notesCount }}/{{ $maxNotes }})
                    </span>
                @endif

                <a href="{{ route('partner.appointments.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline">
                    ← Volver a la agenda
                </a>
            </div>
        </div>

        {{-- Grid Principal: Columna Izquierda (Contexto del Paciente) + Columna Derecha (Formulario SOAP o Notas) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- COLUMNA IZQUIERDA (4 cols): Perfil de Salud Reportado por el Paciente (Solo Lectura) --}}
            <div class="lg:col-span-4 space-y-5">
                
                <!-- Ficha Básica -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm space-y-2.5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400">Datos del Paciente</h2>
                    <div class="text-sm text-slate-700 space-y-1">
                        <p><strong class="text-slate-900">Identificación:</strong> {{ $appointment->patient->identification ?? 'N/R' }}</p>
                        <p><strong class="text-slate-900">Grupo Sanguíneo:</strong> {{ $appointment->patient->blood_type ?? 'N/R' }}</p>
                        <p><strong class="text-slate-900">Teléfono:</strong> {{ $appointment->patient->phone ?? 'N/R' }}</p>
                    </div>
                </div>

                <!-- Alergias Reportadas -->
                <div class="bg-rose-50/50 p-5 rounded-3xl border border-rose-200/80 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-wider text-rose-800">⚠️ Alergias Reportadas</h3>
                        <span class="text-[10px] font-bold text-rose-600 bg-white px-2 py-0.5 rounded-full border border-rose-200">Info paciente</span>
                    </div>
                    @forelse($appointment->patient->allergies as $allergy)
                        <div class="bg-white p-2.5 rounded-xl border border-rose-100 text-xs">
                            <p class="font-bold text-slate-900">{{ $allergy->name }}</p>
                            <p class="text-slate-500">Severidad: {{ ucfirst($allergy->severity) }} {{ $allergy->reaction ? '• ' . $allergy->reaction : '' }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">Sin alergias reportadas en su perfil.</p>
                    @endforelse
                </div>

                <!-- Medicamentos Actuales -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm space-y-2.5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-700">💊 Medicamentos Reportados</h3>
                    @forelse($appointment->patient->medications as $med)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <p class="font-bold text-slate-900">{{ $med->name }}</p>
                            <p class="text-slate-500">{{ $med->dosage }} {{ $med->frequency ? '• ' . $med->frequency : '' }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">Sin medicamentos reportados.</p>
                    @endforelse
                </div>

                <!-- Cirugías y Antecedentes Familiares -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm space-y-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">🏥 Cirugías Previas</h3>
                        @forelse($appointment->patient->surgeries as $surgery)
                            <p class="text-xs text-slate-700">• <strong>{{ $surgery->name }}</strong> ({{ $surgery->year ?? 'Año N/R' }})</p>
                        @empty
                            <p class="text-xs text-slate-500">Ninguna reportada.</p>
                        @endforelse
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">🧬 Antecedentes Familiares</h3>
                        @forelse($appointment->patient->familyHistories as $fam)
                            <p class="text-xs text-slate-700">• <strong>{{ $fam->condition }}</strong> ({{ $fam->relationship }})</p>
                        @empty
                            <p class="text-xs text-slate-500">Ninguno reportado.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Adjuntos PDF / Exámenes del Paciente -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm space-y-2.5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-700">📎 Exámenes y Adjuntos</h3>
                    @forelse($appointment->patient->attachments as $att)
                        <a href="{{ route('patient.attachments.view', $att) }}" target="_blank"
                           class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-200/70 text-xs transition">
                            <span class="font-bold text-slate-800 truncate pr-2">📄 {{ $att->name ?? 'Archivo adjunto' }}</span>
                            <span class="text-indigo-600 font-black shrink-0">Abrir ↗</span>
                        </a>
                    @empty
                        <p class="text-xs text-slate-500">No hay archivos adjuntos cargados.</p>
                    @endforelse
                </div>

            </div>
            {{-- COLUMNA DERECHA (8 cols): Formulario SOAP (0-3h) o Modo Notas Extemporáneas (>3h / Firmada) --}}
            <div class="lg:col-span-8">

                @if($isWithinSoapWindow)
                    <!-- MODO 1: FORMULARIO SOAP COMPLETO (DENTRO DE LAS 3 HORAS) -->
                    <form action="{{ route('partner.appointments.clinical-record.soap.store', $appointment->id) }}" 
                          method="POST" 
                          class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                        @csrf

                        <div class="border-b border-slate-100 pb-4">
                            <h2 class="text-lg font-black text-slate-900">Evolución Clínica Oficial (SOAP)</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Puedes guardar borradores mientras completas la atención. Una vez firmada, la historia clínica quedará bloqueada por normativa legal.
                            </p>
                        </div>

                        <!-- Tipo de Entrada y Código CIE-10 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Tipo de Atención</label>
                                <select name="entry_type" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="consultation" {{ old('entry_type', $history?->entry_type) === 'consultation' ? 'selected' : '' }}>Consulta Médica</option>
                                    <option value="follow_up" {{ old('entry_type', $history?->entry_type) === 'follow_up' ? 'selected' : '' }}>Control / Seguimiento</option>
                                    <option value="emergency" {{ old('entry_type', $history?->entry_type) === 'emergency' ? 'selected' : '' }}>Atención Prioritaria</option>
                                    <option value="note" {{ old('entry_type', $history?->entry_type) === 'note' ? 'selected' : '' }}>Nota Clínica</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Diagnóstico Principal (CIE-10)</label>
                                <input type="text" name="cie10_code" value="{{ old('cie10_code', $history?->cie10_code) }}" 
                                       placeholder="Ej: J00 - Rinofaringitis aguda"
                                       class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- S: Subjetivo -->
                        <div>
                            <label class="flex items-center gap-2 text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
                                <span class="w-5 h-5 rounded bg-blue-100 text-blue-700 flex items-center justify-center">S</span>
                                Subjetivo (Motivo de consulta, enfermedad actual, antecedentes confirmados) *
                            </label>
                            <textarea name="soap_subjective" rows="4" required
                                      placeholder="Describe los síntomas referidos por el paciente, tiempo de evolución y antecedentes relevantes confirmados..."
                                      class="w-full rounded-2xl border-slate-200 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">{{ old('soap_subjective', $history?->soap_subjective) }}</textarea>
                        </div>

                        <!-- O: Objetivo -->
                        <div>
                            <label class="flex items-center gap-2 text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
                                <span class="w-5 h-5 rounded bg-indigo-100 text-indigo-700 flex items-center justify-center">O</span>
                                Objetivo (Examen físico, signos vitales, hallazgos en paraclínicos)
                            </label>
                            <textarea name="soap_objective" rows="3"
                                      placeholder="Hallazgos clínicos observados durante la consulta o resultados de exámenes revisados..."
                                      class="w-full rounded-2xl border-slate-200 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">{{ old('soap_objective', $history?->soap_objective) }}</textarea>
                        </div>

                        <!-- A: Análisis -->
                        <div>
                            <label class="flex items-center gap-2 text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
                                <span class="w-5 h-5 rounded bg-amber-100 text-amber-700 flex items-center justify-center">A</span>
                                Análisis y Criterio Diagnóstico *
                            </label>
                            <textarea name="soap_assessment" rows="3" required
                                      placeholder="Impresión diagnóstica, análisis clínico y correlación de síntomas..."
                                      class="w-full rounded-2xl border-slate-200 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">{{ old('soap_assessment', $history?->soap_assessment) }}</textarea>
                        </div>

                        <!-- P: Plan -->
                        <div>
                            <label class="flex items-center gap-2 text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
                                <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">P</span>
                                Plan de Manejo, Formulación y Recomendaciones *
                            </label>
                            <textarea name="soap_plan" rows="4" required
                                      placeholder="Tratamiento farmacológico, órdenes médicas, signos de alarma y recomendaciones..."
                                      class="w-full rounded-2xl border-slate-200 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">{{ old('soap_plan', $history?->soap_plan) }}</textarea>
                        </div>

                        <!-- Botones de Acción (Guardar Borrador vs Firmar Definitivamente) -->
                        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-3">
                            <button type="submit" name="action" value="draft"
                                    class="w-full sm:w-auto px-5 py-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-black uppercase tracking-wider transition">
                                💾 Guardar Borrador
                            </button>

                            <button type="submit" name="action" value="sign"
                                    onclick="return confirm('¿Estás seguro de firmar y cerrar esta historia clínica? Una vez firmada, el registro será inmutable y no podrá editarse.');"
                                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wider shadow-md transition">
                                ✍️ Firmar y Cerrar Historia
                            </button>
                        </div>
                    </form>
                @else
                    <!-- MODO 2: VENTANA CERRADA (> 3 HORAS) O HISTORIA FIRMADA -> SOLO NOTAS EXTEMPORÁNEAS (MÁXIMO 3) -->
                    <div class="space-y-6">

                        <!-- Formulario para agregar una nueva Nota Extra (si aún no llega a 3) -->
                        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-amber-200 shadow-sm space-y-5">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                                <div>
                                    <h2 class="text-lg font-black text-slate-900">Agregar Nota Extra / Extemporánea</h2>
                                    <p class="text-xs text-slate-500 mt-1">
                                        El formulario SOAP principal se encuentra cerrado. Puedes anexar hasta <strong>{{ $maxNotes }} notas individuales</strong> con registro cronológico oficial.
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-amber-900 shrink-0">
                                    {{ $notesCount }} / {{ $maxNotes }} usadas
                                </span>
                            </div>

                            @if($notesCount < $maxNotes)
                                <form action="{{ route('partner.appointments.clinical-record.notes.store', $appointment->id) }}" 
                                      method="POST" 
                                      class="space-y-4">
                                    @csrf

                                    <div>
                                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                                            Contenido de la Nota Extra (#{{ $notesCount + 1 }} de {{ $maxNotes }}) *
                                        </label>
                                        <textarea name="content" rows="5" required
                                                  placeholder="Redacta aquí la evolución clínica, aclaración médica o seguimiento posterior a la cita..."
                                                  class="w-full rounded-2xl border-slate-200 text-sm text-slate-800 focus:border-amber-500 focus:ring-amber-500">{{ old('content') }}</textarea>
                                        <p class="text-[11px] text-slate-400 mt-1">
                                            Esta nota quedará guardada con la fecha y hora actual ({{ now('America/Bogota')->format('d/m/Y g:i A') }}) y no podrá ser modificada ni eliminada.
                                        </p>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                                onclick="return confirm('¿Confirmas guardar esta nota extra? Una vez registrada no podrá editarse ni eliminarse.');"
                                                class="px-6 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black uppercase tracking-wider shadow-sm transition">
                                            📌 Guardar Nota Extra ({{ $notesCount + 1 }}/{{ $maxNotes }})
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                                    <p class="text-sm font-bold text-slate-700">
                                        🔒 Has alcanzado el límite máximo de {{ $maxNotes }} notas extemporáneas para esta cita.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Listado Cronológico de Notas Extemporáneas ya registradas -->
                        @if($history && $history->notes->isNotEmpty())
                            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">
                                    Notas Extras Registradas en esta Cita ({{ $notesCount }}/{{ $maxNotes }})
                                </h3>
                                <div class="space-y-3">
                                    @foreach($history->notes as $idx => $note)
                                        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80">
                                            <div class="flex items-center justify-between text-xs font-bold text-amber-900 mb-1.5">
                                                <span>Nota #{{ $idx + 1 }} • Dr(a). {{ $note->doctor->user->name ?? 'Médico' }}</span>
                                                <span>{{ $note->created_at->format('d/m/Y g:i A') }}</span>
                                            </div>
                                            <p class="text-sm text-slate-800 whitespace-pre-line leading-relaxed">{{ $note->content }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Vista de Solo Lectura del Registro SOAP Principal (si existe) -->
                        @if($history)
                            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">
                                        Registro SOAP Principal (Solo Lectura)
                                    </h3>
                                    @if($history->cie10_code)
                                        <span class="text-xs font-black text-slate-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                                            CIE-10: {{ $history->cie10_code }}
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80">
                                        <span class="text-[11px] font-black text-blue-600 uppercase block mb-1">S • Subjetivo</span>
                                        <p class="text-slate-700 whitespace-pre-line">{{ $history->soap_subjective ?: 'Sin registros.' }}</p>
                                    </div>
                                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80">
                                        <span class="text-[11px] font-black text-indigo-600 uppercase block mb-1">O • Objetivo</span>
                                        <p class="text-slate-700 whitespace-pre-line">{{ $history->soap_objective ?: 'Sin registros.' }}</p>
                                    </div>
                                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80">
                                        <span class="text-[11px] font-black text-amber-600 uppercase block mb-1">A • Análisis</span>
                                        <p class="text-slate-700 whitespace-pre-line">{{ $history->soap_assessment ?: 'Sin registros.' }}</p>
                                    </div>
                                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80">
                                        <span class="text-[11px] font-black text-emerald-600 uppercase block mb-1">P • Plan</span>
                                        <p class="text-slate-700 whitespace-pre-line">{{ $history->soap_plan ?: 'Sin registros.' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

            </div>
        </div>
    </div>
</x-admin-layout>
