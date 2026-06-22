<?php

namespace App\Services\AI\Contracts;

interface AITranscriptionDriver
{
    public function name(): string;

    /**
     * Transcribe un archivo de audio a texto plano.
     *
     * @param string $audioPath Ruta local al archivo de audio temporal.
     * @param string $mimeType  Mime type del audio (ej. audio/webm).
     * @return string Texto transcrito.
     */
    public function transcribe(string $audioPath, string $mimeType): string;
}