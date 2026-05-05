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
                        Gestiona los límites de sedes y servicios para tu cuenta profesional.
                    </p>
                </div>
            </div>

            <!-- Columna de Contenido -->
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-md bg-white p-6 border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        @foreach($plans as $plan)
                            <form action="{{ route('doctor.profile.plan.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->plan }}">
                                
                                <button type="submit" 
                                    class="w-full text-left p-4 rounded-xl border-2 transition duration-200 
                                    {{ $doctor->settings->plan_id == $plan->id ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-gray-200 hover:border-blue-300' }}">
                                    
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-black text-gray-800">{{ $plan->name }}</span>
                                        @if($doctor->settings->plan_id == $plan->id)
                                            <span class="bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-tighter">Activo</span>
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                            {{ $plan->max_addresses }} {{ Str::plural('Sede', $plan->max_addresses) }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                            {{ $plan->max_services_per_address }} Servicios x Sede
                                        </p>
                                        
                                        <div class="mt-3 pt-2 border-t border-gray-100">
                                            <span class="text-sm font-black text-blue-600">
                                                {{ $plan->price > 0 ? '$' . number_format($plan->price, 0) : 'Gratis' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        @endforeach

                    </div>

                    <!-- Aviso Dinámico -->
                    <div class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            <strong>Nota:</strong> Al cambiar a un plan con menores beneficios, el sistema mantendrá activas solo las sedes y servicios más antiguos hasta alcanzar el límite permitido.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
