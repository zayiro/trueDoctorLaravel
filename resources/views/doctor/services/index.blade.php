@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Servicios',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-8">
            <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center gap-2" href="{{ route('doctor.services.create') }}">
                <i class="fa-regular fa-map-location"></i>
                Nevo servicio
            </a>
        </div>

        <!-- Alertas de éxito/error -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Servicio</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Modalidad</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Duración</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Precio</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Sedes / Ubicación</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($services as $service)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-800 block">{{ $service->name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($service->type === 'virtual')
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold uppercase">
                                    💻 Telemedicina
                                </span>
                            @else
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">
                                    🏥 Presencial
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-medium">
                            {{ $service->duration }} min
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-green-600 font-black">${{ number_format($service->price, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($service->type === 'virtual')
                                <span class="text-gray-400 italic text-sm">Enlace digital</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($service->addresses as $address)
                                        <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded border border-gray-200">
                                            {{ $address->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <a href="#" class="text-gray-400 hover:text-blue-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </a>
                                <form action="#" method="POST" onsubmit="return confirm('¿Eliminar este servicio?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">
                            No has creado servicios todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
