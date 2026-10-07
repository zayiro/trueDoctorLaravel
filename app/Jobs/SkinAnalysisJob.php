<?php

namespace App\Jobs;

use App\Models\SkinAnalysis;
use App\Services\SkinAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SkinAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Intentos totales antes de dar el análisis por fallido. */
    public $tries = 2;

    /** Segundos de espera entre intentos. */
    public $backoff = 30;

    /** El análisis con imágenes y la API externa puede tardar entre 15 y 60 segundos. */
    public $timeout = 120;

    public function __construct(public SkinAnalysis $analysis)
    {
    }

    public function handle(SkinAnalysisService $skinAnalysisService): void
    {
        // Estado fresco desde la BD: si ya no está en procesamiento (completado, error), no se repite.
        $current = $this->analysis->fresh();

        if (!$current || $current->status !== SkinAnalysis::STATUS_PROCESSING) {
            return;
        }

        // Si falla, la excepción sube y la cola reintenta; el estado "error" se marca en failed().
        $skinAnalysisService->analyzeWithAI($current);
    }

    /**
     * Se ejecuta solo cuando se agotaron todos los intentos.
     * Pasa el análisis a "error" para que la vista de resultado deje de esperar.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('SkinAnalysisJob falló definitivamente', [
            'analysis_id' => $this->analysis->id,
            'error' => $e->getMessage(),
        ]);

        SkinAnalysis::whereKey($this->analysis->id)
            ->where('status', SkinAnalysis::STATUS_PROCESSING)
            ->update([
                'status' => SkinAnalysis::STATUS_ERROR,
                'failure_reason' => Str::limit($e->getMessage(), 250, ''),
            ]);
    }
}