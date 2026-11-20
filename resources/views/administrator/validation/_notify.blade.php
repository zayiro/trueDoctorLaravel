{{--
    Botón y modal "Avisar al médico".
    Uso:  @include('administrator.validation._notify', ['profile' => $partner])
    $profile = médico o clínica con la relación notice_info cargada por ValidationController@index
--}}
@php
    $info     = $profile->notice_info;
    $isClinic = $profile instanceof \App\Models\Clinic;
    $reasons  = [
        'missing'   => 'No lo hemos recibido',
        'illegible' => 'No se lee con claridad',
        'mismatch'  => 'No corresponde al documento',
        'expired'   => 'Está vencido',
    ];
@endphp

@if (!$info->valid_email)
    <p class="mt-2 text-[11px] font-medium text-slate-400">
        Correo no válido: no se puede enviar el aviso.
    </p>
@else
    <div x-data="{ open: false, sending: false }" class="mt-2">
        @if ($info->locked)
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-bold text-slate-500"
                  title="Podrás volver a avisar el {{ $info->unlock_at->timezone(config('app.timezone', 'America/Bogota'))->format('d/m/Y H:i') }}">
                ✉️ Avisado {{ $info->last_notice->sent_at->locale('es')->diffForHumans() }}
            </span>
        @elseif ($info->has_missing)
            <button type="button" @click="open = true"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-amber-600">
                ✉️ Avisar al {{ $isClinic ? 'centro médico' : 'médico' }}
            </button>
        @else
            <button type="button" @click="open = true"
                    class="text-[11px] font-bold text-slate-400 underline decoration-dotted underline-offset-2 hover:text-slate-600">
                Reportar problema con un documento
            </button>
        @endif

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="fixed inset-0 bg-slate-900/40" @click="open = false"></div>

                <div class="relative z-10 w-full max-w-lg rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl md:p-8" @click.stop>
                    <div class="mb-4 border-b border-slate-100 pb-3">
                        <h3 class="text-lg font-black text-slate-800">Avisar para que corrija sus documentos</h3>
                        <p class="mt-0.5 text-[11px] font-bold text-slate-400">
                            {{ $profile->user->name }} · {{ $profile->user->email }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('administrator.validation.notify') }}" @submit="sending = true" class="space-y-4">
                        @csrf
                        <input type="hidden" name="{{ $isClinic ? 'clinic_id' : 'doctor_id' }}" value="{{ $profile->id }}">

                        <p class="text-xs font-medium leading-relaxed text-slate-500">
                            Marca los documentos que debe corregir y el motivo. Le llegará un correo con el detalle.
                        </p>

                        <div class="space-y-3">
                            @foreach ($info->docs as $i => $doc)
                                <div x-data="{ on: {{ $doc['missing'] ? 'true' : 'false' }} }"
                                     class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input type="checkbox" x-model="on" name="items[{{ $i }}][doc]" value="{{ $doc['key'] }}"
                                               class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                        <span class="text-sm font-bold text-slate-700">{{ $doc['label'] }}</span>
                                        @if ($doc['missing'])
                                            <span class="rounded-full border border-red-100 bg-red-50 px-2 py-0.5 text-[10px] font-black uppercase text-red-600">Falta</span>
                                        @else
                                            <span class="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-600">Cargado</span>
                                        @endif
                                    </label>

                                    <select name="items[{{ $i }}][reason]" :disabled="!on"
                                            class="mt-2 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 disabled:opacity-40">
                                        @foreach ($reasons as $key => $label)
                                            <option value="{{ $key }}" @selected($doc['missing'] ? $key === 'missing' : $key === 'illegible')>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Nota para el {{ $isClinic ? 'centro médico' : 'médico' }} (opcional)</label>
                            <textarea name="note" rows="3" maxlength="500" placeholder="Ej: La foto de la cédula salió borrosa, sube una más nítida."
                                      class="w-full rounded-2xl border-slate-200 p-3 text-sm text-slate-800 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <button type="button" @click="open = false"
                                    class="rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:bg-slate-50">
                                Cerrar
                            </button>
                            <button type="submit" :disabled="sending"
                                    class="rounded-xl bg-amber-500 px-6 py-3 text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-amber-600 disabled:opacity-50">
                                Enviar aviso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endif
