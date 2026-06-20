<?php

namespace App\Services\AI\Contracts;

interface AIVisionDriver
{
    /**
     * Analiza un set de imágenes (base64) + contexto de texto y devuelve
     * un array asociativo estructurado según el schema clínico definido.
     *
     * @param  string  $systemPrompt   Instrucciones de rol/sistema
     * @param  string  $userText       Texto/contexto del usuario (motivo, detalles, instrucciones)
     * @param  array   $images         Array de ['base64' => string, 'mime' => string]
     * @return array   Resultado estructurado ya parseado (ai_response)
     *
     * @throws \RuntimeException si la llamada falla o la respuesta no es válida
     */
    public function analyzeImages(string $systemPrompt, string $userText, array $images): array;

    /**
     * Nombre identificador del driver (para logs y para guardar qué proveedor se usó).
     */
    public function name(): string;
}