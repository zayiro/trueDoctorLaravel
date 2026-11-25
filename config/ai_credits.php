<?php

// Llaves de ADMINISTRADOR (solo lectura de costos). Son opcionales: sin ellas Claude y OpenAI
// muestran el último saldo registrado a mano. No sirven para llamar a los modelos.
return [
    'anthropic_admin_key' => env('ANTHROPIC_ADMIN_KEY'),
    'openai_admin_key' => env('OPENAI_ADMIN_KEY'),
    // Horas sin chequeo a partir de las cuales el dashboard marca el dato como desactualizado.
    'stale_hours' => 3,
    // A dónde llegan los avisos por correo. Si no se define, se usa MAIL_ADMIN_ADDRESS.
    'alerts_address' => env('MAIL_ALERTS_ADDRESS'),
];
