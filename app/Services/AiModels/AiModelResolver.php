<?php

declare(strict_types=1);

namespace App\Services\AiModels;

use App\Models\Setting;

/**
 * ÚNICA fuente de verdad de los modelos de IA de OpenDoctor.
 *
 * Orden de prioridad (siempre el mismo):
 *   1) Panel del admin (tabla `settings`) — solo se acepta un modelo de la lista permitida.
 *   2) .env / config (services.anthropic.vision_model, services.openai.vision_model).
 *   3) Valor por defecto escrito aquí.
 *
 * Nadie más debe escribir un nombre de modelo en el código. El Chat de Salud IA tiene su propio panel
 * (AiChatSettings) y no pasa por aquí.
 */
class AiModelResolver
{
    /** Modelos que el panel permite elegir para exámenes (valor guardado => nombre). */
    public const EXAM_OPTIONS = [
        'gpt-4o' => 'GPT-4o (OpenAI)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
    ];

    /** Modelos que el panel permite elegir para el análisis de piel (solo Claude: usa el SDK de Anthropic). */
    public const SKIN_OPTIONS = [
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
        'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
    ];

    public const SKIN_KEY = 'skin_ai_model';
    public const SKIN_DEFAULT = 'claude-sonnet-5-5';

    private const GROUP_LABELS = ['lab' => 'laboratorio', 'imaging' => 'imágenes'];
    private const ROLE_LABELS = ['primary' => 'principal', 'fallback' => 'respaldo'];

    /** Valor del panel, o null si no existe, está vacío o la tabla no responde. */
    private static function panel(string $key): ?string
    {
        try {
            $value = Setting::where('key', $key)->value('value');
        } catch (\Throwable) {
            return null;
        }

        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /** Valor del panel solo si está en la lista permitida. */
    private static function allowedPanel(string $key, array $options): ?string
    {
        $value = self::panel($key);

        return ($value !== null && array_key_exists($value, $options)) ? $value : null;
    }

    /** Modelo efectivo de un grupo de exámenes ('lab' | 'imaging') y rol ('primary' | 'fallback'). */
    public static function examModel(string $group, string $role): string
    {
        $fromPanel = self::allowedPanel("{$group}_ai_{$role}_model", self::EXAM_OPTIONS);

        if ($fromPanel !== null) {
            return $fromPanel;
        }

        // Sin valor válido en el panel: el principal cae a Claude y el respaldo a OpenAI (como siempre).
        return $role === 'primary'
            ? (string) config('services.anthropic.vision_model', 'claude-sonnet-5-5')
            : (string) config('services.openai.vision_model', 'gpt-4o');
    }

    /** Proveedor que sirve un modelo. */
    public static function providerFor(string $model): string
    {
        $model = strtolower(trim($model));

        return match (true) {
            str_starts_with($model, 'claude') => 'claude',
            str_starts_with($model, 'gpt-'), str_starts_with($model, 'o1'), str_starts_with($model, 'o3'), str_starts_with($model, 'o4') => 'openai',
            str_starts_with($model, 'medgemma') => 'medgemma',
            default => 'claude',
        };
    }

    /**
     * Plan de un análisis de exámenes: proveedores en orden y el modelo exacto de cada uno.
     *
     * @return array{order: string[], models: array<string,string>}
     */
    public static function examPlan(string $group): array
    {
        $order = [];
        $models = [];

        foreach (['primary', 'fallback'] as $role) {
            $model = self::examModel($group, $role);
            $provider = self::providerFor($model);

            if (! isset($models[$provider])) {
                $models[$provider] = $model;
                $order[] = $provider;
            }
        }

        return ['order' => $order, 'models' => $models];
    }

    /** Modelo efectivo del análisis de piel (informe y validación de la foto). */
    public static function skinModel(): string
    {
        return self::allowedPanel(self::SKIN_KEY, self::SKIN_OPTIONS) ?? self::SKIN_DEFAULT;
    }

    /**
     * Lo que realmente se llama hoy. Lo usa el monitor de retiros del dashboard.
     *
     * @return array<int, array{key:string, place:string, model:string, source:string}>
     */
    public static function effective(): array
    {
        $rows = [];

        foreach (self::GROUP_LABELS as $group => $groupLabel) {
            foreach (self::ROLE_LABELS as $role => $roleLabel) {
                $key = "{$group}_ai_{$role}_model";
                $rows[] = [
                    'key' => $key,
                    'place' => "Exámenes de {$groupLabel} ({$roleLabel})",
                    'model' => self::examModel($group, $role),
                    'source' => self::allowedPanel($key, self::EXAM_OPTIONS) !== null ? 'panel' : 'defecto',
                ];
            }
        }

        $rows[] = [
            'key' => self::SKIN_KEY,
            'place' => 'Análisis de piel',
            'model' => self::skinModel(),
            'source' => self::allowedPanel(self::SKIN_KEY, self::SKIN_OPTIONS) !== null ? 'panel' : 'defecto',
        ];

        return $rows;
    }
}
