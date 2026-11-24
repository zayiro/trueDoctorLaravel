<?php

namespace App\Console\Commands;

use App\Models\MedicalAnalysis;
use App\Models\SkinAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Si el proceso de un análisis muere de golpe (reinicio de worker, corte), el trabajo no llega a
 * ejecutar failed() y el análisis se queda en "processing" para siempre. Este barrido los pasa a error.
 * Solo toca la base de datos: no borra fotos ni archivos.
 *   - Piel: "processing" sin cambios por más de 30 minutos -> "error".
 *   - Exámenes: "processing" sin cambios por más de 60 minutos -> "failed".
 */
class SweepStuckAnalyses extends Command
{
    protected $signature = 'analyses:sweep-stuck
                            {--skin-minutes=30 : Minutos sin cambios para dar por atascado un análisis de piel}
                            {--exam-minutes=60 : Minutos sin cambios para dar por atascado un examen}
                            {--dry-run : Solo muestra lo que marcaría}';

    protected $description = 'Marca como fallidos los análisis de piel y exámenes que quedaron atascados en "processing"';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $skinCut = now()->subMinutes(max(10, (int) $this->option('skin-minutes')));
        $examCut = now()->subMinutes(max(30, (int) $this->option('exam-minutes')));

        $skin = 0;
        foreach (SkinAnalysis::where('status', SkinAnalysis::STATUS_PROCESSING)->where('updated_at', '<', $skinCut)->pluck('id') as $id) {
            if ($dry) { $skin++; continue; }

            $n = SkinAnalysis::whereKey($id)
                ->where('status', SkinAnalysis::STATUS_PROCESSING)
                ->where('updated_at', '<', $skinCut)
                ->update([
                    'status' => SkinAnalysis::STATUS_ERROR,
                    'failure_reason' => 'El proceso se interrumpió y no terminó (marcado automáticamente).',
                ]);

            if ($n) {
                $skin++;
                Log::warning('analyses.stuck_marked', ['tipo' => 'skin', 'analysis_id' => $id]);
            }
        }

        $exam = 0;
        foreach (MedicalAnalysis::where('status', 'processing')->where('updated_at', '<', $examCut)->pluck('id') as $id) {
            if ($dry) { $exam++; continue; }

            $n = MedicalAnalysis::whereKey($id)
                ->where('status', 'processing')
                ->where('updated_at', '<', $examCut)
                ->update(['status' => 'failed']);

            if ($n) {
                $exam++;
                Log::warning('analyses.stuck_marked', ['tipo' => 'exam', 'analysis_id' => $id]);
            }
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Piel atascados: {$skin} | Exámenes atascados: {$exam}");

        return self::SUCCESS;
    }
}
