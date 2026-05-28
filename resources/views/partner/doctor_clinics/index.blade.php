@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Invitaciones de clínicas']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="p-4 mx-auto max-w-7xl sm:p-6" x-data="{ activeTab: 'all' }">
        
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl dark:text-white">
                    Centros Médicos y Alianzas
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gestiona tus vinculaciones institucionales, aprueba invitaciones y controla tu disponibilidad en clínicas.
                </p>
            </div>
        </div>
        @if(session('success'))
            <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 dark:bg-gray-800 dark:text-green-400 dark:border-green-900" role="alert">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 dark:bg-gray-800 dark:text-red-400 dark:border-red-900" role="alert">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                <li class="mr-2">
                    <button @click="activeTab = 'all'" 
                            :class="activeTab === 'all' ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
                            class="inline-block p-4 border-b-2 rounded-t-lg transition-colors duration-200">
                        Todas las Clínicas ({{ $clinics->count() }})
                    </button>
                </li>
                <li class="mr-2">
                    <button @click="activeTab = 'pending'" 
                            :class="activeTab === 'pending' ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
                            class="inline-block p-4 border-b-2 rounded-t-lg transition-colors duration-200">
                        Invitaciones Pendientes ({{ $clinics->where('pivot.status', 'pending')->count() }})
                    </button>
                </li>
                <li class="mr-2">
                    <button @click="activeTab = 'approved'" 
                            :class="activeTab === 'approved' ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
                            class="inline-block p-4 border-b-2 rounded-t-lg transition-colors duration-200">
                        Staff Activo ({{ $clinics->where('pivot.status', 'approved')->count() }})
                    </button>
                </li>
            </ul>
        </div>
        @if($clinics->isEmpty())
            <div class="p-8 text-center bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700 shadow-sm">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aún no hay vinculaciones</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Cuando una clínica te invite a su equipo médico, aparecerá aquí.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($clinics as $clinic)
                    <div x-show="activeTab === 'all' || activeTab === '{{ $clinic->pivot->status }}'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="flex flex-col justify-between bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                        
                        <div class="p-5">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-lg">
                                    {{ strtoupper(substr($clinic->user->name ?? $clinic->name, 0, 2)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                        {{ $clinic->user->name ?? $clinic->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Recibida: {{ $clinic->pivot->created_at->format('d M, Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                @if($clinic->pivot->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800">
                                        Invitación Pendiente
                                    </span>
                                @elseif($clinic->pivot->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-green-50 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800">
                                        Staff Activo
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-b-xl flex items-center justify-end space-x-2">
                            @if($clinic->pivot->status === 'pending')
                                <form action="{{ route('partner.doctor_clinics.reject', $clinic) }}" method="POST" class="inline" onsubmit="return confirm('¿Rechazar la solicitud de esta clínica?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-2 text-xs font-medium text-red-700 hover:text-white border border-red-300 hover:bg-red-600 rounded-lg dark:border-red-600 dark:text-red-400 dark:hover:text-white dark:hover:bg-red-600 transition-colors duration-150">
                                        Rechazar
                                    </button>
                                </form>

                                <form action="{{ route('partner.doctor_clinics.accept', $clinic) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors duration-150">
                                        Aceptar Alianza
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('partner.doctor_clinics.reject', $clinic) }}" method="POST" class="inline" onsubmit="return confirm('¿Deseas darte de baja de esta clínica?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-indigo-500/75 hover:bg-gray-100 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors duration-150">
                                        Retirarse del Centro
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-admin-layout>
