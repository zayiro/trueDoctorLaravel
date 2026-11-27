{{-- Ventana para emitir la incapacidad médica (se incluye desde el panel de certificados, que ya define los estilos .cr-*).
     Variables: $appointment, $crDoctor, $crIncap, $crPatientName, $crWhen, $crTz, $crHistoryCie. REVISAR CON ABOGADO: los textos. --}}
@if (!empty($crIncap['ok']))
@php
    $icMax = (int) config('incapacity.max_days', 30);
    $icRetroMax = (int) config('incapacity.retroactive_max_days', 30);
    $icFutureMax = (int) config('incapacity.future_start_max_days', 2);
    $icReasonMin = (int) config('incapacity.retroactive_reason_min', 20);
    $icConsult = ($crWhen ?? null) ? $crWhen->format('Y-m-d') : now($crTz ?? 'America/Bogota')->format('Y-m-d');
    $icMin = \Illuminate\Support\Carbon::parse($icConsult)->subDays($icRetroMax)->format('Y-m-d');
    $icMaxDate = \Illuminate\Support\Carbon::parse($icConsult)->addDays($icFutureMax)->format('Y-m-d');
    $icPrevious = collect();
    try {
        $icPrevious = \App\Models\IncapacityDetail::query()
            ->with('certificate')
            ->whereHas('certificate', fn ($q) => $q->where('type', 'incapacity')->where('patient_id', $appointment->patient_id)->where('doctor_id', $crDoctor?->id)->active())
            ->orderByDesc('end_date')
            ->limit(10)
            ->get();
    } catch (\Throwable $icE) {
        report($icE);
    }
@endphp
<style>
    .ic-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ic-form select, .ic-form input[type=date], .ic-form input[type=number] { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 14px; padding: 10px 12px; font-size: 14px; color: #1e293b; font-family: inherit; background: #ffffff; }
    .ic-form select:focus, .ic-form input[type=date]:focus, .ic-form input[type=number]:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
    .ic-form { max-height: 86vh; overflow-y: auto; }
    .ic-box { display: none; border: 1px dashed #fde68a; background: #fffbeb; border-radius: 14px; padding: 12px; flex-direction: column; gap: 8px; }
</style>
<dialog id="cr-dlg-incapacity" class="cr-dlg" onclick="if (event.target === this) { this.close(); }">
    <form method="POST" action="{{ route('partner.appointments.clinical-record.incapacities.store', ['appointment' => $appointment->id]) }}" class="cr-form ic-form" onsubmit="crLock(this)">
        @csrf
        <input type="hidden" name="cr_form" value="incapacity">
        <h3>Emitir incapacidad médica</h3>
        <p>Paciente: <strong>{{ $crPatientName }}</strong>{{ ($crWhen ?? null) ? ' · Consulta del ' . $crWhen->format('d/m/Y') . ' a las ' . $crWhen->format('H:i') : '' }}</p>
        @if (old('cr_form') === 'incapacity')
            @if ($errors->any())
                <div class="cr-err">@foreach ($errors->all() as $icErr)<div>{{ $icErr }}</div>@endforeach</div>
            @elseif (session('error'))
                <div class="cr-err">{{ session('error') }}</div>
            @endif
        @endif

        <label class="cr-lab" for="ic-origin">Origen *</label>
        <select name="origin" id="ic-origin" required>
            <option value="">Selecciona…</option>
            @foreach ((array) config('incapacity.origins', []) as $icKey => $icLabel)
                <option value="{{ $icKey }}" @selected(old('origin') === $icKey)>{{ $icLabel }}</option>
            @endforeach
        </select>

        <div class="ic-row">
            <div>
                <label class="cr-lab" for="ic-start">Desde *</label>
                <input type="date" name="start_date" id="ic-start" required min="{{ $icMin }}" max="{{ $icMaxDate }}" value="{{ old('start_date', $icConsult) }}" data-consult="{{ $icConsult }}">
            </div>
            <div>
                <label class="cr-lab" for="ic-days">Días (máx. {{ $icMax }}) *</label>
                <input type="number" name="days" id="ic-days" required min="1" max="{{ $icMax }}" value="{{ old('days', 1) }}">
            </div>
        </div>
        <div class="cr-ok" id="ic-preview" style="display:none;"></div>

        <div class="ic-box" id="ic-retro" style="{{ old('retroactive_reason') ? 'display:flex' : '' }}">
            <label class="cr-lab" for="ic-reason">Justificación de la incapacidad retroactiva * (mín. {{ $icReasonMin }} caracteres)</label>
            <textarea id="ic-reason" name="retroactive_reason" rows="3" maxlength="500" placeholder="Explica por qué la incapacidad empieza antes de la fecha de esta consulta.">{{ old('retroactive_reason') }}</textarea>
            <p style="font-size:11px;color:#92400e;">La fecha de inicio es anterior a la de la consulta: el documento saldrá marcado como retroactivo, con esta justificación.</p>
        </div>

        @if ($icPrevious->isNotEmpty())
            <label class="cr-check"><input type="checkbox" name="is_extension" value="1" id="ic-ext" {{ old('is_extension') ? 'checked' : '' }}>
                <span>Es una prórroga de una incapacidad anterior de este paciente.</span></label>
            <div class="ic-box" id="ic-prev-box" style="{{ old('is_extension') ? 'display:flex' : '' }}">
                <label class="cr-lab" for="ic-prev">Incapacidad que se prorroga *</label>
                <select name="previous_certificate_id" id="ic-prev">
                    <option value="">Selecciona…</option>
                    @foreach ($icPrevious as $icPrev)
                        <option value="{{ $icPrev->certificate_id }}" data-next="{{ $icPrev->end_date->copy()->addDay()->format('Y-m-d') }}" @selected((string) old('previous_certificate_id') === (string) $icPrev->certificate_id)>{{ $icPrev->certificate?->code }} · hasta {{ $icPrev->end_date->format('d/m/Y') }}</option>
                    @endforeach
                </select>
                <p style="font-size:11px;color:#475569;">Al elegirla, la fecha de inicio se ajusta al día siguiente al fin de esa incapacidad.</p>
            </div>
        @endif

        <label class="cr-lab" for="cie10_code">Diagnóstico (código CIE-10) *</label>
        <x-cie10-autocomplete :value="old('cie10_code', $crHistoryCie)" />
        <div class="cr-warn">El PDF llevará solo el <strong>código</strong> CIE-10 (por ejemplo J00), no la descripción del diagnóstico. Elige el diagnóstico de la lista o escribe el código.</div>

        <label class="cr-check"><input type="checkbox" name="declaration" value="1" required><span>{{ config('certificates.texts.declaration') }}</span></label>
        <div class="cr-form-actions">
            <button type="button" class="cr-btn-ghost" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="cr-btn">Emitir incapacidad</button>
        </div>
    </form>
</dialog>
<script>
    (function () {
        var start = document.getElementById('ic-start');
        var days = document.getElementById('ic-days');
        var preview = document.getElementById('ic-preview');
        var retro = document.getElementById('ic-retro');
        var reason = document.getElementById('ic-reason');
        var ext = document.getElementById('ic-ext');
        var prevBox = document.getElementById('ic-prev-box');
        var prev = document.getElementById('ic-prev');
        if (!start || !days) { return; }

        function fmt(d) { return ('0' + d.getUTCDate()).slice(-2) + '/' + ('0' + (d.getUTCMonth() + 1)).slice(-2) + '/' + d.getUTCFullYear(); }

        function recalc() {
            var s = start.value;
            var n = parseInt(days.value, 10);
            var isRetro = s && s < start.getAttribute('data-consult');
            if (retro) { retro.style.display = isRetro ? 'flex' : 'none'; }
            if (reason) { reason.required = !!isRetro; }
            if (s && n > 0) {
                var from = new Date(s + 'T00:00:00Z');
                var to = new Date(from.getTime() + (n - 1) * 86400000);
                preview.style.display = 'block';
                preview.textContent = 'Del ' + fmt(from) + ' al ' + fmt(to) + ' (' + n + (n === 1 ? ' día' : ' días') + ' calendario)' + (isRetro ? ' · retroactiva' : '');
            } else {
                preview.style.display = 'none';
            }
        }

        start.addEventListener('input', recalc);
        days.addEventListener('input', recalc);

        if (ext && prevBox && prev) {
            ext.addEventListener('change', function () {
                prevBox.style.display = ext.checked ? 'flex' : 'none';
                prev.required = ext.checked;
            });
            prev.addEventListener('change', function () {
                var o = prev.options[prev.selectedIndex];
                var next = o ? o.getAttribute('data-next') : null;
                if (next) { start.value = next; recalc(); }
            });
        }

        recalc();
    })();
</script>
@if (old('cr_form') === 'incapacity')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var d = document.getElementById('cr-dlg-incapacity');
        if (d && d.showModal) { d.showModal(); }
    });
</script>
@endif
@endif
