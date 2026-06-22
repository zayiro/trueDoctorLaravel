<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AITranscriptionDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepgramDriver implements AITranscriptionDriver
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'nova-2-medical',
        protected string $language = 'es',
        protected int $timeout = 120,
    ) {}

    public function name(): string
    {
        return 'deepgram';
    }

    public function transcribe(string $audioPath, string $mimeType): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Deepgram: API key no configurada.');
        }

        if (! file_exists($audioPath)) {
            throw new \RuntimeException('Deepgram: archivo de audio no encontrado en ' . $audioPath);
        }

        $query = http_build_query([
            'model' => $this->model,
            'language' => $this->language,
            'diarize' => 'true',
            'smart_format' => 'true',
            'punctuate' => 'true',
        ]);

        $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => $mimeType,
            ])
            ->timeout($this->timeout)
            ->retry(2, 2000, fn ($e) => $this->shouldRetry($e))
            ->withBody(file_get_contents($audioPath), $mimeType)
            ->post("https://api.deepgram.com/v1/listen?{$query}");

        if ($response->failed()) {
            Log::error("Deepgram driver: HTTP {$response->status()} - " . $response->body());
            throw new \RuntimeException("Deepgram API falló: HTTP {$response->status()}");
        }

        $transcript = $response->json('results.channels.0.alternatives.0.transcript');

        if (empty($transcript)) {
            Log::error("Deepgram driver: transcripción vacía. Body: " . $response->body());
            throw new \RuntimeException('Deepgram devolvió una transcripción vacía.');
        }

        return $transcript;
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $status = $e->response->status();
            return $status >= 500 || $status === 429;
        }
        return true;
    }
}