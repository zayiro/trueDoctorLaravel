<?php

/*
 * Registro de modelos de IA y sus fechas de retiro. ES EL ÚNICO ARCHIVO QUE SE ACTUALIZA A MANO.
 * Cuando llegue un correo de Anthropic/OpenAI de "model deprecation": agrega aquí la fecha (retires_at, formato AAAA-MM-DD)
 * y el reemplazo. El dashboard del admin avisa desde `warn_days` antes de la fecha.
 * Las fechas de Anthropic suelen venir como "tentativas": confírmalas en la página de deprecaciones.
 */
return [
    'last_reviewed' => '2026-11-23',
    'source_url' => 'https://platform.claude.com/docs/en/about-claude/model-deprecations',

    // Días antes del retiro desde los que el dashboard muestra el aviso amarillo.
    'warn_days' => 60,

    // Cada cuántos días se pregunta a las APIs si los modelos en uso siguen existiendo.
    'check_every_days' => 15,

    'models' => [
        'claude-sonnet-4-5' => ['label' => 'Claude Sonnet 4.5', 'retires_at' => '2026-11-30', 'replacement' => 'claude-sonnet-5-5'],
        'claude-opus-4-5'   => ['label' => 'Claude Opus 4.5',   'retires_at' => '2027-01-22', 'replacement' => 'claude-opus-5-5'],
        'claude-sonnet-4-6' => ['label' => 'Claude Sonnet 4.6'],
        'claude-sonnet-5-5' => ['label' => 'Claude Sonnet 5.5'],
        'claude-haiku-4-5'  => ['label' => 'Claude Haiku 4.5'],
        'claude-haiku-5-5'  => ['label' => 'Claude Haiku 5.5'],
        'gpt-4o'            => ['label' => 'GPT-4o (OpenAI)'],
    ],

    // Modelos que están escritos directamente en el código (no se configuran en el panel).
    'in_code' => [
        'claude-sonnet-5-5' => 'Análisis de piel (código)',
        'claude-sonnet-4-6' => 'Driver de Claude por defecto (código)',
    ],
];
