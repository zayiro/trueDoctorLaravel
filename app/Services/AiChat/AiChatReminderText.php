<?php

namespace App\Services\AiChat;

/** Textos del recordatorio (config/ai_chat_reminder_texts.php). Cae al español si falta la clave. */
class AiChatReminderText
{
    public static function t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = in_array($locale, AiChatText::LOCALES, true) ? $locale : 'es';

        $text = config("ai_chat_reminder_texts.{$locale}.{$key}")
            ?? config("ai_chat_reminder_texts.es.{$key}")
            ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    /** Fecha corta del vencimiento en el idioma de la sesión y hora de Colombia. */
    public static function date(\Carbon\CarbonInterface $at, ?string $locale): string
    {
        return $at->copy()->timezone('America/Bogota')->locale($locale === 'en' ? 'en' : 'es')
            ->translatedFormat($locale === 'en' ? 'F j' : 'j \d\e F');
    }
}
