<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWeeklyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $clinic;

    public function __construct($clinic)
    {
        $this->clinic = $clinic;
        $this->onQueue('low'); // Enviar a cola baja
    }

    public function handle()
    {
        // Lógica para generar reporte semanal
        // Este job se procesará en la cola 'low' con menor prioridad
    }
}
