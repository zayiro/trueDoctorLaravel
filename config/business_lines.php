<?php

/*
 | Líneas de negocio del panel de Analytics (/administrator/analytics).
 |
 | Cada línea es un embudo de pasos. Un paso se mide de una de dos maneras:
 |   - 'event' => nombre del evento que el sitio envía a Google Analytics con gtag().
 |   - 'page'  => vistas de página (GA mide las páginas solo, sin código extra): ['exact' => '/ruta'] o
 |                ['prefix' => '/ruta/', 'exclude' => '/ruta/excepcion/'].
 | 'side' son eventos laterales que no son un paso del embudo (fallos, pendientes, reintentos).
 | 'conversion' compara dos pasos: 'to' puede ser un paso o 'db:<clave>' (dato real de la base de datos).
 | 'db' activa los números reales de la base de datos de esa línea (ver BusinessLineDb).
 |
 | Solo valores simples (sin funciones) para que funcione con config:cache.
 */

return [

    'appointments' => [
        'label' => 'Citas médicas',
        'emoji' => '📅',
        'color' => '#2563eb',
        'soft' => '#eff6ff',
        'funnel' => [
            ['key' => 'search_specialist', 'label' => 'Búsquedas de especialista', 'event' => 'search_specialist'],
            ['key' => 'view_item_list', 'label' => 'Listados de resultados vistos', 'event' => 'view_item_list'],
            ['key' => 'view_item', 'label' => 'Perfiles / sedes vistos', 'event' => 'view_item'],
            ['key' => 'begin_checkout', 'label' => 'Inicios de reserva', 'event' => 'begin_checkout'],
            ['key' => 'add_shipping_info', 'label' => 'Reservas con datos completos', 'event' => 'add_shipping_info'],
            ['key' => 'payment_processed', 'label' => 'Pagos aprobados', 'event' => 'payment_processed'],
            ['key' => 'view_confirmation', 'label' => 'Confirmaciones vistas', 'event' => 'view_confirmation'],
        ],
        'side' => [
            ['key' => 'payment_failed', 'label' => 'Pagos fallidos', 'event' => 'payment_failed'],
        ],
        'conversion' => ['from' => 'search_specialist', 'to' => 'payment_processed', 'label' => 'Búsqueda → Pago aprobado'],
        'db' => null,
        'notes' => [
            'El evento «purchase» no se usa aquí porque también lo envía el Análisis de laboratorio y mezclaría las dos líneas.',
        ],
    ],

    'lab_analysis' => [
        'label' => 'Análisis de laboratorio',
        'emoji' => '🧬',
        'color' => '#059669',
        'soft' => '#ecfdf5',
        'funnel' => [
            ['key' => 'view_medical_analysis_landing', 'label' => 'Visitas a la oferta', 'event' => 'view_medical_analysis_landing'],
            ['key' => 'start_medical_analysis', 'label' => 'Clics en «Analizar mis exámenes»', 'event' => 'start_medical_analysis'],
            ['key' => 'start_medical_analysis_upload', 'label' => 'Inicios de carga', 'event' => 'start_medical_analysis_upload'],
            ['key' => 'view_medical_analysis_upload', 'label' => 'Formulario de carga visto', 'event' => 'view_medical_analysis_upload'],
            ['key' => 'select_medical_files', 'label' => 'Archivos seleccionados', 'event' => 'select_medical_files'],
            ['key' => 'begin_medical_analysis', 'label' => 'Análisis solicitados', 'event' => 'begin_medical_analysis'],
            ['key' => 'lab_analysis_payment_processed', 'label' => 'Pagos aprobados', 'event' => 'lab_analysis_payment_processed'],
            ['key' => 'view_lab_analysis_report', 'label' => 'Informes vistos', 'event' => 'view_lab_analysis_report'],
        ],
        'side' => [
            ['key' => 'lab_analysis_payment_failed', 'label' => 'Pagos fallidos', 'event' => 'lab_analysis_payment_failed'],
            ['key' => 'lab_analysis_payment_pending', 'label' => 'Pagos pendientes', 'event' => 'lab_analysis_payment_pending'],
            ['key' => 'lab_analysis_payment_retry', 'label' => 'Reintentos de pago', 'event' => 'lab_analysis_payment_retry'],
        ],
        'conversion' => ['from' => 'view_medical_analysis_landing', 'to' => 'lab_analysis_payment_processed', 'label' => 'Visita a la oferta → Pago aprobado'],
        'db' => null,
        'notes' => [
            'Solo se cuentan los eventos que el sitio realmente envía a Google Analytics; la conversión se mide de la visita a la oferta al pago aprobado.',
        ],
    ],

    'skin_analysis' => [
        'label' => 'Análisis de piel',
        'emoji' => '🔬',
        'color' => '#db2777',
        'soft' => '#fdf2f8',
        'funnel' => [
            ['key' => 'landing', 'label' => 'Visitas a la página de análisis de piel', 'page' => ['exact' => '/skin-analysis']],
            ['key' => 'upload', 'label' => 'Pantalla de carga de la foto', 'page' => ['exact' => '/skin-analysis/upload']],
            ['key' => 'preview', 'label' => 'Vista previa de la foto', 'page' => ['prefix' => '/skin-analysis/preview/']],
            ['key' => 'payment', 'label' => 'Pantalla de pago', 'page' => ['prefix' => '/skin-analysis/payment/', 'exclude' => '/skin-analysis/payment/result/']],
            ['key' => 'result', 'label' => 'Informes vistos', 'page' => ['prefix' => '/skin-analysis/result/']],
        ],
        'side' => [],
        'conversion' => ['from' => 'landing', 'to' => 'db:paid', 'label' => 'Visita → Análisis pagado'],
        'db' => 'skin',
        'notes' => [
            'Las visitas salen de Google Analytics (solo cuentan quienes aceptan cookies). Los análisis pagados y los ingresos salen de la base de datos y son exactos.',
        ],
    ],

    'ai_chat' => [
        'label' => 'Chat IA de salud',
        'emoji' => '💬',
        'color' => '#7c3aed',
        'soft' => '#f5f3ff',
        'funnel' => [
            ['key' => 'landing', 'label' => 'Visitas a la página del chat', 'page' => ['exact' => '/chat-salud-ia']],
            ['key' => 'buy', 'label' => 'Elección de duración', 'page' => ['exact' => '/chat-salud-ia/comprar']],
            ['key' => 'data', 'label' => 'Pantalla de datos', 'page' => ['exact' => '/chat-salud-ia/datos']],
            ['key' => 'checkout', 'label' => 'Pantalla de pago', 'page' => ['prefix' => '/chat-salud-ia/pago/']],
            ['key' => 'purchased', 'label' => 'Compras confirmadas vistas', 'page' => ['prefix' => '/chat-salud-ia/compra/']],
            ['key' => 'session', 'label' => 'Entradas a la sesión de chat', 'page' => ['prefix' => '/chat-salud-ia/s/']],
        ],
        'side' => [],
        'conversion' => ['from' => 'landing', 'to' => 'db:paid', 'label' => 'Visita → Sesión pagada'],
        'db' => 'chat',
        'notes' => [
            'Las visitas salen de Google Analytics (solo cuentan quienes aceptan cookies). Las sesiones pagadas, los informes y los ingresos salen de la base de datos y son exactos.',
        ],
    ],

];
