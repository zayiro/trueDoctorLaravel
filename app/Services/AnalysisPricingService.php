<?php
// app/Services/AnalysisPricingService.php

namespace App\Services;

use App\Models\Setting;

class AnalysisPricingService
{
    /**
     * Tipos de examen válidos
     */
    const VALID_EXAM_TYPES = ['lab', 'xray', 'ultrasound', 'ct', 'mri', 'dicom', 'mammography'];

    /**
     * Obtener precio FIJO para un tipo de examen
     */
    public static function getExamPrice(string $examType): int
    {
        if (!in_array($examType, self::VALID_EXAM_TYPES)) {
            throw new \Exception("Tipo de examen inválido: {$examType}");
        }

        $key = "exam_type_{$examType}_price";
        $price = Setting::get($key);

        if ($price === null) {
            throw new \Exception("Precio no configurado para: {$examType}");
        }

        return (int)$price;
    }

    /**
     * Obtener factor de decimación automática
     */
    public static function getAutoDecimationFactor(string $examType): int
    {
        if (!in_array($examType, self::VALID_EXAM_TYPES)) {
            return 1; // Default: procesar todo
        }

        $key = "{$examType}_auto_decimation";
        return (int)(Setting::get($key) ?? 1);
    }

    /**
     * Validar que el tipo detectado sea legítimo
     */
    public static function validateExamType(string $examType): bool
    {
        return in_array($examType, self::VALID_EXAM_TYPES);
    }
}