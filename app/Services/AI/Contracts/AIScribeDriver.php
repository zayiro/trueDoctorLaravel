<?php

namespace App\Services\AI\Contracts;

interface AIScribeDriver
{
    public function name(): string;

    /**
     * Estructura la transcripción de una consulta en los campos
     * de patient_histories + sugerencia opcional de medicamento.
     */
    public function structureConsultation(string $transcript): array;
}