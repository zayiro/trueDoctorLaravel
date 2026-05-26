@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Detalle de Cita: ' . $appointment->reference] // Indica la cita actual
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- 🔥 SECCIÓN 5: ACCIONES DE GESTIÓN DE ESTADO (SEGÚN ROL) -->
    <div class="border-t border-slate-100 pt-6 flex flex-wrap gap-3 justify-end">
        
        {{-- Banner de alertas de éxito o error --}}
        @if (session('success'))
            <div class="w-full text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-4 py-3 rounded-xl mb-2">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- ACCIONES PARA EL PACIENTE --}}
        @if($user->role === 'patient' && in_array($appointment->status, ['pending', 'confirmed']))
            <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar tu cita médica?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" class="bg-red-50 text-red-600 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-red-100 transition-colors">
                    Cancelar Mi Cita
                </button>
            </form>
        @endif

        {{-- ACCIONES PARA EL PERSONAL MÉDICO (DOCTOR, CLÍNICA, ADMIN) --}}
        @if(in_array($user->role, ['doctor', 'clinic', 'admin']))
            
            {{-- Botón: Confirmar Cita (Solo si está pendiente) --}}
            @if($appointment->status === 'pending')
                <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-emerald-700 shadow-md transition-colors">
                        Confirmar Turno
                    </button>
                </form>
            @endif

            {{-- Botón: Concluir/Completar Cita (Solo si está confirmada) --}}
            @if($appointment->status === 'confirmed')
                <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-indigo-700 shadow-md transition-colors">
                        Finalizar Consulta
                    </button>
                </form>
            @endif

            {{-- Botón: Cancelar Cita (Permitido para médicos en estados no concluidos) --}}
            @if(in_array($appointment->status, ['pending', 'confirmed']))
                <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST" onsubmit="return confirm('¿Desea cancelar esta reservación médica? El paciente será notificado.');">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="bg-slate-100 text-slate-600 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-slate-200 transition-colors">
                        Declinar / Cancelar
                    </button>
                </form>
            @endif

        @endif
    </div>
</x-admin-layout>
