<?php

return [
    // Días que el médico conserva su plan después del vencimiento antes de bajar al gratuito.
    'grace_days' => 5,

    // Planes de 6 meses o más: desde cuántos días antes del vencimiento se puede renovar (siempre suma desde el vencimiento).
    'renew_window_days' => 30,

    // Cuántos días antes del vencimiento se avisa por correo (una vez por aviso).
    'warn_days_annual' => [30, 7, 1],
    'warn_days_short' => [3],

    // SMS de aviso (además del correo): activar/desactivar y cuántos días antes del vencimiento.
    'sms_enabled' => true,
    'sms_days' => [7, 1],
];
