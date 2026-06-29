<x-guest-layout>
    <div class="bg-gray-50 py-12 px-4 mt-5">
        <div class="max-w-7xl mx-auto text-center mb-12 mt-5">
            <h2 class="text-4xl font-black text-gray-900 mb-4">Elige el plan ideal para tu consultorio</h2>
            <p class="text-lg text-gray-600">
                Para conocer las tarifas entiende primero los planes. Explora en qué consiste cada uno y cómo se ajustan a lo que necesitas.
            </p>
            <p class="text-lg text-gray-600">Potencia tu visibilidad y gestiona tus citas de forma profesional.</p>
        </div>

        {{-- Planes para Especialistas (Doctores) --}}
        <div class="max-w-6xl mx-auto my-16">
            <div class="text-center mb-10">
                <h3 class="text-3xl font-bold text-gray-900 mb-2">Para Especialistas, por tiempo limitado!!</h3>
                <p class="text-lg text-gray-600">Valido para los <strong class="text-emerald-500">primeros 50 especialistas</strong></p>    
                <p class="text-lg text-gray-600">Planes diseñados para profesionales de la salud independientes</p>    
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($plans->where('applicable_role', 'doctor') as $plan)
                    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 flex flex-col {{ $plan->plan === 'gold' ? 'ring-4 ring-blue-600 relative' : '' }}">
                        
                        @if($plan->plan === 'gold')
                            <span class="absolute top-2 right-1 bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase" style="right: 16px; top: 15px;">
                                Más popular
                            </span>
                        @endif

                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900">${{ number_format($plan->price, 0) }}</span>
                                <span class="text-gray-500">/año</span>
                            </div>
                        </div>

                        <ul class="space-y-4 mb-8 flex-1">
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Doctores <strong>{{ $plan->max_doctors }}</strong></span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Acceso a <strong>{{ $plan->max_addresses }}</strong> {{ $plan->max_addresses > 1 ? "sedes físicas" : "sede física" }}</span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span><strong>Sede virtual incluida</strong> (Citas por zoom. Aplica comisión, pago exclusivo en línea)</span>
                            </li>  
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Hasta <strong>{{ $plan->max_services }}</strong> servicios globales</span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Chat directo con el paciente</span>
                            </li>
                            @if ($plan->ai_scribe_enabled)
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Notas de Evolución Actual por voz y transcripción precisa con la IA</span>
                            </li>
                            @endif
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Agenda en tiempo real</span>
                            </li>                                                         
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Citas anuales: {{ $plan->max_appointments_per_year < "9999" ? $plan->max_appointments_per_year : 'Ilimitadas' }} </span>
                            </li>  
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Notificaciones por {{ $plan->plan === 'free' ? 'Email': 'Email y WhatsApp' }}</span>
                            </li>                                                   
                            @if($plan->plan !== 'free')
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Analítica</span>
                                </li>
                                @if ($plan->plan === 'gold' || $plan->plan === 'clinic_gold')
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Rendimiento financiero (KPI)</span>
                                </li>
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Ingresos por consultorio (KPI)</span>
                                </li>
                                @endif
                            @endif
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Soporte</span>
                            </li>                        
                        </ul>

                        <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-4 rounded-2xl font-black transition {{ $plan->plan === 'premium' ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                Seleccionar {{ $plan->name }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Planes para Clínicas --}}
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-10">
                <h3 class="text-3xl font-bold text-gray-900 mb-2">Para Clínicas</h3>
                <p class="text-lg text-gray-600">Planes optimizados para centros médicos y clínicas con múltiples especialistas</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($plans->where('applicable_role', 'clinic') as $plan)
                    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 flex flex-col {{ $plan->plan === 'clinic_gold' ? 'ring-4 ring-blue-600 relative' : '' }}">
                        
                        @if($plan->plan === 'clinic_gold')
                            <span class="absolute top-2 right-1 bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase" style="right: 16px; top: 15px;">
                                Más popular
                            </span>
                        @endif

                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900">${{ number_format($plan->price, 0) }}</span>
                                <span class="text-gray-500">/año</span>
                            </div>
                        </div>

                        <ul class="space-y-4 mb-8 flex-1">
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Hasta <strong>{{ $plan->max_addresses }}</strong> sedes físicas</span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Hasta <strong>{{ $plan->max_services }}</strong> servicios globales</span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span><strong>Sede virtual incluida</strong> (Citas por zoom. Aplica comisión, pago exclusivo en línea)</span>
                            </li> 
                            @if($plan->plan !== 'free')
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Analítica</span>
                                </li>
                                @if ($plan->plan === 'gold' || $plan->plan === 'clinic_gold')
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Rendimiento financiero (KPI)</span>
                                </li>
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Ingresos por consultorio (KPI)</span>
                                </li>
                                @endif
                            @endif
                            <li class="flex items-center gap-3 text-gray-700">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Soporte</span>
                            </li>                        
                        </ul>

                        <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-4 rounded-2xl font-black transition {{ $plan->plan === 'clinic_premium' ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                Seleccionar {{ $plan->name }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-guest-layout>