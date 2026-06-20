<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIVisionDriver;
use App\Services\AI\Drivers\ClaudeDriver;
use App\Services\AI\Drivers\OpenAIDriver;
use Illuminate\Support\Facades\Log;

class AIVisionManager
{
    /**
     * Crea el driver indicado (o el default de config) sin ejecutar nada todavía.
     *
     * Uso:
     *   AIVisionManager::driver('claude')->analyzeImages(...)
     *   AIVisionManager::driver('openai', 'gpt-5.4-mini')->analyzeImages(...)
     *   AIVisionManager::driver()->analyzeImages(...)  // usa el default de config/services.php
     */
    public static function driver(?string $provider = null, ?string $model = null): AIVisionDriver
    {
        $provider = $provider ?? config('services.ai_vision.default', 'openai');

        return match ($provider) {
            'openai' => new OpenAIDriver(
                apiKey: config('services.openai.key'),
                model: $model ?? config('services.openai.vision_model', 'gpt-5.4'),
            ),
            'claude' => new ClaudeDriver(
                apiKey: config('services.anthropic.key'),
                model: $model ?? config('services.anthropic.vision_model', 'claude-sonnet-4-6'),
            ),
            default => throw new \InvalidArgumentException("Proveedor de IA desconocido: {$provider}"),
        };
    }

    /**
     * Ejecuta el análisis con un proveedor primario y, si falla, intenta
     * automáticamente con un proveedor de respaldo (fallback entre proveedores).
     *
     * @param  string[]  $providerOrder  Ej. ['claude', 'openai'] — intenta en ese orden
     * @return array{result: array, provider_used: string}
     *
     * @throws \RuntimeException si TODOS los proveedores fallan
     */
    public static function analyzeWithFallback(
        string $systemPrompt,
        string $userText,
        array $images,
        array $providerOrder = ['openai', 'claude']
    ): array {
        $errors = [];

        foreach ($providerOrder as $provider) {
            try {
                $driver = self::driver($provider);
                $result = $driver->analyzeImages($systemPrompt, $userText, $images);

                return [
                    'result' => $result,
                    'provider_used' => $driver->name(),
                ];
            } catch (\Throwable $e) {
                Log::warning("AIVisionManager: proveedor '{$provider}' falló, probando siguiente. Error: " . $e->getMessage());
                $errors[$provider] = $e->getMessage();
                continue;
            }
        }

        throw new \RuntimeException(
            'Todos los proveedores de IA fallaron: ' . json_encode($errors)
        );
    }
}