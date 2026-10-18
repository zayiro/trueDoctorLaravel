<?php

namespace App\Console\Commands;

use App\Models\MedicalAnalysis;
use App\Services\MedicalAnalysisFilePurger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeStaleMedicalFiles extends Command
{
    protected $signature = 'medical:purge-stale
                            {--days=7 : Días que se conservan los archivos de análisis fallidos o sin pagar}
                            {--dry-run : Solo muestra lo que se eliminaría}';

    protected $description = 'Elimina subidas temporales abandonadas y archivos de exámenes fallidos o sin pagar';

    public function handle(MedicalAnalysisFilePurger $purger): int
    {
        $dry = (bool) $this->option('dry-run');
        $days = max(1, (int) $this->option('days'));

        $tempUploads = 0;
        $tempImages = 0;
        $analyses = 0;

        // 1) Subidas temporales abandonadas (más de 6 horas)
        $local = Storage::disk('local');
        $uploadCutoff = now()->subHours(6)->timestamp;

        foreach ($local->files('temp-uploads') as $file) {
            if ($local->lastModified($file) < $uploadCutoff) {
                if (!$dry) {
                    $local->delete($file);
                }
                $tempUploads++;
            }
        }

        // 2) Imágenes temporales de la conversión de PDF que quedaron huérfanas (más de 2 horas)
        $imageCutoff = now()->subHours(2)->timestamp;

        foreach (glob(storage_path('app/temp/med_*')) ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $imageCutoff) {
                if (!$dry) {
                    @unlink($file);
                }
                $tempImages++;
            }
        }

        // 3) Archivos de análisis fallidos o sin pagar que ya no se van a procesar
        MedicalAnalysis::query()
            ->whereNotNull('file_paths')
            ->where('created_at', '<', now()->subDays($days))
            ->where(function ($query) {
                $query->where('status', 'failed')
                    ->orWhere('payment_status', '!=', 'completed');
            })
            ->chunkById(100, function ($chunk) use ($purger, $dry, &$analyses) {
                foreach ($chunk as $analysis) {
                    if (empty($purger->paths($analysis))) {
                        continue;
                    }

                    if (!$dry) {
                        $result = $purger->purge($analysis);

                        if (empty($result['failed'])) {
                            $analysis->update(['file_paths' => null]);
                        }
                    }

                    $analyses++;
                }
            });

        $prefix = $dry ? '[simulación] ' : '';
        $this->info("{$prefix}Subidas temporales: {$tempUploads} · Imágenes temporales: {$tempImages} · Análisis con archivos purgados: {$analyses}");

        return self::SUCCESS;
    }
}