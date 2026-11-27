<?php

/*
 | Borrado de pacientes desde el panel de administración.
 |
 | Un paciente que tenga alguno de estos datos NO se puede eliminar desde el panel (se puede desactivar):
 | son información que debe conservarse. REVISAR CON ABOGADO (normativa de historia clínica y de protección de datos).
 | Formato: [tabla, columna con el id del paciente, nombre que se muestra al administrador]
 */
return [
    'protected' => [
        ['patient_histories', 'patient_id', 'historia clínica'],
        ['patient_history_attachments', 'patient_id', 'archivos de la historia clínica'],
        ['patient_allergies', 'patient_id', 'alergias registradas'],
        ['patient_family_histories', 'patient_id', 'antecedentes familiares'],
        ['patient_medications', 'patient_id', 'medicamentos registrados'],
        ['patient_surgeries', 'patient_id', 'cirugías registradas'],
        ['certificates', 'patient_id', 'certificados médicos'],
        ['signed_documents', 'patient_id', 'documentos firmados'],
        ['patient_consents', 'patient_id', 'consentimientos informados'],
        ['patient_access_logs', 'patient_id', 'registro de accesos a sus datos'],
    ],

    // REVISAR CON ABOGADO
    'blocked_message' => 'No se puede eliminar a :name porque tiene :reasons. Esa información debe conservarse. Puedes desactivar su cuenta con el botón de activar/desactivar.',
];
