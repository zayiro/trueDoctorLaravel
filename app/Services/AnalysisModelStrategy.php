<?php
// app/Services/AnalysisModelStrategy.php

namespace App\Services;

use App\Models\Setting;

class AnalysisModelStrategy
{
    /**
     * Obtiene el orden de proveedores (primario + fallback) según el tipo de examen
     * 
     * @param string $examType lab, xray, ultrasound, ct, mri, dicom, mammography
     * @return array ['openai', 'claude'] o ['claude', 'openai'] según configuración
     */
    public static function getProviderOrder(string $examType): array
    {
        // Normalizar tipo
        $examType = strtolower(trim($examType));

        // Determinar si es imagenología o laboratorio
        $isImaging = in_array($examType, ['xray', 'ultrasound', 'ct', 'mri', 'dicom', 'mammography']);

        if ($isImaging) {
            // IMAGENOLOGÍA: Claude primero, OpenAI fallback
            $primary = Setting::get('imaging_ai_primary_model', 'claude');
            $fallback = Setting::get('imaging_ai_fallback_model', 'openai');
        } else {
            // LABORATORIO: OpenAI primero, Claude fallback
            $primary = Setting::get('lab_ai_primary_model', 'openai');
            $fallback = Setting::get('lab_ai_fallback_model', 'claude');
        }

        return self::normalizeProviders([$primary, $fallback]);
    }

    /**
     * Normaliza nombres de proveedores (mapea valores de settings a nombres cortos)
     * Ej: 'claude-sonnet-4-5' → 'claude', 'gpt-4o' → 'openai'
     */
    private static function normalizeProviders(array $providers): array
    {
        return array_map(function ($provider) {
            $provider = strtolower(trim($provider));

            // Mapear nombres largos a cortos
            if (strpos($provider, 'claude') !== false) {
                return 'claude';
            }
            if (strpos($provider, 'gpt') !== false || strpos($provider, 'openai') !== false) {
                return 'openai';
            }

            // Default si no se reconoce
            return 'openai';
        }, $providers);
    }

    /**
     * Obtiene los modelos específicos configurados (no solo el proveedor)
     */
    public static function getModelsForExam(string $examType): array
    {
        $examType = strtolower(trim($examType));
        $isImaging = in_array($examType, ['xray', 'ultrasound', 'ct', 'mri', 'dicom', 'mammography']);

        if ($isImaging) {
            return [
                'primary_model' => Setting::get('imaging_ai_primary_model', 'claude-sonnet-4-5'),
                'fallback_model' => Setting::get('imaging_ai_fallback_model', 'gpt-4o'),
            ];
        } else {
            return [
                'primary_model' => Setting::get('lab_ai_primary_model', 'gpt-4o'),
                'fallback_model' => Setting::get('lab_ai_fallback_model', 'claude-sonnet-4-5'),
            ];
        }
    }
}