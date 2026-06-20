<?php

namespace App\Services;

class AnonymizerService
{
    /**
     * Limpia datos sensibles de un texto consolidado.
     */
    public function cleanMedicalText(string $text): string
    {
        // 1. Ocultar Correos Electrónicos
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[CORREO_OCULTO]', $text);

        // 2. Ocultar Cédulas / Documentos de Identidad (Formatos de 6 a 12 dígitos seguidos, con puntos o guiones)
        $text = preg_replace('/\b(CC|DNI|CEDULA|PASAPORTE|IDENTIFICACION)?\s*?:?\s*?\d{1,3}(\.?\d{3}){2,3}(-\d)?\b/i', '[DOCUMENTO_OCULTO]', $text);

        // 3. Ocultar Números de Teléfono (Formatos locales e internacionales comunes)
        $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[TELEFONO_OCULTO]', $text);

        // 4. Ocultar Nombres Propios antecedidos por etiquetas comunes de reportes clínicos
        $text = preg_replace('/\b(Paciente|Nombre|Sr\.|Sra\.|Dr\.|Dra\.)\s*?:?\s*?([A-Z][a-zñáéíóúü]+\s*){1,3}/i', '$1: [NOMBRE_OCULTO]', $text);

        return $text;
    }
}
