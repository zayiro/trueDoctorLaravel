<x-guest-layout
    meta_title="Planes y Tarifas desde $0 | Citas Médicas en Línea | OpenDoctor Online" 
    meta_description="Encuentra el plan ideal para tu práctica médica o clínica. Desde nuestro Plan Esencial gratuito hasta el Plan Gold por $950.000/año con citas médicas ilimitadas, agenda en tiempo real e historias clínicas con IA."
    keywords="planes médicos, tarifas consultorios, software médico, agenda médica, citas médicas en línea, telemedicina, historias clínicas digitales"
>
    @php
        // Pesos colombianos con punto de miles: $950.000
        $money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        // Solo se lee para decidir si se muestra la nota; el valor de la comisión NUNCA se publica en esta página.
        $comDoctor = \App\Models\Setting::get('virtual_commission_doctor');
        $comClinic = \App\Models\Setting::get('virtual_commission_clinic');
        $hasComDoctor = is_numeric($comDoctor) && (float) $comDoctor > 0;
        $hasComClinic = is_numeric($comClinic) && (float) $comClinic > 0;
        $docPlans = $plans->where('applicable_role', 'doctor')->sortBy('price');
        $clinicPlans = $plans->where('applicable_role', 'clinic')->sortBy('price');
    @endphp

    <style>
        .pl-grid{display:grid;grid-template-columns:1fr;gap:2rem;max-width:56rem;margin:0 auto}
        @media (min-width:768px){
            .pl-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
            .pl-grid-3{grid-template-columns:repeat(3,minmax(0,1fr));max-width:72rem}
        }
        .pl-btn{display:block;width:100%;box-sizing:border-box;padding:1rem;border:0;border-radius:1rem;text-align:center;font-size:1rem;font-weight:800;line-height:1.25;cursor:pointer;text-decoration:none;transition:background-color .2s}
        .pl-btn-primary{background:#2563eb;color:#fff}.pl-btn-primary:hover{background:#1d4ed8}
        .pl-btn-ghost{background:#f1f5f9;color:#1e293b}.pl-btn-ghost:hover{background:#e2e8f0}
        .pl-note{max-width:56rem;margin:2rem auto 0;padding:1.25rem 1.5rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:1rem;color:#1e3a8a;font-size:.95rem;line-height:1.65;text-align:left}
        .pl-note-title{margin:0 0 .35rem;font-size:1rem;font-weight:800;color:#1e3a8a}
        .pl-note p{margin:0}
        .pl-foot{max-width:56rem;margin:1rem auto 0;text-align:center;font-size:.8rem;color:#94a3b8}
        /* pl-checks-fix: el check de cada ventaja no se encoge cuando el texto ocupa varias líneas */
        .pl-grid li > svg{flex:none;width:1.25rem;height:1.25rem}
    </style>

    <div class="bg-white py-12 px-4" data-planes-claridad data-planes-claridad-v2>
        <div class="max-w-7xl mx-auto text-center mb-12 mt-5">
            <h1 class="text-4xl font-black text-gray-900 mb-2">Planes que crecen contigo</h1>            
            <p class="text-lg text-gray-600">Potencia tu visibilidad en la web y gestiona tus citas médicas de forma profesional.</p>
        </div>

        <div x-data="{ tab: 'especialistas' }" class="w-full max-w-7xl mx-auto mt-6">
            <!-- Selector de audiencia -->
            <div class="flex border-b border-gray-200" role="tablist">
                <button 
                    type="button" role="tab" :aria-selected="tab === 'especialistas'"
                    @click="tab = 'especialistas'" 
                    :class="tab === 'especialistas' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none">
                    Para Doctores
                </button>
                <button 
                    type="button" role="tab" :aria-selected="tab === 'clinicas'"
                    @click="tab = 'clinicas'" 
                    :class="tab === 'clinicas' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none">
                    Para Clínicas
                </button>
            </div>

            <div class="mt-6 bg-white p-6 rounded-lg shadow-sm">
                <!-- ===================== DOCTORES ===================== -->
                <div x-show="tab === 'especialistas'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                    <div class="max-w-6xl mx-auto my-8">
                        <div class="text-center mb-10">
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">Planes para Doctores</h2>
                            <p class="text-lg text-gray-600">Válido para los <strong class="text-emerald-500">primeros 50 doctores</strong></p>
                            <p class="text-lg text-gray-600">Planes diseñados para profesionales de la salud independientes</p>
                        </div>

                        <div class="pl-grid {{ $docPlans->count() >= 3 ? 'pl-grid-3' : 'pl-grid-2' }}">
                            @foreach($docPlans as $plan)
                                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 flex flex-col {{ $plan->plan === 'gold' ? 'ring-4 ring-blue-600 relative' : '' }}">

                                    @if($plan->plan === 'gold')
                                        <span class="absolute bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase" style="right: 16px; top: 15px;">
                                            Recomendado
                                        </span>
                                    @endif

                                    <div class="mb-8">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h3>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-4xl font-black text-gray-900">{{ $money($plan->price) }}</span>
                                            <span class="text-gray-500">COP /año</span>
                                        </div>
                                        @if ((float) $plan->price > 0)
                                            <p class="text-sm text-gray-500 mt-1">Pago único por {{ $plan->durationLabel() }} · equivale a {{ $money($plan->perMonth()) }}/mes</p>
                                        @else
                                            <p class="text-sm text-gray-500 mt-1">Sin costo de suscripción</p>
                                        @endif
                                    </div>

                                    <ul class="space-y-4 mb-8 flex-1">
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span><strong>Sede virtual incluida</strong> (citas virtuales por Zoom con pago en línea; la comisión está explicada abajo)</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Hasta <strong>{{ $plan->max_services }}</strong> servicios médicos configurables</span>
                                        </li>
                                        @if ($plan->max_addresses)
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Permite crear <strong>{{ $plan->max_addresses }}</strong> {{ $plan->max_addresses > 1 ? 'sedes físicas' : 'sede física' }}</span>
                                        </li>
                                        @endif
                                        @if ($plan->can_see_whatsapp_contact_button)
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Botón de chat directo en tu perfil web para recibir mensajes de pacientes potenciales.</span>
                                        </li>
                                        @endif
                                        @if ($plan->ai_scribe_enabled)
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Convierte tu dictado en documentación clínica estructurada: nota clínica en formato SOAP y transcripción precisa con IA</span>
                                        </li>
                                        @endif
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Agenda en tiempo real</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>
                                            @if ((int) $plan->max_appointments_per_year < 9999)
                                                Capacidad para gestionar hasta <strong>{{ $plan->max_appointments_per_year }}</strong> citas anuales
                                            @else
                                                Citas anuales ilimitadas
                                            @endif
                                            </span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Notificaciones por Email</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Notificaciones por SMS</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Analítica</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Rendimiento financiero (KPI)</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Ingresos por consultorio (KPI)</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Soporte</span>
                                        </li>
                                    </ul>

                                    @if ((float) $plan->price > 0)
                                        <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="pl-btn {{ $plan->plan === 'gold' ? 'pl-btn-primary' : 'pl-btn-ghost' }}">
                                                Elegir {{ $plan->name }}
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('partner.register') }}" class="pl-btn pl-btn-ghost">Empezar gratis</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($hasComDoctor)
                            <div class="pl-note">
                                <p class="pl-note-title">¿Cuánto cobra OpenDoctor por cita?</p>
                                <p>
                                    Las consultas, virtuales o presenciales, que el paciente paga en línea incluyen una <strong>comisión de servicio de la plataforma</strong>, igual en todos los planes.
                                    <strong>Tú recibes el valor completo que fijas</strong> por la consulta.
                                </p>
                            </div>
                        @endif
                        <p class="pl-foot">Precios en pesos colombianos (COP).</p>
                    </div>
                </div>

                <!-- ===================== CLÍNICAS ===================== -->
                <div x-show="tab === 'clinicas'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-cloak>
                    <div class="max-w-6xl mx-auto">
                        <div class="text-center mb-10">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Planes para Clínicas</h2>
                            <p class="text-lg text-gray-600">Planes optimizados para centros médicos y clínicas con múltiples especialistas</p>
                        </div>

                        <div class="pl-grid {{ $clinicPlans->count() >= 3 ? 'pl-grid-3' : 'pl-grid-2' }}">
                            @foreach($clinicPlans as $plan)
                                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 flex flex-col {{ $plan->plan === 'clinic_gold' ? 'ring-4 ring-blue-600 relative' : '' }}">

                                    @if($plan->plan === 'clinic_gold')
                                        <span class="absolute bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase" style="right: 16px; top: 15px;">
                                            Recomendado
                                        </span>
                                    @endif

                                    <div class="mb-8">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h3>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-4xl font-black text-gray-900">{{ $money($plan->price) }}</span>
                                            <span class="text-gray-500">COP /año</span>
                                        </div>
                                        @if ((float) $plan->price > 0)
                                            <p class="text-sm text-gray-500 mt-1">Pago único por {{ $plan->durationLabel() }} · equivale a {{ $money($plan->perMonth()) }}/mes</p>
                                        @else
                                            <p class="text-sm text-gray-500 mt-1">Sin costo de suscripción</p>
                                        @endif
                                    </div>

                                    <ul class="space-y-4 mb-8 flex-1">
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span><strong>Atención virtual incluida</strong> (citas por Zoom con pago exclusivo en línea; la comisión está explicada abajo)</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>@if ($plan->max_addresses > 0)Hasta <strong>{{ $plan->max_addresses }}</strong> {{ $plan->max_addresses === 1 ? 'sede física' : 'sedes físicas' }}@else<strong>Sedes físicas:</strong> disponibles en los planes de pago @endif</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Hasta <strong>{{ $plan->max_services }}</strong> servicios globales</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Hasta <strong>{{ $plan->max_doctors }}</strong> especialistas</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>
                                            @if ((int) $plan->max_appointments_per_year < 9999)
                                                Capacidad para gestionar hasta <strong>{{ $plan->max_appointments_per_year }}</strong> citas anuales
                                            @else
                                                Citas anuales ilimitadas
                                            @endif
                                            </span>
                                        </li>
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
                                        <li class="flex items-center gap-3 text-gray-700">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Soporte</span>
                                        </li>
                                    </ul>

                                    @if ((float) $plan->price > 0)
                                        <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="pl-btn {{ $plan->plan === 'clinic_gold' ? 'pl-btn-primary' : 'pl-btn-ghost' }}">
                                                Elegir {{ $plan->name }}
                                            </button>
                                        </form>
                                    @else
                                        <a href="/register-clinic" class="pl-btn pl-btn-ghost">Empezar gratis</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($hasComClinic)
                            <div class="pl-note">
                                <p class="pl-note-title">¿Cuánto cobra OpenDoctor por cita?</p>
                                <p>
                                    Las consultas, virtuales o presenciales, que el paciente paga en línea incluyen una <strong>comisión de servicio de la plataforma</strong>, igual en todos los planes.
                                    <strong>La clínica recibe el valor completo que fija</strong> por la consulta.
                                </p>
                            </div>
                        @endif
                        <p class="pl-foot">Precios en pesos colombianos (COP).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
