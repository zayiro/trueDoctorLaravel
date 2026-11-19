<?php

namespace App\Console\Commands;

use App\Models\SkinAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Las fotos de piel son datos sensibles: se conservan solo mientras hacen falta.
 *   1) Subidas temporales abandonadas (más de 6 horas).
 *   2) Fotos de análisis ya completados (se borran al terminar el informe; esto limpia lo que haya quedado).
 *   3) Fotos de órdenes sin pagar o con pago fallido tras N días; la orden pasa a "expired".
 * Los análisis "processing" y "error" (pagados, requieren atención) no se tocan.
 */
class PurgeStaleSkinFiles extends Command
{
    private const DISK = 'private';
    private const TEMP_DIR = 'skin-analysis/temp';

    protected $signature = 'skin:purge-stale
                            {--days=7 : Días que se conservan las fotos de órdenes sin pagar o con pago fallido}
                            {--dry-run : Solo muestra lo que se eliminaría}';

    protected $description = 'Elimina fotos de piel: subidas abandonadas, análisis completados y órdenes sin pagar';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $days = max(1, (int) $this->option('days'));
        $disk = Storage::disk(self::DISK);

        $temp = 0;
        $completed = 0;
        $unpaid = 0;
        $failed = 0;

        // 1) Subidas temporales abandonadas
        $cutoff = now()->subHours(6)->timestamp;

        foreach ($disk->directories(self::TEMP_DIR) as $dir) {
            try {
                $files = $disk->files($dir);
                $newest = 0;

                foreach ($files as $file) {
                    $newest = max($newest, $disk->lastModified($file));
                }

                if ($files === [] || $newest < $cutoff) {
                    if (!$dry) {
                        $disk->deleteDirectory($dir);
                    }
                    $temp++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('skin:purge-stale no pudo procesar una subida temporal', ['dir' => $dir, 'error' => $e->getMessage()]);
            }
        }

        // 2) Análisis completados que todavía conservan fotos
        SkinAnalysis::query()
            ->where('status', SkinAnalysis::STATUS_COMPLETED)
            ->chunkById(200, function ($chunk) use ($disk, $dry, &$completed, &$failed) {
                foreach ($chunk as $analysis) {
                    try {
                        if ($disk->exists($analysis->imageDirectory())) {
                            if (!$dry) {
                                $disk->deleteDirectory($analysis->imageDirectory());
                            }
                            $completed++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('skin:purge-stale no pudo procesar un análisis completado', ['analysis_id' => $analysis->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        // 3) Órdenes sin pagar o con pago fallido, viejas
        SkinAnalysis::query()
            ->whereIn('status', SkinAnalysis::PAYABLE_STATUSES)
            ->where('created_at', '<', now()->subDays($days))
            ->chunkById(200, function ($chunk) use ($disk, $dry, &$unpaid, &$failed) {
                foreach ($chunk as $analysis) {
                    try {
                        if (!$dry) {
                            $disk->deleteDirectory($analysis->imageDirectory());
                            // Sin foto la orden ya no se puede pagar ni procesar.
                            $analysis->forceFill(['status' => 'expired'])->save();
                        }
                        $unpaid++;
                    } catch (\Throwable $e) {
                        // Si no se pudo borrar la foto, la orden no se marca: se reintenta en la próxima corrida.
                        $failed++;
                        Log::warning('skin:purge-stale no pudo vencer una orden', ['analysis_id' => $analysis->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        $prefix = $dry ? '[simulación] ' : '';
        $this->info("{$prefix}Subidas temporales: {$temp} · Completados con fotos: {$completed} · Órdenes sin pagar vencidas: {$unpaid}");

        if ($failed > 0) {
            $this->error("{$failed} elemento(s) no se pudieron procesar (ver storage/logs). Si es por permisos, el comando debe correr como el usuario del servidor web: sudo -u apache php artisan skin:purge-stale");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}