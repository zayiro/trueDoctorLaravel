<?php

/*
|--------------------------------------------------------------------------
| Datos fiscales (facturación electrónica)
|--------------------------------------------------------------------------
| Todo lo que el contador o el proveedor de facturación puedan cambiar vive aquí,
| para no tocar código: tipos de documento, cuáles aplican a cada rol y las
| responsabilidades fiscales que se ofrecen.
*/
return [

    // Por ahora la plataforma factura solo en Colombia.
    'country' => 'CO',

    // 'dian' es el código de referencia del tipo de documento; se confirma con el proveedor que se elija.
    // 'numeric' = solo dígitos.
    'document_types' => [
        'CC'  => ['label' => 'Cédula de ciudadanía',  'dian' => '13', 'numeric' => true],
        'CE'  => ['label' => 'Cédula de extranjería', 'dian' => '22', 'numeric' => true],
        'NIT' => ['label' => 'NIT',                   'dian' => '31', 'numeric' => true],
        'PA'  => ['label' => 'Pasaporte',             'dian' => '41', 'numeric' => false],
        'TI'  => ['label' => 'Tarjeta de identidad',  'dian' => '12', 'numeric' => true],
    ],

    // Tipos de documento que puede elegir cada rol.
    'allowed_by_role' => [
        'doctor'  => ['CC', 'CE', 'NIT', 'PA'],
        'clinic'  => ['NIT'],
        'patient' => ['CC', 'CE', 'NIT', 'PA', 'TI'],
    ],

    // Responsabilidades fiscales ofrecidas. PENDIENTE de confirmar con el contador.
    'tax_responsibilities' => [
        'no_responsable_iva' => 'No responsable de IVA',
        'responsable_iva'    => 'Responsable de IVA',
        'regimen_simple'     => 'Régimen Simple de Tributación (SIMPLE)',
    ],

    // Roles a los que se les exige indicar la responsabilidad fiscal (el paciente no la ve).
    'tax_required_roles' => ['doctor', 'clinic'],
];
