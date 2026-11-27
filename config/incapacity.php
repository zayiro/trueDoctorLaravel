<?php

/*
|--------------------------------------------------------------------------
| Incapacidad médica de OpenDoctor
|--------------------------------------------------------------------------
|
| REVISAR CON ABOGADO: todas las reglas de emisión y todos los textos (lo que dice el PDF y lo que ve
| quien verifica). No son asesoría legal. Cambiarlos aquí no requiere tocar el código.
|
| Marcadores en los textos:
|   {patient} {patient_id} {date} {time} {modality}     (los mismos de los certificados)
|   {days_words} {days} {day_word} {start} {end} {origin}
|
*/

return [

    // Duración máxima de UNA incapacidad (días calendario). Para más tiempo se emite una prórroga.
    'max_days' => 30,

    // CIE-10: si es true, el código debe existir en el catálogo (tabla cie10_codes). Solo se exige cuando el catálogo
    // está cargado (más de 100 códigos); mientras tenga solo los de ejemplo, se valida únicamente el formato.
    'cie10_require_catalog' => true,

    // La incapacidad puede empezar hasta estos días ANTES de la consulta (retroactiva, exige justificación)…
    'retroactive_max_days' => 30,
    // …y hasta estos días DESPUÉS.
    'future_start_max_days' => 2,
    // Caracteres mínimos de la justificación de una incapacidad retroactiva.
    'retroactive_reason_min' => 20,

    'origins' => [
        'common' => 'Enfermedad general (origen común)',
        'work'   => 'Accidente o enfermedad laboral (origen laboral)',
    ],

    'service_group' => 'Consulta externa',

    'modalities' => [
        'telemedicine' => 'Telemedicina',
        'in_person'    => 'Presencial (intramural)',
    ],

    'texts' => [

        // REVISAR CON ABOGADO
        'intro' => 'El(la) profesional de la salud identificado(a) en este documento CERTIFICA que atendió en consulta médica a {patient}{patient_id} el {date} a las {time}{modality} y le otorga INCAPACIDAD MÉDICA por {days_words} ({days}) {day_word} calendario, desde el {start} hasta el {end}, ambos días inclusive.',

        // REVISAR CON ABOGADO
        'diagnosis_note' => 'El diagnóstico se identifica con el código CIE-10 indicado. La descripción del diagnóstico hace parte de la historia clínica y no se incluye en este documento.',

        // REVISAR CON ABOGADO
        'extension_note' => 'Esta incapacidad es PRÓRROGA de la incapacidad con código {previous}.',

        // REVISAR CON ABOGADO
        'retroactive_note' => 'Incapacidad RETROACTIVA (inicia antes de la fecha de la consulta). Justificación del profesional: {reason}',

    ],
];
