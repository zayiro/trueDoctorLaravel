<?php

namespace App\Services;

class AnonymizerService
{
    /**
     * Limpia datos sensibles de un texto consolidado.
     */
    public function cleanMedicalText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $originalLength = strlen($text);

        // 1. Ocultar Correos Electrónicos
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[CORREO_OCULTO]', $text);

        // 2. Ocultar Cédulas / Documentos de Identidad (Formatos de 6 a 12 dígitos seguidos, con puntos o guiones)
        // Versión mejorada: más específica para evitar eliminar números de laboratorio legítimos
        $text = preg_replace('/\b(CC|DNI|CEDULA|PASAPORTE|IDENTIFICACION)\s*:?\s*(\d{1,3}\.?\d{3}\.?\d{3}(-\d)?|\d{6,12})\b/i', '$1: [DOCUMENTO_OCULTO]', $text);

        // 3. Ocultar Números de Teléfono (Formatos locales e internacionales comunes)
        $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[TELEFONO_OCULTO]', $text);

        // 4. Ocultar Nombres Propios antecedidos por etiquetas comunes de reportes clínicos
        // Versión mejorada: más conservadora para no eliminar contenido médico legítimo
        $text = preg_replace('/\b(Paciente|Nombre|Sr\.|Sra\.|Dr\.|Dra\.)\s*:?\s*([A-Z][a-záéíóúüñ]+(?:\s+[A-Z][a-záéíóúüñ]+)*)/i', '$1: [NOMBRE_OCULTO]', $text);

        \Log::info('Sanitización de texto médico completada', [
            'original_length' => $originalLength,
            'cleaned_length' => strlen($text),
            'reduction_percentage' => round((1 - strlen($text) / $originalLength) * 100, 2)
        ]);

        return $text;
    }
}
