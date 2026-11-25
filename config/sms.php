<?php

return [
    // Proveedor que se usa si en Configuración no hay uno elegido. Debe existir en App\Sms\SmsManager::REGISTRY.
    'default' => env('SMS_PROVIDER', 'twilio'),
];
