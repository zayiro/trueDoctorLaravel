<?php

/*
|--------------------------------------------------------------------------
| Certificados electrónicos de OpenDoctor
|--------------------------------------------------------------------------
|
| REVISAR CON ABOGADO: todos los textos de la sección 'texts' (lo que dice el
| PDF y lo que ve quien verifica) y las reglas de emisión. Los textos no son
| asesoría legal. Cambiarlos aquí no requiere tocar el código.
|
| Marcadores que se reemplazan en los textos:
|   {patient}  {patient_id}  {date}  {time}  {modality}  {url}  {code}
|
*/

return [

    // Disco donde se guardan los PDF firmados (privado, no público).
    'disk' => env('CERTIFICATES_DISK', 'private'),
    'directory' => 'certificates',

    // Prefijo del código de verificación: OD-XXXX-XXXX-XXXX
    'code_prefix' => 'OD',

    // Máximo de certificados vigentes (no anulados) por cita y por tipo.
    'max_active_per_appointment_and_type' => 3,

    // Zona horaria con la que se imprimen las fechas en el PDF y en la verificación.
    'display_timezone' => 'America/Bogota',

    'types' => [
        'attendance' => [
            'label' => 'Constancia de asistencia a consulta',
            'signed_type' => 'certificate_attendance',
        ],
        'medical' => [
            'label' => 'Certificado médico',
            'signed_type' => 'certificate_medical',
        ],
        'incapacity' => [
            'label' => 'Incapacidad médica',
            'signed_type' => 'incapacity',
        ],
    ],

    'texts' => [

        // REVISAR CON ABOGADO
        'attendance_body' => 'El(la) profesional de la salud identificado(a) en este documento CERTIFICA que {patient}{patient_id} fue atendido(a) en consulta médica el {date} a las {time}{modality}.',

        // REVISAR CON ABOGADO
        'medical_intro' => 'El(la) profesional de la salud identificado(a) en este documento CERTIFICA que atendió en consulta médica a {patient}{patient_id} el {date} a las {time}{modality}.',

        // REVISAR CON ABOGADO
        'diagnosis_label' => 'Diagnóstico (incluido por solicitud y con autorización expresa del paciente):',

        // REVISAR CON ABOGADO
        'no_diagnosis_note' => 'Este documento no incluye diagnóstico ni información de la historia clínica.',

        // REVISAR CON ABOGADO
        'declaration' => 'Declaración del profesional: certifico que atendí a la persona indicada en este documento y que la información aquí contenida es veraz.',

        // REVISAR CON ABOGADO
        'footer_verify' => 'Documento electrónico emitido a través de OpenDoctor. Verifique su autenticidad en {url} ingresando el código {code} o escaneando el código QR.',

        // REVISAR CON ABOGADO
        'footer_disclaimer' => 'La verificación confirma que el documento fue emitido a través de OpenDoctor y que no ha sido alterado desde su emisión. Que una entidad lo acepte para un trámite particular depende de la entidad que lo reciba.',

    ],
];
