@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Configuraciones',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">        
        <!-- Encabezado -->
        <div class="space-y-2 mb-3">
            <h1 class="text-3xl font-black text-white tracking-tight">Panel de Configuración del SaaS</h1>
            <p class="text-base text-slate-400">Modifica las variables operativas y comerciales globales del sistema.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario -->
        <form action="{{ route('administrator.settings.update') }}" method="POST" class="bg-slate-950 border border-white/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-2xl">
            @csrf
            @method('PUT')

            <!-- Precio del Análisis -->
            <div class="space-y-2">
                <label class="text-sm font-bold tracking-wider text-white block">Precio del Análisis Médico (COP)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500 font-bold">$</span>
                    <input type="number" step="0.01" name="medical_analysis_price" required
                        value="{{ old('medical_analysis_price', $settings['medical_analysis_price'] ?? '0.00') }}"
                        class="w-full pl-8 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <p class="text-xs text-slate-400">Este valor determinará el costo cobrado en la pasarela de pago antes del análisis por IA.</p>
            </div>

            <!-- Correo de Soporte -->
            <div class="space-y-2">
                <label class="text-sm font-bold tracking-wider text-white block">Correo Electrónico de Soporte</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615m19.5 0A2.25 2.25 0 0 0 19.5 4.5"/>
                        </svg>
                    </span>
                    <input type="email" name="support_email" required
                        value="{{ old('support_email', $settings['support_email'] ?? 'soporte@opendoctor.online') }}"
                        class="w-full pl-10 p-3 bg-slate-900 border border-white/10 rounded-xl font-medium text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <p class="text-xs text-slate-400">Remitente oficial para los correos de soporte técnico y contacto al pie de página.</p>
            </div>

            <!-- Botón de Guardado -->
            <div class="pt-4 border-t border-white/5 flex justify-end">
                <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-xl text-sm transition shadow-lg shadow-blue-500/10">
                    Guardar Configuración
                </button>
            </div>

        </form>
    </div>
</x-admin-layout>