<?php

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
    ],

    'fallback' => 'medicina-general',
];