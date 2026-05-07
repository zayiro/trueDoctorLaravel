<!-- resources/views/partner/schedules/form.blade.php -->
<div class="max-w-4xl mx-auto p-6 bg-white shadow-lg rounded-xl">
    <div class="mb-6 flex justify-between items-center border-b pb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Horarios de Atención</h2>
            <p class="text-sm text-gray-500">Configura cuándo atiendes en: <strong>{{ $address->name }}</strong></p>
        </div>
        <button type="button" onclick="copyMonday()" class="text-sm bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full border border-indigo-200 hover:bg-indigo-100 transition">
            Copiar horario del lunes a todos
        </button>
    </div>

    <form action="{{ route('partner.schedules.store') }}" method="POST">
        @csrf
        <input type="hidden" name="address_id" value="{{ $address->id }}">

        <div class="space-y-3">
            @php
                $days = [
                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
                ];
            @endphp

            @foreach($days as $index => $day)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg group hover:bg-gray-100 transition">
                <div class="flex items-center gap-4 w-1/3">
                    <!-- Checkbox de activación -->
                    <input type="checkbox" name="active_days[]" value="{{ $index }}" 
                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 day-checkbox"
                           id="check-{{ $index }}">
                    <label for="check-{{ $index }}" class="font-semibold text-gray-700 cursor-pointer">{{ $day }}</label>
                </div>

                <div class="flex items-center gap-3 w-2/3 justify-end">
                    <div class="relative">
                        <input type="time" name="start[{{ $index }}]" id="start-{{ $index }}"
                               class="block w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm time-input"
                               value="08:00">
                    </div>
                    <span class="text-gray-400">—</span>
                    <div class="relative">
                        <input type="time" name="end[{{ $index }}]" id="end-{{ $index }}"
                               class="block w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm time-input"
                               value="17:00">
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold shadow-indigo-200 shadow-lg hover:bg-indigo-700 transition">
                Guardar Horarios Semanales
            </button>
        </div>
    </form>
</div>

<script>
    function copyMonday() {
        const mondayStart = document.getElementById('start-1').value;
        const mondayEnd = document.getElementById('end-1').value;
        const isMondayActive = document.getElementById('check-1').checked;

        for (let i = 2; i <= 7; i++) {
            document.getElementById('start-' + i).value = mondayStart;
            document.getElementById('end-' + i).value = mondayEnd;
            document.getElementById('check-' + i).checked = isMondayActive;
        }
    }
</script>
