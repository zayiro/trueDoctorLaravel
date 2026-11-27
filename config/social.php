<?php

/*
 | Redes sociales que se muestran como íconos en el pie de página (components/footer).
 | Para activar una red nueva solo pega su enlace completo en 'url'; mientras 'url' esté vacío (null) esa red no se muestra.
 | Íconos disponibles: facebook, instagram, tiktok (components/social-icon.blade.php).
 */

return [

    'links' => [
        'facebook' => [
            'label' => 'Facebook',
            'url' => 'https://www.facebook.com/profile.php?id=61596856657103',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'url' => null,
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'url' => null,
        ],
    ],

];
