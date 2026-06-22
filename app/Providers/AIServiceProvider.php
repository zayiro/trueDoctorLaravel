<?php

namespace App\Providers;

use App\Services\AI\Contracts\AIScribeDriver;
use App\Services\AI\Contracts\AITranscriptionDriver;
use App\Services\AI\Drivers\DeepgramDriver;
use App\Services\AI\Drivers\DeepSeekDriver;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AITranscriptionDriver::class, function ($app) {
            $driver = config('ai.transcription.driver');

            return match ($driver) {
                'deepgram' => new DeepgramDriver(
                    apiKey: config('ai.transcription.deepgram.api_key'),
                    model: config('ai.transcription.deepgram.model'),
                    language: config('ai.transcription.deepgram.language'),
                ),
                default => throw new \InvalidArgumentException("Driver de transcripción no soportado: {$driver}"),
            };
        });

        $this->app->bind(AIScribeDriver::class, function ($app) {
            $driver = config('ai.scribe.driver');

            return match ($driver) {
                'deepseek' => new DeepSeekDriver(
                    apiKey: config('ai.scribe.deepseek.api_key'),
                    model: config('ai.scribe.deepseek.model'),
                ),
                // Si más adelante quieres usar OpenAI también para estructurar
                // (no para visión), agregar aquí un OpenAIScribeDriver análogo.
                default => throw new \InvalidArgumentException("Driver de estructuración no soportado: {$driver}"),
            };
        });
    }
}