<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - Consulta Médica</title>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 font-sans text-slate-800">

    <div class="bg-white max-w-xl w-full rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
        <!-- Decoración Superior -->
        <div class="h-2 w-full bg-indigo-600"></div>

        <div class="p-8 sm:p-10 text-center">
            <!-- Icono de Telemedicina -->
            <div class="mx-auto w-20 h-20 bg-indigo-50 rounded-3xl flex items-center justify-center text-indigo-600 mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>

            <!-- Título Principal -->
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-2">Tu sala de videoconferencia</h1>
            <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">
                Tu enlace estará habilitado en el horario y fecha programados para conectarte de forma segura a la videollamada.
            </p>

            <!-- 👇 CONTENEDOR DEL RELOJ DE CUENTA REGRESIVA -->
            <div id="countdown-container" class="grid grid-cols-4 gap-2 max-w-xs mx-auto mb-8 bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50">
                <div class="text-center">
                    <span id="days" class="block text-2xl font-black text-indigo-900 leading-none">00</span>
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wide">Días</span>
                </div>
                <div class="text-center">
                    <span id="hours" class="block text-2xl font-black text-indigo-900 leading-none">00</span>
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wide">Horas</span>
                </div>
                <div class="text-center">
                    <span id="minutes" class="block text-2xl font-black text-indigo-900 leading-none">00</span>
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wide">Min</span>
                </div>
                <div class="text-center">
                    <span id="seconds" class="block text-2xl font-black text-indigo-600 leading-none">00</span>
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wide">Seg</span>
                </div>
            </div>

            <!-- Tarjeta con Datos de la Cita -->
            <div class="bg-slate-50 rounded-2xl p-5 text-left border border-slate-100 space-y-3 mb-8">
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-2.5">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Servicio</span>
                    <span class="text-sm font-bold text-slate-800">{{ $appointment->service->name }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-2.5">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Médico Especialista</span>
                    <span class="text-sm font-bold text-slate-700">Dr. {{ $appointment->doctor->user->name }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-2.5">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha Programada</span>
                    <span class="text-sm font-bold text-indigo-600">{{ $startTime->translatedFormat('l d \d\e F, Y') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hora Exacta</span>
                    <span class="text-sm font-black text-slate-900 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-xs">
                        {{ $startTime->format('g:i A') }} ({{ $appointment->duration }} min)
                    </span>
                </div>
            </div>

            <!-- Container del Botón (Se inyecta / modifica mediante Javascript) -->
            <div id="action-button-container">
                @if($isAvailable)
                    <a href="{{ $appointment->zoom_start_url ?? '#' }}" 
                       class="inline-flex justify-center items-center gap-2 w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest text-sm rounded-2xl shadow-lg shadow-indigo-100 active:scale-95 transition-all">
                        Ingresar a la videollamada ahora
                    </a>
                @else
                    <button disabled class="w-full py-4 bg-slate-200 text-slate-400 font-bold uppercase tracking-widest text-sm rounded-2xl cursor-not-allowed">
                        🔒 Videollamada Inactiva
                    </button>
                    <p id="helper-text" class="text-[11px] font-semibold text-slate-400 mt-3 flex items-center justify-center gap-1">
                        <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        El botón de acceso se habilitará automáticamente 15 minutos antes de tu cita.
                    </p>
                @endif
            </div>
        </div>

        <!-- Footer Informativo -->
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 text-center">
            <p class="text-[10px] text-slate-400 font-medium">
                Por favor, mantén esta pestaña abierta. Recuerda otorgar permisos de cámara y micrófono al ingresar.
            </p>
        </div>
    </div>

    <!-- 👇 JAVASCRIPT DEL RELOJ DE CUENTA REGRESIVA -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Pasamos las fechas desde PHP a formato ISO para JavaScript
            const activationTimestamp = new Date("{{ $startTime->copy()->subMinutes(15)->toIso8601String() }}").getTime();
            const meetingEndTimestamp = new Date("{{ $startTime->copy()->addMinutes($appointment->duration)->toIso8601String() }}").getTime();
            
            // 🔒 IMPORTANTE: Usamos 'meeting_link' que contiene la URL de invitado de Zoom para el paciente
            const patientZoomUrl = "{{ $appointment->meeting_link ?? '#' }}";

            const countdownInterval = setInterval(function () {
                const now = new Date().getTime();
                
                // Calculamos la distancia restante para la ACTIVACIÓN del botón (15 min antes)
                let distance = activationTimestamp - now;

                // 👇 CASO 1: EL TIEMPO ESTÁ CUMPLIDO (Estamos dentro del rango de la cita)
                if (now >= activationTimestamp && now <= meetingEndTimestamp) {
                    clearInterval(countdownInterval);
                    
                    // Seteamos el marcador del reloj a cero de forma visual
                    document.getElementById("days").innerText = "00";
                    document.getElementById("hours").innerText = "00";
                    document.getElementById("minutes").innerText = "00";
                    document.getElementById("seconds").innerText = "00";

                    // Reemplazamos el botón bloqueado por el botón activo con la URL real de Zoom
                    document.getElementById("action-button-container").innerHTML = `
                        <a href="${patientZoomUrl}" target="_blank" 
                        class="inline-flex justify-center items-center gap-2 w-full py-4 bg-purple-600 hover:bg-purple-700 text-white font-black uppercase tracking-widest text-sm rounded-2xl shadow-lg shadow-purple-100 active:scale-95 transition-all animate-pulse">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            Conectarse a Zoom ahora
                        </a>
                    `;
                    return;
                }

                // CASO 2: La hora de la cita ya terminó por completo
                if (now > meetingEndTimestamp) {
                    clearInterval(countdownInterval);
                    document.getElementById("countdown-container").classList.add("hidden");
                    document.getElementById("action-button-container").innerHTML = `
                        <button disabled class="w-full py-4 bg-red-100 text-red-500 font-bold uppercase tracking-widest text-sm rounded-2xl cursor-not-allowed">
                            ❌ Consulta Finalizada
                        </button>
                    `;
                    return;
                }

                // Conversión matemática para el reloj en cuenta regresiva (Aún no se cumple el tiempo)
                let days = Math.floor(distance / (1000 * 60 * 60 * 24));
                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Renderizamos los números con dos dígitos
                document.getElementById("days").innerText = String(days).padStart(2, '0');
                document.getElementById("hours").innerText = String(hours).padStart(2, '0');
                document.getElementById("minutes").innerText = String(minutes).padStart(2, '0');
                document.getElementById("seconds").innerText = String(seconds).padStart(2, '0');

            }, 1000); // Actualización cada 1 segundo
        });
    </script>
</body>
</html>
