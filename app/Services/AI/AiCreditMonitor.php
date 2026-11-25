<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Detecta, sin tocar ningún driver, cuándo un proveedor de IA rechaza una llamada por falta de crédito
 * y lo deja anotado en ai_provider_credits.billing_error_at (el dashboard del admin lo pinta en rojo).
 * Todo está envuelto en try/catch: este monitor nunca debe romper una llamada de IA.
 */
final class AiCreditMonitor
{
    private const PATTERN = '/credit[ _]balance|insufficient[ _](balance|quota|funds)|billing_not_active|payment required/i';

    private const HOSTS = ['api.anthropic.com' => 'anthropic', 'api.openai.com' => 'openai', 'api.deepseek.com' => 'deepseek', 'api.twilio.com' => 'twilio', 'api.deepgram.com' => 'deepgram'];

    private static bool $booted = false;

    public static function listen(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        Event::listen(ResponseReceived::class, function ($e) {
            try {
                $host = (string) parse_url((string) $e->request->url(), PHP_URL_HOST);
                $provider = self::HOSTS[$host] ?? null;
                $status = $e->response->status();
                if (!$provider || $status < 400) {
                    return;
                }
                $body = (string) $e->response->body();
                if ($status === 402 || preg_match(self::PATTERN, $body)) {
                    self::report($provider, $status, $body);
                }
            } catch (\Throwable) {
            }
        });

        Event::listen(MessageLogged::class, function ($e) {
            try {
                if (!in_array($e->level, ['error', 'critical', 'alert', 'emergency'], true)) {
                    return;
                }
                $text = (string) $e->message;
                if (isset($e->context['exception']) && $e->context['exception'] instanceof \Throwable) {
                    $text .= ' ' . $e->context['exception']->getMessage();
                }
                self::fromText($text, 0);
            } catch (\Throwable) {
            }
        });

        Event::listen(JobExceptionOccurred::class, function ($e) {
            self::fromException($e->exception);
        });
    }

    public static function fromException(\Throwable $ex): void
    {
        try {
            self::fromText(get_class($ex) . ' ' . $ex->getMessage(), 0);
        } catch (\Throwable) {
        }
    }

    private static function fromText(string $text, int $status): void
    {
        if (!preg_match(self::PATTERN, $text)) {
            return;
        }
        $provider = match (true) {
            (bool) preg_match('/anthropic|claude/i', $text) => 'anthropic',
            (bool) preg_match('/openai|gpt/i', $text) => 'openai',
            (bool) preg_match('/deepseek/i', $text) => 'deepseek',
            (bool) preg_match('/twilio/i', $text) => 'twilio',
            (bool) preg_match('/deepgram/i', $text) => 'deepgram',
            default => null,
        };
        if ($provider) {
            self::report($provider, $status, $text);
        }
    }

    public static function report(string $provider, int $status, string $body): void
    {
        try {
            $prev = DB::table('ai_provider_credits')->where('provider', $provider)->value('billing_error_at');
            $fresh = !$prev || Carbon::parse($prev)->lt(now()->subMinutes(10));

            DB::table('ai_provider_credits')->where('provider', $provider)->update([
                'billing_error_at' => now(),
                'last_error' => mb_substr('Sin crédito' . ($status ? " (HTTP $status)" : '') . ': ' . trim(preg_replace('/\s+/', ' ', strip_tags($body))), 0, 250),
                'updated_at' => now(),
            ]);

            if ($fresh) {
                Log::warning('ai.credit_exhausted', ['provider' => $provider, 'status' => $status]);
            }
        } catch (\Throwable) {
        }
    }
}
