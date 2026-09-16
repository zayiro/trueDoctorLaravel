<?php

namespace App\Jobs;

use App\Models\MedicalAnalysis;
use App\Http\Controllers\MedicalAnalysisController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMedicalAnalysisAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $analysis;

    public function __construct(MedicalAnalysis $analysis)
    {
        $this->analysis = $analysis;
    }

    public function handle()
    {
        Log::info("Job iniciado para análisis #{$this->analysis->id}");

        try {
            // ✅ Llamar tu método existente
            $controller = new MedicalAnalysisController();
            $controller->analyzeWithAI(
                $this->analysis,
                null,
                true,
                $this->analysis->language ?? 'es'
            );

            Log::info("Job completado para análisis #{$this->analysis->id}");
        } catch (\Exception $e) {
            Log::error("Job fallido para análisis #{$this->analysis->id}: " . $e->getMessage());
            $this->analysis->update(['status' => 'failed']);
        }
    }
}