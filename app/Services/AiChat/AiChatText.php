<?php

namespace App\Services\AiChat;

/**
 * Textos del chat en español e inglés (config/ai_chat_texts.php).
 * Si falta una clave en el idioma pedido, cae al español; si tampoco existe, devuelve la clave.
 */
class AiChatText
{
    public const LOCALES = ['es', 'en'];

    public static function t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'es';

        $text = config("ai_chat_texts.{$locale}.{$key}")
            ?? config("ai_chat_texts.es.{$key}")
            ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }
}
