<!-- resources/views/partner/schedules/form.blade.php -->
<div class="max-w-4xl mx-auto p-8 bg-white shadow-xl rounded-3xl border border-gray-100">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Horarios de Atención Semanal</h2>
            <p class="text-sm text-slate-500 mt-0.5">Configura la franja de disponibilidad en: <strong class="text-indigo-600">{{ $address->name }}</strong></p>
        </div>
        <button type="button" onclick="copyMonday()" class="text-xs bg-indigo-50 text-indigo-600 px-4 py-2 rounded-full border border-indigo-200 hover:bg-indigo-100 transition font-bold uppercase tracking-wider">
            Copiar horario del lunes a todos
        </button>
    </div>

    <!-- El formulario apunta a la persistencia de horarios en lote -->
    <form action="{{ route('partner.schedules.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="address_id" value="{{ $address->id }}">

        <!-- SELECTOR DE DOCTORES EXCLUSIVO PARA CUENTAS DE CLÍNICA -->
        @if(auth()->user()->role === 'clinic')
            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 mb-6">
                <label for="doctor_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Profesional de Salud Asignado a la Jornada</label>
                <select name="doctor_id" id="doctor_id" class="w-full md:w-1/2 rounded-xl border-slate-200 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">Selecciona un médico de la nómina...</option>
                    @foreach($availableDoctors as $doc)
                        <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>
                            {{ $doc->user->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1.5">Los bloques marcados se cargarun a la agenda de este especialista dentro de esta sede.</p>
            </div>
        @endif
        <!-- Bloques de Días Semanales -->
        <div class="space-y-3">
            @php
                // Sincronizado: 1 = Lunes, 0 = Domingo para emparejar con el backend
                $days = [
                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'
                ];
            @endphp

            @foreach($days as $index => $day)
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-slate-50 rounded-2xl group hover:bg-slate-100/70 border border-slate-100/50 transition gap-4">
                <div class="flex items-center gap-4 w-full sm:w-1/3">
                    <!-- Checkbox de activación -->
                    <input type="checkbox" name="active_days[]" value="{{ $index }}" 
                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 day-checkbox"
                           id="check-{{ $index }}">
                    <label for="check-{{ $index }}" class="font-bold text-slate-700 cursor-pointer text-sm select-none">{{ $day }}</label>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-2/3 justify-start sm:justify-end">
                    <div class="relative">
                        <input type="time" name="start_time[{{ $index }}]" id="start-{{ $index }}"
                               class="block w-32 border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm time-input py-2 font-bold text-slate-700"
                               value="08:00">
                    </div>
                    <span class="text-slate-400 font-semibold">—</span>
                    <div class="relative">
                        <input type="time" name="end_time[{{ $index }}]" id="end-{{ $index }}"
                               class="block w-32 border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm time-input py-2 font-bold text-slate-700"
                               value="17:00">
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Botones de Acción -->
        <div class="mt-8 flex justify-end gap-3 border-t border-slate-50 pt-6">
            <a href="{{ route('partner.schedules.index', $address->id) }}" 
                class="px-6 py-3 border border-gray-300 rounded-2xl text-slate-500 bg-white hover:bg-slate-50 transition duration-200 text-xs font-bold uppercase tracking-wider">
                Cancelar
            </a> 
            <button type="submit" class="bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black shadow-indigo-100 shadow-lg hover:bg-indigo-700 transition-all uppercase tracking-wider text-xs">
                Guardar Horarios Semanales
            </button>
        </div>
    </form>
</div>

<!-- ======================================================== -->
<!-- AUTOMATIZACIÓN DE COPIADO HORARIO DE LUNES -->
<!-- ======================================================== -->
<script>
    function copyMonday() {
        const mondayStart = document.getElementById('start-1').value;
        const mondayEnd = document.getElementById('end-1').value;
        const isMondayActive = document.getElementById('check-1').checked;

        // Lista de los índices de los días restantes
        const targetDays = [2, 3, 4, 5, 6, 0];

        targetDays.forEach(i => {
            const startInput = document.getElementById('start-' + i);
            const endInput = document.getElementById('end-' + i);
            const checkInput = document.getElementById('check-' + i);

            if (startInput) startInput.value = mondayStart;
            if (endInput) endInput.value = mondayEnd;
            if (checkInput) checkInput.checked = isMondayActive;
        });
    }
</script>
