<?php

/**
 * Landing "Doctores cerca de mí" (/doctores-cerca-de-mi).
 *
 * - La clave de cada ciudad es lo que va en el enlace de campaña: /doctores-cerca-de-mi?ciudad=cali
 * - Si ?ciudad= no existe o no viene, se usa la ciudad por defecto.
 * - lat/lng es el centro desde el cual se busca (radio en radius_km).
 * - Para quitar o agregar ciudades del selector, edita este arreglo.
 */
return [
    'default'   => 'cali',
    'radius_km' => 30,

    'cities' => [
        'cali'         => ['name' => 'Cali',         'lat' => 3.4516,  'lng' => -76.5320],
        'bogota'       => ['name' => 'Bogotá',       'lat' => 4.7110,  'lng' => -74.0721],
        'medellin'     => ['name' => 'Medellín',     'lat' => 6.2442,  'lng' => -75.5812],
        'barranquilla' => ['name' => 'Barranquilla', 'lat' => 10.9685, 'lng' => -74.7813],
        'cartagena'    => ['name' => 'Cartagena',    'lat' => 10.3910, 'lng' => -75.4794],
        'bucaramanga'  => ['name' => 'Bucaramanga',  'lat' => 7.1254,  'lng' => -73.1198],
        'pereira'      => ['name' => 'Pereira',      'lat' => 4.8133,  'lng' => -75.6961],
        'manizales'    => ['name' => 'Manizales',    'lat' => 5.0703,  'lng' => -75.5138],
        'cucuta'       => ['name' => 'Cúcuta',       'lat' => 7.8939,  'lng' => -72.5078],
        'santa-marta'  => ['name' => 'Santa Marta',  'lat' => 11.2408, 'lng' => -74.1990],
        'ibague'       => ['name' => 'Ibagué',       'lat' => 4.4389,  'lng' => -75.2322],
        'villavicencio'=> ['name' => 'Villavicencio','lat' => 4.1420,  'lng' => -73.6266],
    ],
];
