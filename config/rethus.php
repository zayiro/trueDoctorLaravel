<?php

/**
 * Verificación profesional de los doctores en ReTHUS (Registro Único Nacional del Talento Humano en Salud).
 * La consulta pública de ReTHUS pide un captcha, así que la verifica una persona del equipo, a mano.
 * REVISAR CON ABOGADO: todos los textos de este archivo que ve el doctor o el público.
 */
return [
    // Página oficial de consulta (SISPRO). Se abre en una pestaña nueva desde el panel del administrador.
    'lookup_url' => 'https://web.sispro.gov.co/THS/Cliente/ConsultasPublicas/ConsultaPublicaDeTHxIdentificacion.aspx',

    // Tipos de documento que acepta el formulario. AJUSTAR para que coincida con el selector de ReTHUS.
    'document_types' => [
        'CC'  => 'Cédula de ciudadanía',
        'CE'  => 'Cédula de extranjería',
        'PA'  => 'Pasaporte',
        'PEP' => 'Permiso especial de permanencia (PEP)',
        'PPT' => 'Permiso por protección temporal (PPT)',
    ],

    // La verificación vence a los N meses y se pide renovarla (cada doctor vence en su propia fecha).
    'validity_months' => 12,
    // Días antes del vencimiento en que se avisa al doctor.
    'expiring_notice_days' => 30,

    // Motivos cuando no coincide (el doctor ve la etiqueta y la ayuda; la nota interna del admin NO se muestra).
    'mismatch_reasons' => [
        'not_found'  => [
            'label' => 'No encontramos tu registro en ReTHUS',
            'hint'  => 'Revisa que tu tipo de documento y tu número estén bien escritos en tu perfil y vuelve a solicitar la verificación.',
        ],
        'data_error' => [
            'label' => 'Los datos de tu perfil no coinciden con ReTHUS',
            'hint'  => 'Corrige tu documento o tu licencia en tu perfil (si están bloqueados, escríbenos a soporte) y vuelve a solicitar.',
        ],
        'inactive'   => [
            'label' => 'Tu registro en ReTHUS no aparece activo',
            'hint'  => 'Cuando tu registro esté activo en ReTHUS podrás volver a solicitar la verificación.',
        ],
        'profession' => [
            'label' => 'Tu profesión en ReTHUS no corresponde a médico',
            'hint'  => 'Si crees que es un error, escríbenos a soporte con tus datos y lo revisamos.',
        ],
    ],

    'texts' => [
        'badge'     => 'Registro verificado en ReTHUS',
        'incentive' => 'Verifica tu registro en ReTHUS y muestra a tus pacientes la insignia «Registro verificado en ReTHUS». Además, cuando esté disponible, podrás emitir incapacidades médicas desde tus consultas.',
        'disclaimer' => 'La verificación consiste en comprobar que tu registro aparece activo en la consulta pública de ReTHUS (Ministerio de Salud). No es una certificación del Ministerio ni de OpenDoctor sobre tu ejercicio profesional.',
    ],
];
