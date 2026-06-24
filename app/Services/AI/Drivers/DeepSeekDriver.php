<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIScribeDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekDriver implements AIScribeDriver
{
    public function __construct(
        protected string $apiKey,
        protected string $model   = 'deepseek-chat',
        protected int    $timeout = 90,
    ) {}

    public function name(): string
    {
        return 'deepseek';
    }

    public function structureConsultation(string $transcript): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('DeepSeek: API key no configurada.');
        }

        $systemPrompt = <<<PROMPT
Eres un asistente médico experto en documentación clínica en formato SOAP.
Recibirás la transcripción de una consulta médica y debes estructurarla.

Reglas estrictas:
- Usa ÚNICAMENTE información que aparezca explícitamente en la transcripción.
- Nunca inventes diagnósticos, síntomas, dosis o datos que no se mencionaron.
- Si un campo no se puede determinar, devuélvelo como cadena vacía "".
- Responde EXCLUSIVAMENTE con un objeto JSON válido, sin texto adicional,
  sin explicaciones, sin Markdown ni bloques de código.

El JSON debe tener exactamente esta forma:
{
  "entry_type": "consultation" | "follow_up" | "emergency" | "note",
  "cie10_code": "código CIE-10 principal si se menciona diagnóstico, si no vacío",
  "soap_subjective": "S — Motivo de consulta y síntomas referidos por el paciente en sus propias palabras",
  "soap_objective": "O — Hallazgos del examen físico, signos vitales y resultados de exámenes mencionados",
  "soap_assessment": "A — Diagnóstico o impresión diagnóstica con lenguaje clínico",
  "soap_plan": "P — Tratamiento indicado, medicamentos, remisiones, recomendaciones y fecha de control",
  "medication_suggestion": {
    "name": "nombre del medicamento si se prescribió, si no vacío",
    "dosage": "dosis",
    "frequency": "frecuencia",
    "notes": "indicaciones adicionales"
  }
}
PROMPT;

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(3, 2000, fn($e) => $this->shouldRetry($e))
            ->post('https://api.deepseek.com/chat/completions', [
                'model'           => $this->model,
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => "Transcripción de la consulta:\n\n{$transcript}"],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.1,
            ]);

        if ($response->failed()) {
            Log::error("DeepSeek driver: HTTP {$response->status()} - " . $response->body());
            throw new \RuntimeException("DeepSeek API falló: HTTP {$response->status()}");
        }

        $rawContent = $response->json('choices.0.message.content');

        if (empty($rawContent)) {
            Log::error("DeepSeek driver: respuesta sin contenido. Body: " . $response->body());
            throw new \RuntimeException('DeepSeek devolvió una respuesta vacía.');
        }

        $decoded = json_decode($rawContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("DeepSeek driver: JSON inválido. Raw: " . $rawContent);
            throw new \RuntimeException('DeepSeek devolvió un JSON inválido: ' . json_last_error_msg());
        }

        return $this->validateShape($decoded);
    }

    protected function validateShape(array $data): array
    {
        $allowedEntryTypes = ['consultation', 'follow_up', 'emergency', 'note'];

        return [
            'entry_type'           => in_array($data['entry_type'] ?? null, $allowedEntryTypes)
                                        ? $data['entry_type']
                                        : 'consultation',
            'cie10_code'           => (string) ($data['cie10_code']      ?? ''),
            'soap_subjective'      => (string) ($data['soap_subjective'] ?? ''),
            'soap_objective'       => (string) ($data['soap_objective']  ?? ''),
            'soap_assessment'      => (string) ($data['soap_assessment'] ?? ''),
            'soap_plan'            => (string) ($data['soap_plan']       ?? ''),
            'medication_suggestion' => [
                'name'      => (string) ($data['medication_suggestion']['name']      ?? ''),
                'dosage'    => (string) ($data['medication_suggestion']['dosage']    ?? ''),
                'frequency' => (string) ($data['medication_suggestion']['frequency'] ?? ''),
                'notes'     => (string) ($data['medication_suggestion']['notes']     ?? ''),
            ],
        ];
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