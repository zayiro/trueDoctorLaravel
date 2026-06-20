<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIVisionDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIDriver implements AIVisionDriver
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'gpt-5.4',
        protected int $timeout = 90,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function analyzeImages(string $systemPrompt, string $userText, array $images): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI: API key no configurada.');
        }

        $userContent = [
            ['type' => 'text', 'text' => $userText],
        ];

        foreach ($images as $image) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$image['mime']};base64,{$image['base64']}",
                ],
            ];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(3, 2000, fn ($e) => $this->shouldRetry($e))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => $this->jsonSchema(),
                ],
                'temperature' => 0.2,
            ]);

        if ($response->failed()) {
            Log::error("OpenAI driver: HTTP {$response->status()} - " . $response->body());
            throw new \RuntimeException("OpenAI API falló: HTTP {$response->status()}");
        }

        $rawContent = $response->json('choices.0.message.content');

        if (empty($rawContent)) {
            Log::error("OpenAI driver: respuesta sin contenido. Body: " . $response->body());
            throw new \RuntimeException('OpenAI devolvió una respuesta vacía.');
        }

        $decoded = json_decode($rawContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("OpenAI driver: JSON inválido. Error: " . json_last_error_msg());
            throw new \RuntimeException('OpenAI devolvió un JSON inválido: ' . json_last_error_msg());
        }

        return $decoded;
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        // No reintentar errores de cliente (4xx) como API key inválida o payload malo;
        // solo timeouts/errores de conexión/5xx.
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $status = $e->response->status();
            return $status >= 500 || $status === 429;
        }
        return true;
    }

    protected function jsonSchema(): array
    {
        return [
            'name' => 'analisis_clinico_pago',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'nombre_examen' => ['type' => 'string'],
                    'especialidad_slug' => ['type' => 'string'],
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
                            'additionalProperties' => false,
                        ],
                    ],
                    'conclusion_paciente' => ['type' => 'string'],
                    'recomendaciones' => ['type' => 'string'],
                ],
                'required' => ['nombre_examen', 'especialidad_slug', 'hallazgos_clave', 'conclusion_paciente', 'recomendaciones'],
                'additionalProperties' => false,
            ],
        ];
    }
}