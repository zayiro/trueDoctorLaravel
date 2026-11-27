<?php

/**
 * Protección contra la creación masiva de cuentas en los formularios públicos de registro.
 * Todo se puede ajustar desde el .env sin tocar código.
 */
return [
    // Apaga toda la protección (por si algo saliera mal): SIGNUP_PROTECTION=false
    'enabled' => env('SIGNUP_PROTECTION', true),

    // POST protegidos (rutas sin la barra inicial, separadas por coma). Cada formulario debe tener @honeypot.
    'protected_paths' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'SIGNUP_PROTECTED_PATHS',
        'register,register-partner,register-clinic,appointments/process-patient,chat-salud-ia/datos'
    ))))),

    // Redes (CIDR, separadas por coma) a las que NO se les permite crear cuentas. Solo afecta a esos formularios.
    // 2.26.150.0/24 = red desde la que llegaron los registros falsos del 26/11/2026.
    'blocked_networks' => array_values(array_filter(array_map('trim', explode(',', (string) env('SIGNUP_BLOCKED_NETWORKS', '2.26.150.0/24'))))),

    // Cuentas CREADAS por hora, por IP y por red (/24 en IPv4). Los intentos con errores y los inicios de sesión no cuentan.
    'max_per_ip_per_hour' => (int) env('SIGNUP_MAX_PER_IP_HOUR', 3),
    'max_per_network_per_hour' => (int) env('SIGNUP_MAX_PER_NET_HOUR', 4),

    // Mensaje amable (nunca una página en blanco ni un error crudo).
    'message' => 'No pudimos completar tu registro en este momento. Inténtalo de nuevo en unos minutos; si el problema continúa, escríbenos desde «Contáctanos».',
];
