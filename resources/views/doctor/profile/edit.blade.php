@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('doctor.addresses.index'),
    ],
    [
        'name' => 'Perfil',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">  
    <div class="mt-10 sm:mt-0">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <!-- Columna de Título -->
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Plan de Suscripción</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Gestiona los límites de consultorios para tu cuenta.
                    </p>
                </div>
            </div>

            <!-- Columna de Contenido -->
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-md bg-white p-6 border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Opción Plan Básico -->
                        <form action="{{ route('doctor.profile.plan.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan" value="basico">
                            <button type="submit" 
                                class="w-full text-left p-4 rounded-xl border-2 transition duration-200 {{ Auth::user()->doctor->plan == 'basico' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200' : 'border-gray-200 hover:border-blue-300' }}">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-gray-800">Plan Básico</span>
                                    @if(Auth::user()->doctor->plan == 'basico')
                                        <span class="text-blue-600 text-xs font-bold uppercase">Activo</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Límite: 2 consultorios</p>
                            </button>
                        </form>

                        <!-- Opción Plan Avanzado -->
                        <form action="{{ route('doctor.profile.plan.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan" value="avanzado">
                            <button type="submit" 
                                class="w-full text-left p-4 rounded-xl border-2 transition duration-200 {{ Auth::user()->doctor->plan == 'avanzado' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200' : 'border-gray-200 hover:border-blue-300' }}">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-purple-700">Plan Avanzado</span>
                                    @if(Auth::user()->doctor->plan == 'avanzado')
                                        <span class="text-blue-600 text-xs font-bold uppercase">Activo</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Límite: 10 consultorios</p>
                            </button>
                        </form>

                    </div>

                    <!-- Aviso de Límite -->
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 italic">
                            * Si bajas al Plan Básico, solo podrás mantener 2 consultorios activos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>