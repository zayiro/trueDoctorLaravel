<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIVisionDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeDriver implements AIVisionDriver
{
    protected const ALLOWED_MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        protected string $apiKey,
        protected string $model = 'claude-sonnet-4-6',
        protected int $timeout = 90,
    ) {}

    public function name(): string
    {
        return 'claude';
    }

    public function analyzeImages(string $systemPrompt, string $userText, array $images): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Claude: API key no configurada.');
        }

        $userContent = [
            ['type' => 'text', 'text' => $userText],
        ];

        foreach ($images as $image) {
            if (! in_array($image['mime'], self::ALLOWED_MEDIA_TYPES, true)) {
                Log::warning("Claude driver: media type no soportado '{$image['mime']}', se omite imagen.");
                continue;
            }

            $userContent[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $image['mime'],
                    'data' => $image['base64'],
                ],
            ];
        }

        $tool = [
            'name' => 'reportar_analisis_clinico',
            'description' => 'Reporta el análisis clínico estructurado extraído de los documentos médicos.',
            'input_schema' => $this->jsonSchema(),
        ];

        $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry(3, 2000, fn ($e) => $this->shouldRetry($e))
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2048,
                'system' => $systemPrompt . " SIEMPRE debes responder usando la tool 'reportar_analisis_clinico'.",
                'messages' => [
                    ['role' => 'user', 'content' => $userContent],
                ],
                'tools' => [$tool],
                'tool_choice' => ['type' => 'tool', 'name' => 'reportar_analisis_clinico'],
                'temperature' => 0.2,
            ]);

        if ($response->failed()) {
            Log::error("Claude driver: HTTP {$response->status()} - " . $response->body());
            throw new \RuntimeException("Claude API falló: HTTP {$response->status()}");
        }

        $contentBlocks = $response->json('content', []);
        $toolUseBlock = collect($contentBlocks)->firstWhere('type', 'tool_use');

        if (! $toolUseBlock || empty($toolUseBlock['input'])) {
            Log::error("Claude driver: no se encontró bloque tool_use. Body: " . $response->body());
            throw new \RuntimeException('Claude no devolvió la estructura esperada (tool_use).');
        }

        return $toolUseBlock['input'];
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $status = $e->response->status();
            return $status >= 500 || $status === 429;
        }
        return true;
    }

    protected function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nombre_examen' => ['type' => 'string'],
                'especialidad_slug' => [
                    'type' => 'string',
                    'enum' => ['medicina-general', 'neurologia', 'cardiologia', 'ginecologia', 'endocrinologia', 'pediatria', 'urologia', 'dermatologia'],
                ],
                'hallazgos_clave' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'parametro' => ['type' => 'string'],
                            'valor_detectado' => ['type' => 'string'],
                            'estado' => ['type' => 'string', 'enum' => ['Normal', 'Elevado', 'Bajo', 'Crítico']],
                        ],
                        'required' => ['parametro', 'valor_detectado', 'estado'],
                    ],
                ],
                'conclusion_paciente' => ['type' => 'string'],
                'recomendaciones' => ['type' => 'string'],
            ],
            'required' => ['nombre_examen', 'especialidad_slug', 'hallazgos_clave', 'conclusion_paciente', 'recomendaciones'],
        ];
    }
}