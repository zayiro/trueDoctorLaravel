<x-guest-layout>
    <div class="max-w-7xl mx-auto py-12 px-4 mt-6">    
        <div class="bg-white mt-8 rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Decoración Superior Estilizada -->
            <div class="h-2 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600"></div>

            <div class="p-6 sm:p-10 text-center">            
                <!-- Título Principal -->
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-2">Sala de Espera Virtual</h1>
                <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">
                    Tu enlace de telemedicina se habilitará automáticamente para conectarte de forma segura.
                </p>

                <!-- CONTENEDOR DEL RELOJ DE CUENTA REGRESIVA -->
                <div id="countdown-container" class="grid grid-cols-4 gap-2 max-w-xs mx-auto mb-8 bg-gradient-to-b from-slate-50 to-indigo-50/20 p-4 rounded-2xl border border-slate-100 shadow-inner">
                    <div class="text-center">
                        <span id="days" class="block text-2xl font-black text-slate-800 tabular-nums">00</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Días</span>
                    </div>
                    <div class="text-center">
                        <span id="hours" class="block text-2xl font-black text-slate-800 tabular-nums">00</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Horas</span>
                    </div>
                    <div class="text-center">
                        <span id="minutes" class="block text-2xl font-black text-slate-800 tabular-nums">00</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Min</span>
                    </div>
                    <div class="text-center">
                        <span id="seconds" class="block text-2xl font-black text-indigo-600 tabular-nums animate-pulse">00</span>
                        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Seg</span>
                    </div>
                </div>

                <!-- Tarjeta con Datos de la Cita -->
                <div class="bg-slate-50/60 rounded-2xl p-5 text-left border border-slate-100/80 space-y-3 mb-8">
                    <div class="flex justify-between items-center border-b border-slate-200/50 pb-2.5">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Servicio</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $appointment->service->name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/50 pb-2.5">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Especialista</span>
                        <span class="text-sm font-semibold text-slate-700">Dr. {{ $appointment->doctor->user->name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/50 pb-2.5">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</span>
                        <span class="text-sm font-bold text-indigo-600">{{ $startTime->translatedFormat('l d \d\e F, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 tracking-wider">Hora Programada</span>
                        <span class="text-xs font-black text-slate-900 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs tabular-nums">
                            {{ $startTime->format('g:i A') }} ({{ $appointment->duration }} min)
                        </span>
                    </div>
                </div>

                <!-- Container del Botón Dinámico -->
                <div id="action-button-container">
                    @if($isAvailable)
                        <a href="{{ $appointment->meeting_link ?? '#' }}" 
                        class="inline-flex justify-center items-center gap-2 w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold tracking-wide text-sm rounded-xl shadow-lg shadow-indigo-100 hover:shadow-indigo-200 active:scale-[0.98] transition-all duration-200">
                            Ingresar a la videollamada ahora
                        </a>
                    @else
                        <button disabled class="w-full py-4 bg-slate-100 text-slate-400 font-semibold tracking-wide text-sm rounded-xl cursor-not-allowed border border-slate-200/40">
                            🔒 Videollamada Inactiva
                        </button>
                        <p id="helper-text" class="text-[11px] font-medium text-slate-400 mt-3 flex items-center justify-center gap-1.5">                        
                            El acceso se habilitará automáticamente 5 minutos antes de tu cita.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Footer Informativo -->
            <div class="bg-slate-50/80 px-6 py-4 border-t border-slate-100 text-center">
                <p class="text-[11px] text-slate-400 leading-relaxed max-w-sm mx-auto">
                    Mantén esta pestaña abierta. Recuerda otorgar permisos de cámara y micrófono al iniciar la llamada.
                </p>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT DEL RELOJ DE CUENTA REGRESIVA -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Se cambia la activación del botón a 5 minutos antes por consistencia UX
            const activationTimestamp = new Date("{{ $startTime->copy()->subMinutes(5)->toIso8601String() }}").getTime();
            const meetingEndTimestamp = new Date("{{ $startTime->copy()->addMinutes($appointment->duration)->toIso8601String() }}").getTime();
            const patientZoomUrl = "{{ $appointment->meeting_link ?? '#' }}";

            const countdownInterval = setInterval(function () {
                const now = new Date().getTime();
                let distance = activationTimestamp - now;

                // CASO 1: LA CITA ESTÁ ACTIVA O EN CURSO
                if (now >= activationTimestamp && now <= meetingEndTimestamp) {
                    clearInterval(countdownInterval);
                    
                    document.getElementById("days").innerText = "00";
                    document.getElementById("hours").innerText = "00";
                    document.getElementById("minutes").innerText = "00";
                    document.getElementById("seconds").innerText = "00";

                    document.getElementById("action-button-container").innerHTML = `
                        <a href="${patientZoomUrl}" target="_blank"
                           class="inline-flex justify-center items-center gap-2 w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold tracking-wide text-sm rounded-xl shadow-lg shadow-emerald-100 hover:shadow-emerald-200 active:scale-[0.98] transition-all duration-200 animate-bounce-short">
                            <span class="w-2 h-2 rounded-full bg-white animate-ping mr-1"></span>
                            Ingresar a la videollamada ahora
                        </a>
                    `;
                    return;
                }

                // CASO 2: LA CITA YA FINALIZÓ
                if (now > meetingEndTimestamp) {
                    clearInterval(countdownInterval);
                    document.getElementById("action-button-container").innerHTML = `
                        <button disabled class="w-full py-4 bg-rose-50 text-rose-400 font-semibold text-sm rounded-xl cursor-not-allowed border border-rose-100">
                            ⏰ Esta consulta ha finalizado
                        </button>
                    `;
                    return;
                }

                // CASO 3: EN ESPERA (CÁLCULO DEL RELOJ)
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("days").innerText = String(days).padStart(2, '0');
                document.getElementById("hours").innerText = String(hours).padStart(2, '0');
                document.getElementById("minutes").innerText = String(minutes).padStart(2, '0');
                document.getElementById("seconds").innerText = String(seconds).padStart(2, '0');
            }, 1000);
        });
    </script>
</x-guest-layout>
