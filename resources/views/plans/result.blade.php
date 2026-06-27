<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
        <div class="bg-white rounded-3xl shadow-lg p-10 max-w-md w-full text-center">
           @php $status = request('status') @endphp

            @if($status === 'approved')
                {{-- Pago aprobado --}}
                <div class="flex flex-col items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">¡Pago exitoso!</h1>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Tu plan ha sido activado correctamente.
                    </p>
                    <a href="{{ route('admin.dashboard') }}"
                        class="mt-4 bg-green-500 hover:bg-green-600 text-white font-bold px-8 py-3 rounded-2xl transition">
                        Ir al dashboard
                    </a>
                </div>

            @elseif($status === 'declined')
                <div class="flex flex-col items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">Pago no aprobado</h1>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        La transacción fue rechazada. Por favor intenta de nuevo.
                    </p>
                    <a href="{{ route('plans.index') }}"
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-2xl transition">
                        Ver planes
                    </a>
                </div>

            @else
                <div class="flex flex-col items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">Estado pendiente</h1>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Tu pago está siendo procesado. Te notificaremos pronto.
                    </p>
                    <a href="{{ route('admin.dashboard') }}"
                        class="mt-4 bg-slate-700 hover:bg-slate-800 text-white font-bold px-8 py-3 rounded-2xl transition">
                        Ir al dashboard
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-guest-layout>