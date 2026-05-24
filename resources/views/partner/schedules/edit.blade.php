@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Consultorios',
        'href' => route('partner.addresses.index'),
    ],
    [
        'name' => 'Editar Horarios en Lote',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">     
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="bg-white p-8 shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <h2 class="text-2xl font-black text-slate-800 mb-1">Modificar Horarios en Lote</h2>
            
            <!-- 🔥 CORREGIDO: Ajustado a las propiedades reales de tu migración addresses -->
            <p class="text-sm text-slate-500 mb-8 font-medium">
                {{ $address->name }} — {{ $address->address }}{{ $address->type === 'virtual' ? ' (Canal Digital)' : ', ' . ($address->city->name ?? '') }}
            </p>

            <form action="{{ route('partner.schedules.update', $address) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Lista de errores global -->
                <x-validation-errors class="mb-4" />

                <div class="divide-y divide-slate-100 border-t border-b border-slate-100">
                    @foreach($schedules as $schedule)
                        <div class="py-5 flex flex-wrap items-center justify-between gap-4">
                            <!-- Día de la semana -->
                            <div class="w-32">
                                <span class="font-bold text-slate-800 text-sm block capitalize">
                                    {{ $schedule->day_name }}
                                </span>
                                
                                <!-- 🔥 EXCLUSIVO CLÍNICAS: Muestra el especialista dueño del turno -->
                                @if(auth()->user()->role === 'clinic')
                                    <span class="text-[10px] bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md font-medium mt-1 inline-block border border-indigo-100/50">
                                        {{ $schedule->doctor->user->name ?? 'Sin asignar' }}
                                    </span>
                                @endif
                            </div>

                            <!-- Inputs de Hora de Inicio y Fin -->
                            <div class="flex items-center space-x-2">
                                <input type="time" 
                                       name="schedules[{{ $schedule->id }}][start_time]" 
                                       value="{{ old('schedules.'.$schedule->id.'.start_time', $schedule->start_time->format('H:i')) }}"
                                       class="rounded-xl border-gray-300 py-2 text-sm focus:ring-indigo-500 shadow-sm" required />
                                
                                <span class="text-slate-400 text-xs font-semibold lowercase">a</span>
                                
                                <input type="time" 
                                       name="schedules[{{ $schedule->id }}][end_time]" 
                                       value="{{ old('schedules.'.$schedule->id.'.end_time', $schedule->end_time->format('H:i')) }}"
                                       class="rounded-xl border-gray-300 py-2 text-sm focus:ring-indigo-500 shadow-sm" required />
                            </div>

                            <!-- Interruptor de Estado (Habilitado/Deshabilitado) -->
                            <label class="inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" 
                                       name="schedules[{{ $schedule->id }}][is_active]" 
                                       value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4"
                                       {{ old('schedules.'.$schedule->id.'.is_active', $schedule->is_active ?? true) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-slate-600 font-bold">Habilitado</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <!-- Botones Inferiores de Guardado y Cancelación -->
                <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-50 pt-6">
                    <a href="{{ route('partner.schedules.index', $address->id) }}" 
                        class="px-6 py-3 border border-gray-300 rounded-2xl text-gray-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 text-xs font-bold uppercase tracking-wider">
                        Cancelar
                    </a> 
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-6 rounded-2xl shadow-lg shadow-indigo-100 transition-all uppercase tracking-wider text-xs">
                        Guardar Cambios en Lote
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
