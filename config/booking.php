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
];
