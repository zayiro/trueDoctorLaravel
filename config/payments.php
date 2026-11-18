<?php

return [
    // Proveedor activo. Es el "switch": hoy solo existe 'wompi'.
    'default' => env('PAYMENTS_GATEWAY', 'wompi'),

    'currency' => 'COP',

    // Interruptor de despliegue de la capa nueva (ver App\Payments\PaymentsFeature).
    'v2' => [
        'enabled'          => (bool) env('PAYMENTS_V2', false),
        // IDs de usuario separados por coma. Vacío + enabled = para todos.
        'allowed_user_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('PAYMENTS_V2_USER_IDS', ''))))),
    ],

    // Comando payments:reconcile (red de seguridad si se pierde un webhook).
    'reconcile' => [
        'enabled'       => (bool) env('PAYMENTS_RECONCILE', true),
        'after_minutes' => 5,
        'window_days'   => 7,
        'batch'         => 100,
    ],
];
