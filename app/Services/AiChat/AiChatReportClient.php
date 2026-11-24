<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Llamada a la IA para el informe final (Anthropic u OpenAI). Sin temperature.
 * Acepta la herramienta, JSON en texto o texto con JSON dentro, y valida el resultado.
 */
class AiChatReportClient
{
    private const TOOL = 'entregar_informe';
    public const URGENCIES = ['baja', 'media', 'alta', 'urgente'];

    /** @return array{data: array, tokens_in: int, tokens_out: int, model: string} */
    public function generate(string $model, string $system, string $userMessage): array
    {
        $provider = config("ai_chat.models.{$model}.provider") ?? (str_starts_with($model, 'gpt-') ? 'openai' : 'anthropic');

        [$raw, $in, $out] = $provider === 'openai'
            ? $this->openai($model, $system, $userMessage)
            : $this->anthropic($model, $system, $userMessage);

        return ['data' => $this->normalize($raw), 'tokens_in' => $in, 'tokens_out' => $out, 'model' => $model];
    }

    private function anthropic(string $model, string $system, string $userMessage): array
    {
        $key = (string) config('services.anthropic.key');
        if ($key === '') {
            throw new RuntimeException('Anthropic: API key no configurada.');
        }

        $res = Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
            ->timeout(45)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 3000,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $userMessage]],
                'tools' => [['name' => self::TOOL, 'description' => 'Entrega el informe final al paciente.', 'input_schema' => $this->schema()]],
            ]);

        if ($res->failed()) {
            throw new RuntimeException("Anthropic HTTP {$res->status()}: ".mb_substr((string) $res->body(), 0, 300));
        }

        $content = collect($res->json('content', []));
        $data = (array) ($content->firstWhere('type', 'tool_use')['input'] ?? []);

        if ($data === []) {
            $data = $this->jsonFromText((string) $content->where('type', 'text')->pluck('text')->implode("\n"));
        }

        return [$data, (int) $res->json('usage.input_tokens', 0), (int) $res->json('usage.output_tokens', 0)];
    }

    private function openai(string $model, string $system, string $userMessage): array
    {
        $key = (string) config('services.openai.key');
        if ($key === '') {
            throw new RuntimeException('OpenAI: API key no configurada.');
        }

        $format = "\n\nResponde SOLO con un objeto JSON con las claves: reason (string), summary (string), impressions (array de strings), action_plan (array de strings), warning_signs (array de strings), questions (array de strings), urgency (baja|media|alta|urgente), specialty (slug).";

        $res = Http::withToken($key)
            ->timeout(45)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'max_completion_tokens' => 3000,
                'response_format' => ['type' => 'json_object'],
                'messages' => [['role' => 'system', 'content' => $system.$format], ['role' => 'user', 'content' => $userMessage]],
            ]);

        if ($res->failed()) {
            throw new RuntimeException("OpenAI HTTP {$res->status()}: ".mb_substr((string) $res->body(), 0, 300));
        }

        return [
            $this->jsonFromText((string) $res->json('choices.0.message.content')),
            (int) $res->json('usage.prompt_tokens', 0),
            (int) $res->json('usage.completion_tokens', 0),
        ];
    }

    private function jsonFromText(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
        $json = json_decode($text, true);

        if (! is_array($json) && preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
        }

        return is_array($json) ? $json : [];
    }

    /** Limpia y valida lo que devolvió el modelo; exige resumen y plan de acción. */
    private function normalize(array $d): array
    {
        $list = function ($v, int $max, int $len): array {
            $v = is_array($v) ? $v : (is_string($v) && trim($v) !== '' ? [$v] : []);

            return array_values(array_slice(array_filter(array_map(
                fn ($x) => is_scalar($x) ? mb_substr(trim((string) $x), 0, $len) : '', $v
            ), fn ($x) => $x !== ''), 0, $max));
        };

        $summary = is_scalar($d['summary'] ?? null) ? trim((string) $d['summary']) : '';
        $plan = $list($d['action_plan'] ?? [], 8, 500);

        if ($summary === '' || $plan === []) {
            throw new RuntimeException('La IA no devolvió un informe utilizable (faltan resumen o plan de acción).');
        }

        $urgency = strtolower(trim((string) ($d['urgency'] ?? '')));
        $specialty = (string) ($d['specialty'] ?? '');

        return [
            'reason' => mb_substr(trim((string) ($d['reason'] ?? '')), 0, 300),
            'summary' => mb_substr($summary, 0, 3500),
            'impressions' => $list($d['impressions'] ?? [], 5, 400),
            'action_plan' => $plan,
            'warning_signs' => $list($d['warning_signs'] ?? [], 8, 300),
            'questions' => $list($d['questions'] ?? [], 6, 300),
            'urgency' => in_array($urgency, self::URGENCIES, true) ? $urgency : 'media',
            'specialty' => AiChatSpecialties::isValid($specialty) ? $specialty : 'medicina-general',
        ];
    }

    private function schema(): array
    {
        $arr = fn (string $d) => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => $d];

        return [
            'type' => 'object',
            'properties' => [
                'reason' => ['type' => 'string', 'description' => 'Motivo principal en una frase.'],
                'summary' => ['type' => 'string', 'description' => 'Resumen de lo conversado (1 a 3 párrafos).'],
                'impressions' => $arr('Posibilidades generales, como orientación y no como diagnóstico.'),
                'action_plan' => $arr('Pasos concretos y seguros.'),
                'warning_signs' => $arr('Señales de alarma para consultar de urgencia.'),
                'questions' => $arr('Preguntas para llevar al médico.'),
                'urgency' => ['type' => 'string', 'enum' => self::URGENCIES],
                'specialty' => ['type' => 'string', 'description' => 'Slug de especialidad sugerida.'],
            ],
            'required' => ['reason', 'summary', 'impressions', 'action_plan', 'warning_signs', 'questions', 'urgency', 'specialty'],
        ];
    }

}
