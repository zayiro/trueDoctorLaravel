<?php

/*
 | Opiniones de la portada (sección «Lo que dicen nuestros pacientes»).
 |
 | De dónde salen:
 |   1) Las reseñas REALES publicadas en la plataforma (tabla reviews: publicadas, 4 o 5 estrellas, con comentario).
 |   2) Si hacen falta más, los testimonios de este archivo, que SOLO deben ser reales y con permiso de quien los dio
 |      (un mensaje de WhatsApp, un correo, una reseña en Google, etc.). No inventes opiniones ni nombres: es publicidad
 |      engañosa. Cada testimonio necesita 'consent' => true; sin eso no se muestra.
 |
 | Si entre las dos fuentes no se llega a 'min_to_show', la sección se oculta sola en vez de mostrar poco o relleno.
 |
 | Ejemplo (quita el comentario y rellena con una opinión real):
 |
 |   [
 |       'name'    => 'María G.',                    // nombre corto, como lo autorizó la persona
 |       'text'    => 'Texto textual de la opinión.',
 |       'rating'  => 5,                            // 1 a 5
 |       'label'   => 'Paciente de OpenDoctor',     // opcional
 |       'consent' => true,                          // confirmas que tienes su permiso para publicarla
 |   ],
 */

return [

    // Cuántas opiniones se muestran como máximo en el carrusel (se eligen al azar en cada carga)
    'limit' => 15,

    // Mínimo de opiniones para mostrar la sección
    'min_to_show' => 3,

    // Calificación mínima de las reseñas de la base de datos que se muestran
    'min_rating' => 4,

    // Largo mínimo del comentario de una reseña para mostrarla (evita «ok», «bien»)
    'min_chars' => 25,

    // Testimonios reales con permiso (vacío hasta que tengas los primeros)
    'items' => [],

];
