<?php

return [
    // Zona horaria del negocio. TODAS las reglas de disponibilidad se evalúan en esta zona.
    'timezone' => env('BOOKING_TIMEZONE', 'America/Bogota'),

    // Valores por defecto cuando el doctor o la clínica no tienen configuración propia.
    'default_min_notice_hours' => 2,
    'default_max_advance_days' => 30,
    'default_buffer_minutes' => 0,
    'fallback_duration_minutes' => 20,

    // Una cita pendiente de pago bloquea el turno este tiempo; después se libera.
    'pending_hold_minutes' => 120,

    // Cuántos días hacia adelante busca el "próximo turno".
    'next_slot_window_days' => 30,

    // Interruptores de despliegue por fases (todos apagados hasta validar cada fase).
    'new_availability' => [
        'search' => env('BOOKING_NEW_SEARCH', false),
        'profile' => env('BOOKING_NEW_PROFILE', false),
        'slots_web' => env('BOOKING_NEW_SLOTS_WEB', false),
        'slots_api' => env('BOOKING_NEW_SLOTS_API', false),
        'booking_validation' => env('BOOKING_NEW_VALIDATION', false),
    ],

    // Modo sombra: calcula con el motor nuevo y registra las diferencias, sin cambiar lo que ve el usuario.
    'shadow' => env('BOOKING_SHADOW', false),
];