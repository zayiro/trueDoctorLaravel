@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Validación de Documentos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Validación de Cuentas Comerciales</h1>
                <p class="text-sm text-slate-500 mt-1">Revisa las credenciales de médicos y clínicas postuladas para activar sus cuentas.</p>
            </div>
            
            <div class="mt-4 md:mt-0 bg-indigo-50 text-indigo-700 text-xs font-semibold px-4 py-2 rounded-full border border-indigo-100 self-start">
                {{ $partners->total() }} Solicitudes pendientes
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 mb-6 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                            <th class="p-4">Cuenta / Contacto</th>
                            <th class="p-4">Documentación Soportada</th>
                            <th class="p-4 text-right">Acciones de Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($partners as $partner)
                            @php
                                $isClinic = $partner->user?->role === 'clinic';
                                $queryParam = $isClinic ? ['clinic_id' => $partner->id] : ['doctor_id' => $partner->id];
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <td class="p-4">
                                    <div class="font-semibold text-slate-900">
                                        {{ $partner->user->name ?? ($isClinic ? 'Clínica sin nombre' : 'Médico sin nombre') }}
                                        <span class="ml-1.5 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full {{ $isClinic ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $isClinic ? 'Clínica' : 'Médico' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1">{{ $partner->user->email ?? 'Sin correo' }}</div>
                                    @if($partner->phone)
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">📞 {{ $partner->phone }}</div>
                                    @endif
                                </td>
                                
                                <td class="p-4">
                                    <div class="flex flex-col gap-y-2">                                        
                                        @if ($partner->identity_card_path)
                                            <a href="{{ route('administrator.document.view', array_merge(['type' => 'cedula'], $queryParam)) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline group w-fit">
                                                <span class="mr-1.5">📜</span> Ver {{ $isClinic ? 'RUT / NIT' : 'Identificación' }}
                                            </a>
                                        @else
                                            <span class="text-xs text-red-500 font-medium">❌ Falta {{ $isClinic ? 'RUT / NIT' : 'Identificación' }}</span>
                                        @endif
                                        
                                        @if ($partner->professional_card_path)
                                            <a href="{{ route('administrator.document.view', array_merge(['type' => 'tarjeta'], $queryParam)) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline group w-fit">
                                                <span class="mr-1.5">📜</span> Ver {{ $isClinic ? 'Certificado REPS' : 'Tarjeta Profesional' }}
                                            </a>
                                        @else
                                            <span class="text-xs text-red-500 font-medium">❌ Falta {{ $isClinic ? 'Certificado REPS' : 'Tarjeta Profesional' }}</span>
                                        @endif                                    
                                    </div>
                                </td>

                                <td class="p-4 text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-x-2">
                                        @if ($partner->identity_card_path && $partner->professional_card_path)
                                            <form action="{{ route('administrator.validation.update') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="{{ $isClinic ? 'clinic_id' : 'doctor_id' }}" value="{{ $partner->id }}">
                                                <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition">Aprobar</button>
                                            </form>

                                            <form action="{{ route('administrator.validation.update') }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas rechazar esta cuenta?')">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <input type="hidden" name="{{ $isClinic ? 'clinic_id' : 'doctor_id' }}" value="{{ $partner->id }}">
                                                <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition">Rechazar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center bg-slate-50/30">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full mb-3 text-xl border border-emerald-100">✓</div>
                                    <div class="text-sm font-semibold text-slate-900">Al día con las revisiones</div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">No hay registros esperando validación de documentos.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($partners->hasPages())
            <div class="mt-6">
                {{ $partners->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
