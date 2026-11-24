<?php

namespace App\Services\AiChat;

/** Nombre visible del enfoque de la conversación (slug de especialidad → texto en el idioma de la sesión). */
class AiChatSpecialties
{
    private const NAMES = [
        'medicina-general' => ['Medicina general', 'General medicine'],
        'medicina-familiar' => ['Medicina familiar', 'Family medicine'],
        'internista' => ['Medicina interna', 'Internal medicine'],
        'endocrinologia' => ['Endocrinología', 'Endocrinology'],
        'cardiologia' => ['Cardiología', 'Cardiology'],
        'nefrologia' => ['Nefrología', 'Nephrology'],
        'gastroenterologia' => ['Gastroenterología', 'Gastroenterology'],
        'hepatologia' => ['Hepatología', 'Hepatology'],
        'hematologia' => ['Hematología', 'Hematology'],
        'neumologia' => ['Neumología', 'Pulmonology'],
        'reumatologia' => ['Reumatología', 'Rheumatology'],
        'neurologia' => ['Neurología', 'Neurology'],
        'dermatologia' => ['Dermatología', 'Dermatology'],
        'ginecologia' => ['Ginecología', 'Gynecology'],
        'urologia' => ['Urología', 'Urology'],
        'infectologia' => ['Infectología', 'Infectious diseases'],
        'oncologo' => ['Oncología', 'Oncology'],
        'nutricionista' => ['Nutrición', 'Nutrition'],
        'pediatria' => ['Pediatría', 'Pediatrics'],
        'psiquiatria' => ['Salud mental', 'Mental health'],
        'neuropediatria' => ['Neuropediatría', 'Pediatric neurology'],
        'anestesiologia' => ['Anestesiología', 'Anesthesiology'],
        'optometria' => ['Optometría', 'Optometry'],
    ];

    public static function slugs(): array
    {
        return array_keys(self::NAMES);
    }

    public static function isValid(?string $slug): bool
    {
        return $slug !== null && isset(self::NAMES[$slug]);
    }

    public static function label(?string $slug, ?string $locale = 'es'): ?string
    {
        if (! self::isValid($slug)) {
            return null;
        }

        return self::NAMES[$slug][$locale === 'en' ? 1 : 0];
    }
}
