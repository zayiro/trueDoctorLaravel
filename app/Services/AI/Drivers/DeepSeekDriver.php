<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIScribeDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekDriver implements AIScribeDriver
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'deepseek-chat',
        protected int $timeout = 90,
    ) {}

    public function name(): string
    {
        return 'deepseek';
    }

    /**
     * Convierte la transcripción cruda de una consulta en una nota clínica estructurada.
     *
     * @param string $transcript Texto transcrito de la consulta.
     * @return array Datos estructurados, alineados a los campos de patient_histories
     *               + sugerencia opcional de medicamento.
     */
    public function structureConsultation(string $transcript): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('DeepSeek: API key no configurada.');
        }

        // DeepSeek no soporta json_schema/strict como OpenAI; el contrato se
        // refuerza por prompt y se valida el JSON resultante manualmente.
        $systemPrompt = <<<PROMPT
Eres un asistente que ayuda a un médico a estructurar la nota de una consulta
a partir de la transcripción de la conversación con su paciente.

Reglas estrictas:
- Usa ÚNICAMENTE información que aparezca explícitamente en la transcripción.
- Nunca inventes diagnósticos, síntomas, dosis o datos que no se mencionarón.
- Si un campo no se puede determinar con la información disponible, devuélvelo
  como cadena vacía "".
- Responde EXCLUSIVAMENTE con un objeto JSON válido, sin texto adicional,
  sin explicaciones, sin Markdown ni bloques de código.

El JSON debe tener exactamente esta forma:
{
  "entry_type": "consultation" | "follow_up" | "emergency" | "note",
  "reason_for_consultation": "string",
  "symptoms": "string",
  "diagnosis": "string",
  "treatment_plan": "string",
  "medication_suggestion": {
    "name": "string o vacío si no se mencionó medicamento nuevo",
    "dosage": "string",
    "frequency": "string",
    "notes": "string"
  }
}
PROMPT;

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(3, 2000, fn ($e) => $this->shouldRetry($e))
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Transcripción de la consulta:\n\n{$transcript}"],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
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
            Log::error("DeepSeek driver: JSON inválido. Error: " . json_last_error_msg() . " - Raw: " . $rawContent);
            throw new \RuntimeException('DeepSeek devolvió un JSON inválido: ' . json_last_error_msg());
        }

        return $this->validateShape($decoded);
    }

    /**
     * DeepSeek no soporta JSON schema estricto, así que validamos a mano
     * la forma esperada antes de devolverlo al controlador/job.
     */
    protected function validateShape(array $data): array
    {
        $allowedEntryTypes = ['consultation', 'follow_up', 'emergency', 'note'];

        return [
            'entry_type' => in_array($data['entry_type'] ?? null, $allowedEntryTypes)
                ? $data['entry_type']
                : 'consultation',
            'reason_for_consultation' => (string) ($data['reason_for_consultation'] ?? ''),
            'symptoms' => (string) ($data['symptoms'] ?? ''),
            'diagnosis' => (string) ($data['diagnosis'] ?? ''),
            'treatment_plan' => (string) ($data['treatment_plan'] ?? ''),
            'medication_suggestion' => [
                'name' => (string) ($data['medication_suggestion']['name'] ?? ''),
                'dosage' => (string) ($data['medication_suggestion']['dosage'] ?? ''),
                'frequency' => (string) ($data['medication_suggestion']['frequency'] ?? ''),
                'notes' => (string) ($data['medication_suggestion']['notes'] ?? ''),
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