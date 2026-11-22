<?php

return [
    // Llave de CARTO Basemaps. Se define en .env como CARTO_API_KEY (nunca en el código).
    'carto_key' => env('CARTO_API_KEY'),

    // URL base de los tiles (sin llave). La llave se agrega en la vista.
    'tiles_url' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
];
