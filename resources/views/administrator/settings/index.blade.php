@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Configuraciones'],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4">        
        <div class="space-y-2 mb-3">
            <h1 class="text-3xl font-black text-white tracking-tight">Panel de Configuración del SaaS</h1>
            <p class="text-base text-slate-400">Modifica las variables operativas y comerciales globales del sistema.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('administrator.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- ── SECCIÓN SAAS ─────────────────────────────────────────────── --}}
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-2xl">
                <div class="flex items-center gap-3 pb-4 border-b border-white/5">
                    <div class="w-8 h-8 bg-blue-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-wider">General SaaS</h2>
                        <p class="text-xs text-slate-500">Configuración operativa del sistema</p>
                    </div>
                </div>

                {{-- Precio del Análisis --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold tracking-wider text-white block">Precio del Análisis Médico (COP)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500 font-bold">$</span>
                        <input type="number" step="0.01" name="medical_analysis_price" required
                            value="{{ old('medical_analysis_price', $settings['medical_analysis_price'] ?? '0.00') }}"
                            class="w-full pl-8 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <p class="text-xs text-slate-400">Costo cobrado en la pasarela de pago antes del análisis por IA.</p>
                </div>

                {{-- Correo de Soporte --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold tracking-wider text-white block">Correo Electrónico de Soporte</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615"/>
                            </svg>
                        </span>
                        <input type="email" name="support_email" required
                            value="{{ old('support_email', $settings['support_email'] ?? '') }}"
                            class="w-full pl-10 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <p class="text-xs text-slate-400">Remitente oficial para los correos de soporte técnico.</p>
                </div>
            </div>

            {{-- ── SECCIÓN COMISIONES ───────────────────────────────────────── --}}
            <div class="bg-slate-950 border border-white/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-2xl">
                <div class="flex items-center gap-3 pb-4 border-b border-white/5">
                    <div class="w-8 h-8 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-wider">Comisiones de la Plataforma</h2>
                        <p class="text-xs text-slate-500">Porcentajes cobrados sobre el valor de cada cita</p>
                    </div>
                </div>

                {{-- Fee Wompi --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold tracking-wider text-white block">Fee Wompi (%)</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="wompi_fee" required min="0" max="100"
                            value="{{ old('wompi_fee', $settings['wompi_fee'] ?? '2.9') }}"
                            class="w-full p-3 pr-10 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500 font-bold">%</span>
                    </div>
                    <p class="text-xs text-slate-400">Porcentaje que cobra Wompi por cada transacción. Se descuenta de tu ganancia neta.</p>
                </div>

                {{-- Citas Virtuales --}}
                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-wider">Citas Virtuales</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white block">Comisión Médico Particular (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="virtual_commission_doctor" required min="0" max="100"
                                    value="{{ old('virtual_commission_doctor', $settings['virtual_commission_doctor'] ?? '15') }}"
                                    class="w-full p-3 pr-10 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500 font-bold">%</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white block">Comisión Clínica (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="virtual_commission_clinic" required min="0" max="100"
                                    value="{{ old('virtual_commission_clinic', $settings['virtual_commission_clinic'] ?? '10') }}"
                                    class="w-full p-3 pr-10 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500 font-bold">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Citas Presenciales --}}
                <div class="space-y-3">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-wider">Citas Presenciales</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white block">Comisión Médico Particular (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="presential_commission_doctor" required min="0" max="100"
                                    value="{{ old('presential_commission_doctor', $settings['presential_commission_doctor'] ?? '0') }}"
                                    class="w-full p-3 pr-10 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500 font-bold">%</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-white block">Comisión Clínica (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="presential_commission_clinic" required min="0" max="100"
                                    value="{{ old('presential_commission_clinic', $settings['presential_commission_clinic'] ?? '0') }}"
                                    class="w-full p-3 pr-10 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-500 font-bold">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resumen visual --}}
                <div class="bg-slate-900 border border-white/5 rounded-2xl p-4 space-y-2"
                    x-data="{
                        doctorRate: {{ $settings['virtual_commission_doctor'] ?? 15 }},
                        clinicRate: {{ $settings['virtual_commission_clinic'] ?? 10 }},
                        wompi: {{ $settings['wompi_fee'] ?? 2.9 }},
                        price: 100000,
                        get doctorTotal() { return this.price + (this.price * this.doctorRate / 100); },
                        get doctorNet() { return (this.price * this.doctorRate / 100) - (this.doctorTotal * this.wompi / 100); },
                        get clinicTotal() { return this.price + (this.price * this.clinicRate / 100); },
                        get clinicNet() { return (this.price * this.clinicRate / 100) - (this.clinicTotal * this.wompi / 100); },
                        fmt(n) { return '$' + Math.round(n).toLocaleString('es-CO'); },
                        init() {
                            document.querySelector('[name=virtual_commission_doctor]').addEventListener('input', e => { this.doctorRate = +e.target.value; });
                            document.querySelector('[name=virtual_commission_clinic]').addEventListener('input', e => { this.clinicRate = +e.target.value; });
                            document.querySelector('[name=wompi_fee]').addEventListener('input', e => { this.wompi = +e.target.value; });
                        }
                    }"
                    x-init="init()">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Simulador — Cita de $100.000</p>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-slate-800 rounded-xl p-3">
                            <p class="text-slate-400 mb-1">Paciente paga (doctor)</p>
                            <p class="font-black text-white" x-text="fmt(doctorTotal)"></p>
                        </div>
                        <div class="bg-slate-800 rounded-xl p-3">
                            <p class="text-slate-400 mb-1">Tu ganancia neta (doctor)</p>
                            <p class="font-black text-emerald-400" x-text="fmt(doctorNet)"></p>
                        </div>
                        <div class="bg-slate-800 rounded-xl p-3">
                            <p class="text-slate-400 mb-1">Paciente paga (clínica)</p>
                            <p class="font-black text-white" x-text="fmt(clinicTotal)"></p>
                        </div>
                        <div class="bg-slate-800 rounded-xl p-3">
                            <p class="text-slate-400 mb-1">Tu ganancia neta (clínica)</p>
                            <p class="font-black text-emerald-400" x-text="fmt(clinicNet)"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botón guardar --}}
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-8 rounded-xl text-sm transition shadow-lg shadow-blue-500/10">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>