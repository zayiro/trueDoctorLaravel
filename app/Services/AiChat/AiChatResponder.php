<?php

namespace App\Services\AiChat;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Quien responde al paciente: IA real con el modelo elegido en el admin y respaldo automático.
 * Si ningún modelo responde, el mensaje del paciente se devuelve (no gasta su cupo) y se lanza una excepción.
 */
class AiChatResponder
{
    public function __construct(private readonly AiChatClient $client)
    {
    }

    public function reply(AiChatSession $session, AiChatMessage $userMessage): AiChatMessage
    {
        $result = $this->ask($session, $userMessage);

        $offTopic = $result['off_topic'];
        $text = $result['reply'];

        if ($offTopic) {
            $count = (int) $session->offtopic_count + 1;
            $userMessage->update(['off_topic' => true]);
            $session->update(['offtopic_count' => $count]);

            // Pasado el límite, la respuesta es fija y firme (no se sigue alimentando el desvío).
            if ($count > AiChatSettings::int('ai_chat_offtopic_limit')) {
                $text = AiChatText::t('offtopic_fixed', [], $session->locale);
            }
        }

        $update = [
            'tokens_in' => (int) $session->tokens_in + $result['tokens_in'],
            'tokens_out' => (int) $session->tokens_out + $result['tokens_out'],
            'tokens_cache_read' => (int) $session->tokens_cache_read + $result['cache_read'],
        ];
        if ($result['focus'] !== '') {
            $update['focus'] = $result['focus'];
        }
        $session->update($update);

        return $session->messages()->create([
            'role' => 'assistant',
            'kind' => 'chat',
            'content' => $text,
            'counted' => false,
            'off_topic' => $offTopic,
            'locale' => $session->locale,
            'model' => $result['model'],
            'tokens_in' => $result['tokens_in'],
            'tokens_out' => $result['tokens_out'],
        ]);
    }

    /** Modelo elegido y, si falla, el de respaldo; si ambos fallan, devuelve el mensaje y relanza. */
    private function ask(AiChatSession $session, AiChatMessage $userMessage): array
    {
        $system = AiChatPrompt::build($session);
        $history = $this->history($session);

        $models = array_values(array_unique(array_filter([
            $session->model_chat ?: AiChatSettings::value('ai_chat_model_chat'),
            AiChatSettings::value('ai_chat_model_fallback'),
        ])));

        $last = null;
        foreach ($models as $model) {
            try {
                return $this->client->ask((string) $model, $system, $history);
            } catch (Throwable $e) {
                $last = $e;
                Log::warning('ai_chat.model_failed', ['session_id' => $session->id, 'model' => $model, 'message' => $e->getMessage()]);
            }
        }

        $this->refund($session, $userMessage);

        throw new RuntimeException('Ningún modelo de IA respondió: '.($last?->getMessage() ?? 'sin modelos configurados'));
    }

    private function refund(AiChatSession $session, AiChatMessage $userMessage): void
    {
        if ($userMessage->counted) {
            AiChatSession::whereKey($session->id)->where('user_message_count', '>', 0)->decrement('user_message_count');
        }

        $userMessage->delete();
    }

    /**
     * Historial para la IA: saludo fijo y avisos fuera (el saludo va implícito), empieza siempre con el paciente
     * y no deja dos turnos seguidos del mismo rol.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function history(AiChatSession $session): array
    {
        $rows = $session->messages()
            ->whereIn('kind', ['chat', 'quickstart'])
            ->orderBy('id')
            ->get(['role', 'content']);

        $out = [];
        foreach ($rows as $row) {
            if ($out === [] && $row->role !== 'user') {
                continue;
            }

            if ($out !== [] && $out[array_key_last($out)]['role'] === $row->role) {
                $out[array_key_last($out)]['content'] .= "\n\n".$row->content;

                continue;
            }

            $out[] = ['role' => $row->role, 'content' => $row->content];
        }

        return $out;
    }
}
