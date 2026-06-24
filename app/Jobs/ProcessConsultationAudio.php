<?php

namespace App\Jobs;

use App\Services\AI\Contracts\AITranscriptionDriver;
use App\Services\AI\Contracts\AIScribeDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessConsultationAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        protected string $jobToken,
        protected string $tmpDisk,
        protected string $tmpPath,
        protected string $mimeType,
        protected int    $doctorId,
        protected int    $patientId,
    ) {}

    public function handle(AITranscriptionDriver $transcriptionDriver, AIScribeDriver $scribeDriver): void
    {
        $cacheKey = "ai_scribe:{$this->jobToken}";

        try {
            // ── 1. Transcribir audio con Deepgram ────────────────────────
            Cache::put($cacheKey, ['status' => 'transcribing'], now()->addMinutes(15));

            $localPath  = Storage::disk($this->tmpDisk)->path($this->tmpPath);
            $transcript = $transcriptionDriver->transcribe($localPath, $this->mimeType);

            // ── 2. Estructurar en SOAP con DeepSeek ──────────────────────
            Cache::put($cacheKey, ['status' => 'structuring'], now()->addMinutes(15));

            $structured = $scribeDriver->structureConsultation($transcript);

            // ── 3. Guardar resultado en caché — ya viene en formato SOAP ─
            Cache::put($cacheKey, [
                'status'     => 'ready',
                'transcript' => $transcript,
                'soap'       => [
                    'entry_type'      => $structured['entry_type'],
                    'cie10_code'      => $structured['cie10_code'],
                    'subjective'      => $structured['soap_subjective'],
                    'objective'       => $structured['soap_objective'],
                    'assessment'      => $structured['soap_assessment'],
                    'plan'            => $structured['soap_plan'],
                ],
                'medication_suggestion' => $structured['medication_suggestion'],
            ], now()->addMinutes(15));

        } catch (\Throwable $e) {
            Log::error("ProcessConsultationAudio falló [{$this->jobToken}]: " . $e->getMessage());

            Cache::put($cacheKey, [
                'status' => 'failed',
                'error'  => 'No se pudo procesar el audio. Intenta nuevamente o ingresa la nota manualmente.',
            ], now()->addMinutes(15));

        } finally {
            // Audio temporal: se borra siempre, haya éxito o falla
            Storage::disk($this->tmpDisk)->delete($this->tmpPath);
        }
    }
}