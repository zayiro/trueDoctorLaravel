<?php

/*
 | Limpieza de cuentas de paciente que nunca se usaron (creadas al reservar, al comprar el Chat IA o al registrarse,
 | pero sin ninguna cita real, compra, historia clínica ni regreso).
 |
 | Los plazos son fijos a propósito (sin interruptor en el .env). Para ver qué haría sin tocar nada:
 |     php artisan accounts:cleanup --reporte
 */
return [
    // Se avisa a los 13 días sin actividad y se elimina al día 14 (mínimo 23 horas después del aviso).
    'warn_after_days' => 13,
    'delete_after_days' => 14,
    'hours_between_warning_and_deletion' => 23,

    // Máximo de avisos por corrida diaria (protege el límite de correos del servidor).
    'max_warnings_per_run' => 30,

    // Un ingreso cuenta como «volvió de verdad» solo si ocurre al menos estos minutos después de crear la cuenta
    // (la reserva inicia sesión sola al crearla).
    'return_grace_minutes' => 10,

    // Vigencia del enlace «Conservar mi cuenta».
    'keep_link_days' => 3,

    // Las citas vencidas sin pagar de una cuenta eliminada se conservan (auditoría) a nombre de este paciente anónimo.
    'placeholder_email' => 'paciente-eliminado@opendoctor.invalid',
    'placeholder_name' => '[Paciente eliminado]',

    // Si el paciente llenó alguno de estos datos de su perfil, la cuenta NO se elimina.
    'profile_columns' => [
        'blood_type', 'birth_date', 'gender', 'weight', 'height', 'insurance_id', 'permanent_conditions',
        'department_id', 'city_id', 'residence_zone', 'occupation', 'civil_status', 'ethnicity',
        'affiliation_type', 'regime_type', 'sisben_level',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
    ],

    // REVISAR CON ABOGADO: textos del correo de aviso y de la página del enlace.
    'texts' => [
        'subject' => 'Tu cuenta de OpenDoctor se eliminará pronto si no la conservas',
        'intro' => 'Creaste una cuenta en OpenDoctor, pero no llegaste a completar ninguna reserva ni volviste a entrar. Para proteger tus datos personales, en aproximadamente 24 horas eliminaremos tu cuenta y lo que dejaste en ella (nombre, correo, documento y teléfono).',
        'cta_intro' => '¿Quieres conservarla? Pulsa el botón y no se eliminará.',
        'cta' => 'Conservar mi cuenta',
        'link_note' => 'El botón funciona durante :days días.',
        'ignore' => 'Si ya no la necesitas, no tienes que hacer nada. Puedes volver a registrarte cuando quieras.',
        'kept_title' => 'Listo, conservamos tu cuenta',
        'kept_text' => 'Gracias por avisarnos. Tu cuenta sigue activa. Si aún no creaste una contraseña, entra a «¿Olvidaste tu contraseña?» con tu correo y te enviamos un enlace.',
        'invalid_title' => 'Este enlace ya no está disponible',
        'invalid_text' => 'Puede que haya vencido o que la cuenta ya se haya eliminado por falta de uso. Si quieres usar OpenDoctor, puedes registrarte de nuevo cuando quieras.',
        'home' => 'Ir a OpenDoctor',
    ],
];
