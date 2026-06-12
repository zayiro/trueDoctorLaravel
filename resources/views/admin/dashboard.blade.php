@php
    $isPatient = $user->role === 'patient';
    $isDoctor = $user->role === 'doctor';
    $isClinic = $user->role === 'clinic';
    $isAdmin = $user->role === 'admin';

    $validationStatus = 'approved';
    if ($isDoctor) {
        $validationStatus = $user->doctor?->validation_status ?? 'missing';
    } elseif ($isClinic) {
        $validationStatus = $user->clinic?->validation_status ?? 'missing';
    }

    $breadcrumbs = [
        ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['name' => $isPatient ? 'Mi Panel de Salud' : 'Consola Analítica de Negocio']
    ];

    $doctorSettings = $user->doctorSettings;
    $hasIndividualPlan = $doctorSettings && $doctorSettings?->plan_id !== null;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">    
    <div>     
        @if($isAdmin || (($isDoctor || $isClinic) && $validationStatus === 'approved'))
            <div class="mb-5">
                @if($user->role !== 'patient' && isset($owner->slug))
                    @if ($user->role === 'doctor')
                    <a href="{{ route('partner.public.profile', ['slug' => $owner->slug]) }}" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 transition-colors">Ver perfil publico</a>
                    @elseif ($user->role === 'clinic')
                    <a href="{{ route('partner.clinic.public.decision', ['slug' => $owner->slug]) }}" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-indigo-700 transition-colors">Ver perfil publico clinico</a>
                    @endif
                @endif
            </div>
            <x-appointment-search-form class="mb-6 p-4 bg-white rounded-lg shadow" />
        @endif       

        <div class="max-w-7xl mx-auto py-8 space-y-8">
            @if($isPatient)
                <!-- CABECERA PRINCIPAL DEL PORTAL DE PACIENTES -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                    <div>
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-1 block">Portal del Paciente</span>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight">{{ ucfirst($user->name) }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Gestiona tus citas médicas y controla tus indicadores generales de bienestar.</p>
                    </div>
                    <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                        ID: <span class="text-emerald-600 font-black">#{{ $owner->identification }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Tu BMI</span>
                            <h3 class="text-2xl font-black text-indigo-600 tracking-tight">{{ $healthMetrics['bmi'] ?? 'N/A' }}</h3>
                            <span class="inline-block px-2 py-0.5 text-[9px] font-black rounded uppercase tracking-tighter @if($healthMetrics['bmi_status'] == 'Normal') bg-green-100 text-green-800 @elseif($healthMetrics['bmi_status'] == 'Bajo peso' || $healthMetrics['bmi_status'] == 'Underweight') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">
                                {{ $healthMetrics['bmi_status'] }}
                            </span>
                        </div>
                        <div class="p-3.5 bg-indigo-50 text-indigo-600 rounded-2xl border border-indigo-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Edad calculada</span>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ $healthMetrics['age'] }}</h3>
                        </div>
                        <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Tipo de sangre</span>
                            <h3 class="text-2xl font-black text-red-600 tracking-tight">{{ $healthMetrics['blood_type'] }}</h3>
                        </div>
                        <div class="p-3.5 bg-red-50 text-red-600 rounded-2xl border border-red-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Zona de Residencia</span>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ $owner->residence_zone }}</h3>
                        </div>
                        <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-lg">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">Próximas citas</h4>
                        @forelse($upcomingAppointments as $appointment)
                            @php
                            $appointmentStatusPatient = '';
                            
                            switch ($appointment->status) {
                                case 'pending': $appointmentStatusPatient = 'Pendiente';
                                break;
                                case 'confirmed': $appointmentStatusPatient = 'Confirmada';
                                break;
                                case 'cancelled': $appointmentStatusPatient = 'Cancelada';
                                break;
                                case 'completed': $appointmentStatusPatient = 'Completada';
                                break;
                            }
                            @endphp
                            <div class="flex justify-between items-center p-3.5 bg-slate-50 rounded-xl mb-2 border border-slate-100">
                                <div>
                                    <p class="text-xs font-black text-slate-700">Dr. {{ $appointment->doctor->user->name ?? 'Specialist' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y h:i A') }}</p>
                                </div>
                                <span class="px-2 py-1 text-[9px] font-black rounded-lg uppercase bg-indigo-100 text-indigo-800">{{ $appointmentStatusPatient }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-4 text-center">No tienes consultas pendientes.</p>
                        @endforelse
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-lg">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">Consultas asistidas</h4>
                        @forelse($pastAppointments as $appointment)
                            <div class="flex justify-between items-center p-3.5 bg-slate-50 rounded-xl mb-2 border border-slate-100">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Dr. {{ $appointment->doctor->user->name ?? 'Specialist' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</p>
                                </div>
                                <span class="px-2 py-1 text-[9px] font-black rounded-lg uppercase bg-emerald-100 text-emerald-800">Completed</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-4 text-center">Sin historial de consultas.</p>
                        @endforelse
                    </div>
                </div>
            @else
                
                @if(($isDoctor || $isClinic) && $validationStatus !== 'approved')
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                        <div>
                            <span class="text-[10px] font-black text-amber-600 uppercase tracking-[0.2em] mb-1 block">Verificación en Curso</span>
                            <h2 class="text-3xl font-black text-slate-800 tracking-tight">¡Hola, {{ $user->name }}!</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Para habilitar tu consola analítica y empezar a operar con tu agenda, completa tus soportes legales.</p>
                        </div>
                    </div>
                    <x-verify-docs :type="$user->role" :status="$validationStatus" />
                    
                @else
                    
                    <!-- CABECERA CORPORATIVA PARA ROLES DE NEGOCIO -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-[2rem] border border-gray-100 shadow-lg">
                        <div>
                            <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-1 block">Control Console</span>
                            <h2 class="text-3xl font-black text-slate-800 tracking-tight">Estadísticas e indicadores</h2>
                        </div>
                        <div class="text-xs font-bold text-slate-500 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 uppercase tracking-wider">
                            <span class="text-indigo-600 font-black">{{ $owner?->name ?? $user->name }}</span>
                        </div>
                    </div>

                    @if($user->role === 'admin')
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-900 p-6 rounded-[2rem] text-white">
                            <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Doctores Activos</span>
                                <span class="text-2xl font-black text-indigo-400 font-mono">{{ $usersByRole['doctor'] ?? 0 }}</span>
                            </div>
                            <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4 sm:pl-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Clínicas Activas</span>
                                <span class="text-2xl font-black text-purple-400 font-mono">{{ $usersByRole['clinic'] ?? 0 }}</span>
                            </div>
                            <div class="space-y-1 border-r border-slate-800 last:border-0 pr-4 sm:pl-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Pacientes</span>
                                <span class="text-2xl font-black text-emerald-400 font-mono">{{ $usersByRole['patient'] ?? 0 }}</span>
                            </div>
                            <div class="space-y-1 sm:pl-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Administradores</span>
                                <span class="text-2xl font-black text-amber-400 font-mono">{{ $usersByRole['admin'] ?? 0 }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- CUADRÍCULA DE KPIs FINANCIEROS Y OPERATIVOS -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">                        
                        @if ($hasIndividualPlan || $user->role !== 'patient')
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Ingresos Mensuales</span>
                                <h3 class="text-2xl font-black text-emerald-600">${{ number_format($monthlyRevenue, 2) }}</h3>
                            </div>
                        </div>

                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Consultas de Hoy</span>
                                <h3 class="text-2xl font-black text-slate-800">{{ $appointmentsToday }}</h3>
                            </div>
                        </div>

                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Próximos espacios</span>
                                <h3 class="text-2xl font-black text-slate-800">{{ $upcomingAppointmentsCount }}</h3>
                            </div>
                        </div>

                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Absentismo Global</span>
                                <h3 class="text-2xl font-black text-rose-600">{{ $cancellationRate }}%</h3>
                            </div>
                        </div>

                        <!-- 📊 SECCIÓN: HISTORIAL DE FACTURACIÓN CONSOLIDADA -->
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-indigo-500 p-2 rounded-xl shadow-lg shadow-indigo-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Rendimiento Financiero</h4>
                                    <p class="text-xs text-slate-400">Historial consolidado de ingresos de los últimos 5 meses de operación.</p>
                                </div>
                            </div>

                            <!-- Contenedor responsivo para el Canvas del gráfico -->
                            <div class="relative w-full h-72">
                                <canvas id="financialHistoryChart"></canvas>
                            </div>
                        </div>

                        <!-- 🏢 SECCIÓN: DISTRIBUCIÓN FINANCIERA POR SEDES -->
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 flex flex-col justify-between">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="bg-emerald-500 p-2 rounded-xl shadow-lg shadow-emerald-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v12m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Ingresos por Consultorio</h4>
                                    <p class="text-xs text-slate-400">Distribución de rentabilidad según el volumen de atenciones físicas o virtuales.</p>
                                </div>
                            </div>

                            <!-- Contenedor responsivo para el Canvas de la Dona -->
                            <div class="relative w-full h-64 flex items-center justify-center">
                                <canvas id="locationDistributionChart"></canvas>
                            </div>
                        </div>
                        @else
                        <div class="bg-white border rounded-[2rem] p-6 shadow-lg border-slate-100 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Consultas de Hoy</span>
                                <h3 class="text-2xl font-black text-slate-800">{{ $appointmentsToday }}</h3>
                            </div>
                        </div>
                        @endif
                    </div>
                @endif

            @endif

        </div>
    </div>

@if(!$isPatient)    
    <script src="{{ asset('js/chart/chart.umd.js') }}"></script>  
    <script>        
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('financialHistoryChart').getContext('2d');
            
            // Inicialización del gráfico de barras estilizado en CSSIndigo
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Ingresos Mensuales',
                        data: {!! json_encode($chartData) !!},
                        backgroundColor: 'rgba(79, 70, 229, 0.85)',
                        borderColor: 'rgb(79, 70, 229)',
                        borderWidth: 1,
                        borderRadius: 12,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }, // Ocultamos la leyenda para diseño limpio
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Total: $' + new Intl.NumberFormat('es-CO').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) { return '$' + new Intl.NumberFormat('es-CO').format(value); },
                                font: { size: 10, weight: 'bold' }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: 'bold' } }
                        }
                    }
                }
            });
        });
    
                // Inicialización de la Gráfica de Dona para las Sedes de Atención
        const locationCtx = document.getElementById('locationDistributionChart').getContext('2d');
        
        new Chart(locationCtx, {
            type: 'doughnut',
            data: {
                // Inyección segura de las variables en inglés procesadas en el controlador
                labels: {!! json_encode($locationLabels) !!},
                datasets: [{
                    data: {!! json_encode($locationRevenueData) !!},
                    // Paleta de colores corporativa premium (Índigo, Esmeralda, Ámbar)
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.85)',  
                        'rgba(16, 185, 129, 0.85)', 
                        'rgba(245, 158, 11, 0.85)'  
                    ],
                    borderColor: [
                        'rgb(79, 70, 229)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
                    ],
                    borderWidth: 2,
                    cutout: '70%', // Estilizado de dona delgada moderna
                    radius: '90%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom', // Muestra los nombres de las sedes abajo
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': $' + new Intl.NumberFormat('es-CO').format(context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
@endif
</x-admin-layout>
