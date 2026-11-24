<?php

namespace App\Services\AiChat;

/** Textos del informe final (config/ai_chat_report_texts.php). Cae al español si falta la clave. */
class AiChatReportText
{
    public static function t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = in_array($locale, AiChatText::LOCALES, true) ? $locale : 'es';

        $text = config("ai_chat_report_texts.{$locale}.{$key}")
            ?? config("ai_chat_report_texts.es.{$key}")
            ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    /** Aviso de conservación con los días configurados en el admin. */
    public static function retention(?string $locale): string
    {
        return self::t('retention_notice', ['days' => max(1, AiChatSettings::int('ai_chat_retention_days'))], $locale);
    }

    /** Fecha larga en el idioma de la sesión y hora de Colombia. */
    public static function longDate(\Carbon\CarbonInterface $at, ?string $locale): string
    {
        return $at->copy()->timezone('America/Bogota')->locale($locale === 'en' ? 'en' : 'es')
            ->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y');
    }
}
