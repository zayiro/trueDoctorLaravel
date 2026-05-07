@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('partner.addresses.index'),
    ],
    [
        'name' => 'Editar Horario',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">     
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="bg-white p-6 shadow rounded-lg">
            <h2 class="text-xl font-bold mb-2">Editar Horarios</h2>
            <p class="text-gray-600 mb-6 font-medium">{{ $address->address_line_1 }} - {{ $address->city }}</p>

            <form action="{{ route('schedules.update', $address) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="divide-y divide-gray-200">
                    @foreach($schedules as $schedule)
                        <div class="py-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="w-32 font-bold text-gray-800 capitalize">
                                {{ $schedule->day_name }}
                            </div>

                            <div class="flex items-center space-x-2">
                                <x-input type="time" name="schedules[{{ $schedule->id }}][start_time]" 
                                         value="{{ old('schedules.'.$schedule->id.'.start_time', $schedule->start_time) }}" />
                                <span class="text-gray-400 text-sm">a</span>
                                <x-input type="time" name="schedules[{{ $schedule->id }}][end_time]" 
                                         value="{{ old('schedules.'.$schedule->id.'.end_time', $schedule->end_time) }}" />
                            </div>

                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="schedules[{{ $schedule->id }}][is_active]" 
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       {{ $schedule->is_active ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600 font-medium">Habilitado</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-end">
                    <x-button>
                        Guardar Cambios
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
