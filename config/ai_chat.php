<?php

/*
|--------------------------------------------------------------------------
| Chat de Salud con IA de OpenDoctor — configuración base
|--------------------------------------------------------------------------
| Los valores de "defaults" son los de arranque: el admin los cambia desde
| "Chat de Salud IA" y quedan en la tabla `settings` (grupo ai_chat).
| Para ofrecer un modelo nuevo en los selectores, agrégalo en "models".
| Las claves de API NO van aquí ni en el admin: siguen en .env / services.
*/

return [

    // Duraciones de sesión que se venden (minutos).
    'durations' => [20, 30],

    // Versión de los términos que acepta el paciente (se guarda en cada sesión).
    'terms_version' => '2026-11-v1',

    // Referencia para mostrar un aproximado en dólares a quien compra en inglés.
    'fx_cop_per_usd' => 4000,

    // Indicativos de país que acepta el formulario (los mismos de las citas).
    'country_codes' => [
        '+57' => '🇨🇴 +57', '+54' => '🇦🇷 +54', '+591' => '🇧🇴 +591', '+55' => '🇧🇷 +55',
        '+56' => '🇨🇱 +56', '+593' => '🇪🇨 +593', '+595' => '🇵🇾 +595', '+51' => '🇵🇪 +51',
        '+598' => '🇺🇾 +598', '+58' => '🇻🇪 +58',
    ],

    // Modelos que el admin puede elegir (valor guardado => datos).
    'models' => [
        'claude-sonnet-5-5' => ['label' => 'Claude Sonnet 5.5 (Anthropic)', 'provider' => 'anthropic'],
        'claude-haiku-5-5'  => ['label' => 'Claude Haiku 5.5 (Anthropic · económico)', 'provider' => 'anthropic'],
        'gpt-4o'            => ['label' => 'GPT-4o (OpenAI)', 'provider' => 'openai'],
    ],

    // Valores de arranque (clave en `settings` => valor).
    'defaults' => [
        'ai_chat_price_20'        => 9900,   // neto para OpenDoctor, COP (el cliente paga además la comisión)
        'ai_chat_price_30'        => 13900,
        'ai_chat_wompi_percent'   => 2.65,   // % de Wompi con tarjeta (plan avanzado); PCOL cuesta más
        'ai_chat_wompi_fixed_fee' => 700,    // parte fija por transacción de Wompi, COP (confirmar con el contrato)
        'ai_chat_wompi_iva_percent' => 19,   // IVA que Wompi cobra sobre su comisión
        'ai_chat_max_messages_20' => 25,
        'ai_chat_max_messages_30' => 40,
        'ai_chat_max_chars'       => 1000,
        'ai_chat_max_reply_words' => 300,
        'ai_chat_offtopic_limit'  => 3,
        'ai_chat_expiry_days'     => 7,
        'ai_chat_retention_days'  => 30,
        'ai_chat_nudge_seconds'   => 90,
        'ai_chat_id_max_attempts' => 5,
        'ai_chat_sales_paused'    => 0,
        'ai_chat_model_chat'      => 'claude-sonnet-5-5',
        'ai_chat_model_report'    => 'claude-sonnet-5-5',
        'ai_chat_model_fallback'  => 'gpt-4o',
    ],
];
