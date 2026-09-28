<?php

namespace App\Services;

use App\Models\MedicalAnalysis;
use Illuminate\Support\Facades\Storage;

class SkinAnalysisService
{
    /**
     * Procesar análisis de piel: convertir imágenes y enviar a IA
     */
    public function analyzeWithAI(MedicalAnalysis $analysis)
    {
        try {
            // Obtener archivos
            $analysisDir = 'medical-analyses/skin/' . $analysis->id;
            $files = Storage::disk('public')->files($analysisDir);

            if (empty($files)) {
                throw new \Exception('No files found for analysis');
            }

            // Procesar imágenes
            $imageData = [];
            foreach ($files as $file) {
                $path = storage_path('app/public/' . $file);
                if (file_exists($path)) {
                    $base64 = base64_encode(file_get_contents($path));
                    $mime = mime_content_type($path);
                    $imageData[] = [
                        'base64' => $base64,
                        'mime' => $mime,
                    ];
                }
            }

            if (empty($imageData)) {
                throw new \Exception('Could not process images');
            }

            // Generar prompt según idioma
            $prompt = $analysis->language === 'es'
                ? $this->getSpanishPrompt($analysis)
                : $this->getEnglishPrompt($analysis);

            // Llamar a IA (Claude)
            $response = $this->callClaudeAPI($imageData, $prompt);

            // Guardar resultado
            $analysis->update([
                'analysis_result' => $response,
                'status' => 'completed',
                'analyzed_at' => now(),
            ]);

            // Enviar email con resultado
            \Mail::to($analysis->email)->queue(new \App\Mail\SkinAnalysisReady($analysis));

            return $response;

        } catch (\Exception $e) {
            \Log::error('Skin analysis error: ' . $e->getMessage(), [
                'analysis_id' => $analysis->id,
                'email' => $analysis->email,
            ]);

            $analysis->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Llamar Claude API para análisis
     */
    private function callClaudeAPI($imageData, $prompt)
    {
        $client = new \Anthropic\Client([
            'apiKey' => env('ANTHROPIC_API_KEY'),
        ]);

        // Construir content con imágenes
        $content = [
            [
                'type' => 'text',
                'text' => $prompt
            ]
        ];

        // Agregar imágenes al contenido
        foreach ($imageData as $image) {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $image['mime'],
                    'data' => $image['base64'],
                ]
            ];
        }

        $response = $client->messages->create([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1500,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ]
        ]);

        if (!empty($response->content)) {
            return $response->content[0]->text;
        }

        throw new \Exception('No response from Claude API');
    }

    /**
     * Prompt en español para análisis de lesiones de piel
     */
    private function getSpanishPrompt(MedicalAnalysis $analysis)
    {
        $locationContext = $analysis->body_location
            ? "La lesión está localizada en: {$analysis->body_location}."
            : '';

        $descriptionContext = $analysis->description
            ? "Descripción del paciente: {$analysis->description}."
            : '';

        return <<<PROMPT
Eres un dermatólogo experto analizando fotos de lesiones de piel enviadas por un paciente.

{$locationContext}
{$descriptionContext}

**IMPORTANTE**: Este es un análisis preliminar educativo. NO es un diagnóstico médico definitivo. El paciente DEBE consultar a un dermatólogo para confirmación.

Analiza las imágenes y proporciona:

## 1. Identificación Probable
¿Qué parece ser la lesión? Lista 2-3 diagnósticos diferenciales más probables (ej: acné, eccema, candidiasis, dermatitis, infección bacteriana, etc).

## 2. Caracterización Benigno/Maligno
- **Características benignas observadas**: describe qué ves que sugiere lesión benigna
- **Signos de alerta (si existen)**: cambios de color, asimetría, bordes irregulares, etc
- **Conclusión**: "Apariencia BENIGNA / REQUIERE EVALUACIÓN PROFESIONAL"

## 3. Nivel de Urgencia
- 🟢 LEVE: Puedes esperar a cita rutinaria
- 🟡 MODERADA: Busca cita en 1-2 semanas
- 🔴 URGENTE: Consulta dermatólogo/médico en 24-48 horas

## 4. Recomendaciones Preliminares
- Cuidados básicos (higiene, humedad, etc)
- Medicamentos OTC que PODRÍAN ayudar (crema antifúngica, corticoide tópico, etc)
- Qué evitar (ropa apretada, sudor, etc)

## 5. Especialista Recomendado
¿A quién debería consultar?
- Dermatólogo (primaria)
- Urólogo (si es genital)
- Médico general (si es leve)

## 6. Disclaimer
**ESTE NO ES UN DIAGNÓSTICO.** Solo análisis visual preliminar. Requiere evaluación física profesional. Si los síntomas empeoran, consulta urgentemente.

---
Formato tu respuesta en Markdown claro, con emojis visuales para urgencia. Sé empático pero honesto.
PROMPT;
    }

    /**
     * Prompt en inglés para análisis de lesiones de piel
     */
    private function getEnglishPrompt(MedicalAnalysis $analysis)
    {
        $locationContext = $analysis->body_location
            ? "The lesion is located at: {$analysis->body_location}."
            : '';

        $descriptionContext = $analysis->description
            ? "Patient description: {$analysis->description}."
            : '';

        return <<<PROMPT
You are an expert dermatologist analyzing photos of skin lesions submitted by a patient.

{$locationContext}
{$descriptionContext}

**IMPORTANT**: This is a preliminary educational analysis. It is NOT a definitive medical diagnosis. The patient MUST consult a dermatologist for confirmation.

Analyze the images and provide:

## 1. Probable Identification
What does the lesion appear to be? List 2-3 most likely differential diagnoses (e.g., acne, eczema, candidiasis, dermatitis, bacterial infection, etc).

## 2. Benign/Malignant Characterization
- **Benign features observed**: describe what you see that suggests benign lesion
- **Warning signs (if any)**: color changes, asymmetry, irregular borders, etc
- **Conclusion**: "Appearance BENIGN / REQUIRES PROFESSIONAL EVALUATION"

## 3. Urgency Level
- 🟢 MILD: Can wait for routine appointment
- 🟡 MODERATE: Seek appointment in 1-2 weeks
- 🔴 URGENT: See dermatologist/doctor in 24-48 hours

## 4. Preliminary Recommendations
- Basic care (hygiene, moisture, etc)
- OTC medications that MIGHT help (antifungal cream, topical steroid, etc)
- What to avoid (tight clothes, sweat, etc)

## 5. Recommended Specialist
Who should you consult?
- Dermatologist (primary)
- Urologist (if genital)
- General practitioner (if mild)

## 6. Disclaimer
**THIS IS NOT A DIAGNOSIS.** Only preliminary visual analysis. Requires professional physical evaluation. If symptoms worsen, consult urgently.

---
Format your response in clear Markdown with visual emojis for urgency. Be empathetic but honest.
PROMPT;
    }

    /**
     * Eliminar archivos de análisis (opcional)
     */
    public function deleteAnalysisFiles(MedicalAnalysis $analysis)
    {
        $analysisDir = 'medical-analyses/skin/' . $analysis->id;
        if (Storage::disk('public')->exists($analysisDir)) {
            Storage::disk('public')->deleteDirectory($analysisDir);
        }
    }
}