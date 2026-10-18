<?php

namespace App\Services;

use App\Models\MedicalAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Único lugar que sabe dónde viven los archivos de un examen y cómo borrarlos.
 * Lo usan destroy(), la limpieza tras analizar y el comando programado de purga.
 */
class MedicalAnalysisFilePurger
{
    private const DISK = 'private';
    private const ALLOWED_PREFIX = 'medical-exams/';

    /**
     * Rutas de los archivos del examen en el disco privado.
     * file_paths puede venir como arreglo o como texto JSON (se guarda con json_encode).
     */
    public function paths(MedicalAnalysis $analysis): array
    {
        $raw = $analysis->file_paths;

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, fn ($path) => is_string($path) && $this->isSafe($path)));
    }

    /**
     * Borra del disco privado los archivos del examen.
     *
     * @return array{deleted:int, failed:string[]}
     */
    public function purge(MedicalAnalysis $analysis): array
    {
        $disk = Storage::disk(self::DISK);
        $deleted = 0;
        $failed = [];

        foreach ($this->paths($analysis) as $path) {
            try {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                    $deleted++;
                }
            } catch (\Throwable $e) {
                $failed[] = $path;
                Log::warning("Análisis #{$analysis->id}: no se pudo borrar '{$path}': " . $e->getMessage());
            }
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    /** Solo se borra dentro de medical-exams/ y nunca con ".." en la ruta. */
    private function isSafe(string $path): bool
    {
        return str_starts_with($path, self::ALLOWED_PREFIX) && !str_contains($path, '..');
    }
}