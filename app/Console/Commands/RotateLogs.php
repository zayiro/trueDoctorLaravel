<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RotateLogs extends Command
{
    protected $signature = 'logs:rotate';
    protected $description = 'Rotate Laravel logs by copying to dated file and clearing current log';

    public function handle()
    {
        $logPath = storage_path('logs');
        $currentLogFile = $logPath . '/laravel.log';

        // Verificar que el archivo existe
        if (!file_exists($currentLogFile)) {
            $this->info('El archivo laravel.log no existe.');
            return 0;
        }

        // Obtener la fecha del día anterior
        $yesterday = Carbon::yesterday()->format('Ymd');
        $archivedLogFile = $logPath . "/laravel_{$yesterday}.log";

        try {
            // Copiar el contenido actual al archivo con fecha
            copy($currentLogFile, $archivedLogFile);
            $this->info("✓ Log archivado en: laravel_{$yesterday}.log");

            // Limpiar el contenido del archivo actual
            file_put_contents($currentLogFile, '');
            $this->info('✓ laravel.log limpiado');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error al rotar logs: ' . $e->getMessage());
            return 1;
        }
    }
}