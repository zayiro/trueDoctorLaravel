@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Agenda médica',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-10 px-4">
        <!-- Encabezado y Filtro -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h2 class="text-3xl font-black text-gray-800">Mi Agenda</h2>
            
            <form action="{{ route('partner.appointments.index') }}" method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-xl border-gray-300 text-sm focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold">Filtrar</button>
            </form>
        </div>

        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-400">Hora</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-400">Paciente</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-400">Servicio / Modalidad</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-400">Estado</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <div class="space-y-8">
                        @forelse($appointments as $addressId => $group)
                            <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
                                <!-- Encabezado de la Sede -->
                                <div class="bg-gray-800 px-6 py-3 flex justify-between items-center">
                                    <h3 class="text-white font-bold text-sm uppercase tracking-wider">
                                        📍 Sede: {{ $group->first()->address->name }}
                                    </h3>
                                    <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-lg font-black">
                                        {{ $group->count() }} citas
                                    </span>
                                </div>

                                <table class="w-full text-left border-collapse">
                                    <!-- ... (thead igual que antes) ... -->
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($group as $app)
                                            <tr class="hover:bg-blue-50/30 transition">
                                                <td class="px-6 py-4">
                                                    <span class="font-black text-gray-800">{{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}</span>
                                                    <p class="text-[10px] text-gray-400">{{ $app->duration }} min</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-gray-800">{{ $app->patient->user->name }}</p>
                                                    <p class="text-xs text-gray-500">ID: {{ $app->patient->identification }}</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="text-sm font-medium text-gray-700">{{ $app->service->name }}</p>
                                                    @if($app->service->type === 'virtual')
                                                        <span class="text-[10px] bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold uppercase">💻 Virtual</span>
                                                    @else
                                                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold uppercase">🏥 {{ $app->address->name }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-xs font-bold {{ $app->status === 'confirmed' ? 'text-green-600' : 'text-amber-600' }}">
                                                        ● {{ ucfirst($app->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex gap-2">
                                                        @if($app->service->type === 'virtual')
                                                            <a href="{{ $app->meeting_link }}" target="_blank" title="Unirse" class="p-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                            </a>
                                                        @endif
                                                        <button onclick="alert('Motivo: {{ $app->notes }}')" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @empty
                            <div class="bg-white p-12 rounded-3xl text-center text-gray-400 italic shadow-sm">
                                No hay citas programadas para hoy.
                            </div>
                        @endforelse
                    </div>
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
