<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIVisionDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MedGemmaDriver implements AIVisionDriver
{
    protected const ALLOWED_MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        protected string $projectId,
        protected string $endpoint,
        protected string $location = 'us-central1',
        protected string $model = 'medgemma-1-5-4b',
        protected int $timeout = 120,
        protected ?string $accessToken = null,
    ) {}

    public function name(): string
    {
        return 'medgemma';
    }

    public function analyzeImages(string $systemPrompt, string $userText, array $images): array
    {
        if (empty($this->projectId) || empty($this->endpoint)) {
            throw new \RuntimeException('MedGemma: Google Cloud credentials no configuradas.');
        }

        // Obtener access token si no se proporciona
        $token = $this->accessToken ?? $this->getAccessToken();

        if (empty($token)) {
            throw new \RuntimeException('MedGemma: No se pudo obtener access token de Google Cloud.');
        }

        // Construir contenido del usuario
        $userContent = [
            'text' => $userText,
        ];

        // Validar y agregar imágenes
        $imagesToSend = [];
        foreach ($images as $image) {
            if (! in_array($image['mime'], self::ALLOWED_MEDIA_TYPES, true)) {
                Log::warning("MedGemma driver: media type no soportado '{$image['mime']}', se omite imagen.");
                continue;
            }

            $imagesToSend[] = [
                'inline_data' => [
                    'mime_type' => $image['mime'],
                    'data' => $image['base64'],
                ],
            ];
        }

        if (empty($imagesToSend)) {
            throw new \RuntimeException('MedGemma: No hay imágenes válidas para analizar.');
        }

        // Construir payload para Vertex AI
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => array_merge(
                        [['text' => $systemPrompt . "\n\n" . $userText]],
                        $imagesToSend
                    ),
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.95,
                'topK' => 40,
                'maxOutputTokens' => 2048,
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
        ];

        $url = "https://{$this->location}-aiplatform.googleapis.com/v1/projects/{$this->projectId}/locations/{$this->location}/endpoints/{$this->endpoint}:predict";

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry(3, 2000, fn ($e) => $this->shouldRetry($e))
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error("MedGemma driver: HTTP {$response->status()} - " . $response->body());
            throw new \RuntimeException("MedGemma API falló: HTTP {$response->status()}");
        }

        $predictions = $response->json('predictions', []);

        if (empty($predictions)) {
            Log::error("MedGemma driver: respuesta sin predicciones. Body: " . $response->body());
            throw new \RuntimeException('MedGemma devolvió una respuesta vacía.');
        }

        // Parsear respuesta
        $prediction = $predictions[0];
        $content = $prediction['content'] ?? $prediction;

        if (is_string($content)) {
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("MedGemma driver: JSON inválido. Error: " . json_last_error_msg());
                throw new \RuntimeException('MedGemma devolvió un JSON inválido: ' . json_last_error_msg());
            }

            return $decoded;
        }

        return $content;
    }

    /**
     * Obtener access token desde Google Cloud (usa Application Default Credentials)
     */
    protected function getAccessToken(): ?string
    {
        try {
            $metadataUrl = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/identity';

            $response = Http::withHeaders([
                'Metadata-Flavor' => 'Google',
            ])
            ->timeout(5)
            ->get($metadataUrl, [
                'audience' => "https://{$this->location}-aiplatform.googleapis.com",
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('MedGemma: No se pudo obtener token desde metadata server.');
        } catch (\Throwable $e) {
            Log::warning("MedGemma: Error obteniendo token: " . $e->getMessage());
        }

        return null;
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
                    'enum' => ['radiologia', 'cardiologia', 'neurologia', 'oncologia', 'medicina-general'],
                ],
                'hallazgos_clave' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'parametro' => ['type' => 'string'],
                            'valor_detectado' => ['type' => 'string'],
                            'estado' => ['type' => 'string', 'enum' => ['Normal', 'Elevado', 'Bajo', 'Crítico', 'Anormal']],
                            'ubicacion' => ['type' => 'string'],
                        ],
                        'required' => ['parametro', 'valor_detectado', 'estado'],
                    ],
                ],
                'conclusion_paciente' => ['type' => 'string'],
                'recomendaciones' => ['type' => 'string'],
                'hallazgos_criticos' => ['type' => 'boolean'],
            ],
            'required' => ['nombre_examen', 'especialidad_slug', 'hallazgos_clave', 'conclusion_paciente', 'recomendaciones'],
        ];
    }
}