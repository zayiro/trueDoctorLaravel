<?php

declare(strict_types=1);

namespace App\Services\AiModels;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Qué modelos de IA usa OpenDoctor y cuáles están por retirarse o ya no responden.
 * Lee config/ai_models.php (fechas) y el resultado de la última comprobación real (comando ai-models:check).
 */
class AiModelMonitor
{
    public const LIVE_KEY = 'ai_models_live_check';

    /** Ajustes del panel que guardan un modelo => dónde se usa. */
    private const USAGE_SETTINGS = [
        'lab_ai_primary_model' => 'Exámenes de laboratorio (principal)',
        'lab_ai_fallback_model' => 'Exámenes de laboratorio (respaldo)',
        'imaging_ai_primary_model' => 'Exámenes de imágenes (principal)',
        'imaging_ai_fallback_model' => 'Exámenes de imágenes (respaldo)',
        'ai_chat_model_chat' => 'Chat de Salud IA (conversación)',
        'ai_chat_model_report' => 'Chat de Salud IA (reporte)',
        'ai_chat_model_fallback' => 'Chat de Salud IA (respaldo)',
    ];

    /** Id sin sufijo de fecha: claude-sonnet-4-5-20250929 → claude-sonnet-4-5 */
    public static function normalize(string $id): string
    {
        return (string) preg_replace('/-\d{8}$/', '', trim($id));
    }

    /** @return array<string, string[]> modelo => lugares donde se usa */
    public function usage(): array
    {
        $usage = [];
        $add = function (?string $id, string $place) use (&$usage) {
            $id = self::normalize((string) $id);
            if ($id !== '') {
                $usage[$id][] = $place;
            }
        };

        foreach (Setting::whereIn('key', array_keys(self::USAGE_SETTINGS))->pluck('value', 'key') as $key => $value) {
            $add($value, self::USAGE_SETTINGS[$key]);
        }

        $add(config('services.anthropic.vision_model'), 'Exámenes: modelo real de Claude (.env)');
        $add(config('services.openai.vision_model'), 'Exámenes: modelo de OpenAI (.env)');

        foreach ((array) config('ai_models.in_code', []) as $id => $place) {
            $add($id, $place);
        }

        return array_map(fn ($places) => array_values(array_unique($places)), $usage);
    }

    /** @return array{checked_at: ?string, missing: string[], results: array<string,string>} */
    public function liveCheck(): array
    {
        $raw = Setting::where('key', self::LIVE_KEY)->value('value');
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return [
            'checked_at' => $data['checked_at'] ?? null,
            'missing' => array_map([self::class, 'normalize'], (array) ($data['missing'] ?? [])),
            'results' => (array) ($data['results'] ?? []),
        ];
    }

    /**
     * Avisos activos, los más graves primero. Parámetros opcionales para pruebas.
     *
     * @return array<int, array{id:string,label:string,places:string[],status:string,days:?int,retires_at:?string,replacement:?string}>
     */
    public function alerts(?Carbon $today = null, ?array $usage = null, ?array $live = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $usage ??= $this->usage();
        $live ??= $this->liveCheck();
        $warn = (int) config('ai_models.warn_days', 60);
        $alerts = [];

        foreach ($usage as $id => $places) {
            $info = config("ai_models.models.{$id}", []);
            $label = $info['label'] ?? $id;
            $status = null;
            $days = null;
            $date = $info['retires_at'] ?? null;

            if ($date) {
                $days = (int) $today->diffInDays(Carbon::parse($date)->startOfDay(), false);
                if ($days <= 0) {
                    $status = 'expired';
                } elseif ($days <= $warn) {
                    $status = 'soon';
                }
            }

            if ($status === null && in_array($id, $live['missing'], true)) {
                $status = 'missing';
            }

            if ($status !== null) {
                $alerts[] = [
                    'id' => $id,
                    'label' => $label,
                    'places' => $places,
                    'status' => $status,
                    'days' => $days,
                    'retires_at' => $date,
                    'replacement' => $info['replacement'] ?? null,
                ];
            }
        }

        $order = ['expired' => 0, 'missing' => 1, 'soon' => 2];
        usort($alerts, fn ($a, $b) => [$order[$a['status']], $a['days'] ?? 0] <=> [$order[$b['status']], $b['days'] ?? 0]);

        return $alerts;
    }
}
