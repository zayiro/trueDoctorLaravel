<?php
// se usa en la IA para sugerir la especialidad que se le debe mostrar al usuario en los examenes medicos(medicalAnalysis)
// despues de agregar una especialidad verificar que exista en la tabla specialties y luego agregarla a este array
// luego recargar el congif php artisan config:clear

return [
    // Slugs que la IA puede sugerir (todos deben existir en la tabla specialties)
    'allowed' => [
        'medicina-general',
        'medicina-familiar',
        'internista',
        'endocrinologia',
        'cardiologia',
        'nefrologia',
        'gastroenterologia',
        'hepatologia',
        'hematologia',
        'neumologia',
        'reumatologia',
        'neurologia',
        'dermatologia',
        'ginecologia',
        'urologia',
        'infectologia',
        'oncologo',
        'nutricionista',
        'pediatria',
        'psiquiatria',
        'neuropediatria',
        'anestesiologia',
        'optometria',
    ],

    'fallback' => 'medicina-general',
];