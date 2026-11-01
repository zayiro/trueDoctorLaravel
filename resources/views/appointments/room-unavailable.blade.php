{{--
    resources/views/appointments/room-unavailable.blade.php

    Se muestra en la URL de la sala (/appointments/{id}/room) cuando la cita no tiene
    reunión de Zoom y no se pudo crear. Evita redirigir al usuario al dashboard sin explicación.

    Variables (las pasa AppointmentController::roomUnavailableView):
      $appointment, $reason, $isProfessional, $exitUrl, $autoRetry, $supportUrl
--}}
@php
    $startTime = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');
    $dateText  = \Carbon\Carbon::parse($appointment->date)->format('d/m/Y');

    $title = match ($reason) {
        'unpaid'      => 'Pago pendiente',
        'cancelled'   => 'Cita cancelada',
        'completed'   => 'Consulta finalizada',
        'expired'     => 'El horario de la cita ya pasó',
        'not_virtual' => 'Esta cita no es virtual',
        'throttled'   => 'Estamos preparando la sala',
        'failed'      => 'No pudimos preparar la sala',
        default       => 'Sala no disponible',
    };

    $message = match ($reason) {
        'unpaid' => $isProfessional
            ? 'El paciente aún no ha completado el pago de esta cita, por eso la videollamada todavía no está disponible.'
            : 'Tu pago todavía no está confirmado, por eso la videollamada aún no se ha creado. En cuanto se confirme podrás entrar a la sala.',
        'cancelled'   => 'Esta cita fue cancelada, por lo que no tiene sala de videollamada.',
        'completed'   => 'Esta consulta ya finalizó.',
        'expired'     => 'El horario de esta cita ya terminó y la sala de videollamada no llegó a crearse.',
        'not_virtual' => 'Esta cita es presencial; no tiene sala de videollamada.',
        'throttled'   => 'Estamos preparando la sala de videollamada. Intentaremos de nuevo automáticamente en unos segundos.',
        'failed'      => 'No pudimos crear la sala de videollamada en este momento. Ya registramos el problema y seguiremos intentando; si persiste, contacta a soporte indicando la referencia de la cita.',
        default       => 'No pudimos obtener las credenciales de la videollamada. Contacta a soporte indicando la referencia de la cita.',
    };

    // Colores en línea para no depender de que el CSS compilado incluya estas clases
    $isWaiting = in_array($reason, ['throttled', 'failed'], true);
    $iconBg    = $isWaiting ? '#fef3c7' : ($reason === 'unpaid' ? '#dbeafe' : '#fee2e2');
    $iconFg    = $isWaiting ? '#b45309' : ($reason === 'unpaid' ? '#1d4ed8' : '#b91c1c');
@endphp

<x-guest-layout
    meta_title="Sala de videollamada {{ $appointment->reference }} | OpenDoctorOnline"
    meta_description="Estado de la sala de videollamada de tu cita médica."
>
<div class="max-w-2xl mx-auto py-8 px-4 mt-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 text-center">

        <div class="mx-auto mb-4 flex items-center justify-center rounded-full"
             style="width:56px;height:56px;background:{{ $iconBg }};color:{{ $iconFg }};">
            @if ($isWaiting)
                <svg class="animate-spin" style="width:28px;height:28px" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            @else
                <svg style="width:28px;height:28px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                </svg>
            @endif
        </div>

        <h1 class="text-lg md:text-xl font-bold text-gray-800">{{ $title }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>

        <div class="mt-5 inline-block text-left text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-lg px-4 py-3">
            <p>Referencia: <span class="font-semibold text-gray-700">{{ $appointment->reference }}</span></p>
            <p>Fecha: <span class="font-medium text-gray-700">{{ $dateText }} · {{ $startTime }}</span></p>
            @if ($appointment->patient?->user?->name)
                <p>Paciente: <span class="font-medium text-gray-700">{{ $appointment->patient->user->name }}</span></p>
            @endif
        </div>

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-2">
            @if ($reason === 'unpaid' && !$isProfessional)
                <a href="{{ route('appointments.preview', ['id' => $appointment->id]) }}"
                   class="px-4 py-2 text-sm font-medium rounded-lg text-white"
                   style="background:#2563eb;">
                    Completar pago
                </a>
            @endif

            @if (!in_array($reason, ['cancelled', 'completed', 'expired', 'not_virtual', 'unpaid'], true))
                <a href="{{ url()->current() }}"
                   class="px-4 py-2 text-sm font-medium rounded-lg text-white"
                   style="background:#2563eb;">
                    Reintentar
                </a>
            @endif

            @if ($supportUrl)
                <a href="{{ $supportUrl }}" target="_blank" rel="noopener"
                   class="px-4 py-2 text-sm font-medium rounded-lg bg-green-50 text-green-700 hover:bg-green-100">
                    Contactar a soporte
                </a>
            @endif

            <a href="{{ $exitUrl }}"
               class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                Volver
            </a>
        </div>

        @if ($autoRetry)
            <p id="retry-note" class="mt-4 text-xs text-gray-400" hidden>
                Reintentando automáticamente en <span id="retry-count">15</span> s…
            </p>
            <script>
                (function () {
                    var key = 'room_retry_{{ $appointment->id }}';
                    var max = 6; // 6 reintentos automáticos como máximo
                    var done = parseInt(sessionStorage.getItem(key) || '0', 10);
                    if (done >= max) return;

                    var note = document.getElementById('retry-note');
                    var out  = document.getElementById('retry-count');
                    var left = 15;
                    note.hidden = false;
                    out.textContent = left;

                    var timer = setInterval(function () {
                        left--;
                        out.textContent = left;
                        if (left <= 0) {
                            clearInterval(timer);
                            sessionStorage.setItem(key, String(done + 1));
                            window.location.reload();
                        }
                    }, 1000);
                })();
            </script>
        @endif
    </div>
</div>
</x-guest-layout>