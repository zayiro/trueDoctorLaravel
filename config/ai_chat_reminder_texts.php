<?php

/* Textos del recordatorio de sesión sin empezar (correo y SMS), en español e inglés. */
return [
    'es' => [
        'brand' => 'Chat de Salud con IA de OpenDoctor',
        'mail_subject' => 'Tu sesión del Chat de Salud con IA vence pronto',
        'mail_heading' => 'Tu sesión te está esperando',
        'mail_body' => 'Te recordamos que tu sesión de :min minutos aún no ha empezado y puedes usarla hasta el :date. El tiempo solo corre cuando pulses «Empezar», así que puedes elegir un momento tranquilo.',
        'mail_button' => 'Abrir mi sesión',
        'mail_privacy' => 'Este correo no contiene datos médicos. No compartas el enlace.',
        'mail_disclaimer' => 'El chat es un asistente de IA: no es un médico y no reemplaza una consulta. En una emergencia llama a tu línea local de emergencias.',
        'sms' => 'OpenDoctor: tu sesion de Chat de Salud IA vence el :date. El tiempo corre al pulsar Empezar. Entra: :url',
    ],
    'en' => [
        'brand' => 'OpenDoctor AI Health Chat',
        'mail_subject' => 'Your AI Health Chat session expires soon',
        'mail_heading' => 'Your session is waiting for you',
        'mail_body' => 'A reminder that your :min-minute session has not started yet and you can use it until :date. The clock only runs once you press “Start”, so you can pick a quiet moment.',
        'mail_button' => 'Open my session',
        'mail_privacy' => 'This email contains no medical data. Please do not share the link.',
        'mail_disclaimer' => 'The chat is an AI assistant: it is not a doctor and does not replace a consultation. In an emergency, call your local emergency number.',
        'sms' => 'OpenDoctor: your AI Health Chat session expires :date. Time only runs when you press Start. Open: :url',
    ],
];
