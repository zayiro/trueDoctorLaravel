<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cie10\Cie10Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCie10 extends Command
{
    protected $signature = 'cie10:importar
        {archivo : Ruta del archivo CSV (o Excel, si el servidor puede leerlo)}
        {--aplicar : Guarda los cambios. Sin esto solo muestra qué pasaría}
        {--desactivar-faltantes : Desactiva los códigos que ya estaban en la base y no vienen en el archivo}
        {--capitalizar : Pasa descripciones TODAS EN MAYÚSCULAS a «Mayúscula inicial y el resto en minúscula»}';

    protected $description = 'Importa o actualiza el catálogo CIE-10 desde un archivo (por defecto solo simula).';

    public function handle(): int
    {
        $path = (string) $this->argument('archivo');

        if (!is_file($path) || !is_readable($path)) {
            $this->error("FALLA: no puedo leer el archivo $path");

            return self::FAILURE;
        }

        try {
            $content = $this->readFile($path);
            $parsed = (new Cie10Parser())->parse($content);
        } catch (\Throwable $e) {
            $this->error('FALLA: ' . $e->getMessage());

            return self::FAILURE;
        }

        $rows = $parsed['rows'];

        if ($this->option('capitalizar')) {
            foreach ($rows as $k => $r) {
                $d = $r['description'];

                if (mb_strtoupper($d) === $d && preg_match('/\p{L}/u', $d)) {
                    $d = mb_strtolower($d);
                    $rows[$k]['description'] = mb_strtoupper(mb_substr($d, 0, 1)) . mb_substr($d, 1);
                }
            }
        }

        if (count($rows) < 1) {
            $this->error('FALLA: no encontré ningún código válido en el archivo.');

            return self::FAILURE;
        }

        $m = $parsed['meta'];
        $this->info(sprintf('Archivo: %s · codificación %s · separador «%s» · %s', basename($path), $m['encoding'], $m['delimiter'], $m['header'] ? 'con encabezado' : 'sin encabezado'));
        $this->info(sprintf('Columnas usadas: código = %d, descripción = %d%s', $m['code_col'], $m['description_col'], $m['active_col'] ? ', activo = ' . $m['active_col'] : ''));
        $this->info(sprintf('Códigos válidos: %d · filas descartadas: %d', count($rows), $parsed['skipped_total']));

        foreach ($parsed['skipped'] as $s) {
            $this->line('   descartada: ' . $s);
        }

        $existing = DB::table('cie10_codes')->get(['code', 'description', 'is_active'])->keyBy('code');
        $new = 0;
        $changed = 0;
        $same = 0;

        foreach ($rows as $code => $r) {
            $e = $existing->get($code);

            if (!$e) {
                $new++;
            } elseif ($e->description !== $r['description'] || (bool) $e->is_active !== $r['is_active']) {
                $changed++;
            } else {
                $same++;
            }
        }

        $missing = $existing->keys()->diff(array_keys($rows))->values();
        $this->info(sprintf('Nuevos: %d · se actualizan: %d · sin cambios: %d · en la base y no en el archivo: %d', $new, $changed, $same, $missing->count()));

        if ($missing->isNotEmpty()) {
            $this->line('   ejemplos que no vienen en el archivo: ' . $missing->take(8)->implode(', '));
        }

        $this->line('Ejemplos del archivo:');

        foreach (array_slice($rows, 0, 6, true) as $r) {
            $this->line(sprintf('   %-8s %s', $r['code'], mb_substr($r['description'], 0, 90)));
        }

        if (!$this->option('aplicar')) {
            $this->warn('SIMULACIÓN: no se guardó nada. Si todo se ve bien, repite el comando agregando --aplicar');

            return self::SUCCESS;
        }

        $now = now();
        $before = (int) DB::table('cie10_codes')->count();

        try {
            DB::transaction(function () use ($rows, $now, $missing) {
                foreach (array_chunk(array_values($rows), 500) as $chunk) {
                    $batch = array_map(fn ($r) => [
                        'code' => $r['code'],
                        'description' => $r['description'],
                        'is_active' => $r['is_active'] ? 1 : 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $chunk);

                    DB::table('cie10_codes')->upsert($batch, ['code'], ['description', 'is_active', 'updated_at']);
                }

                if ($this->option('desactivar-faltantes') && $missing->isNotEmpty()) {
                    foreach ($missing->chunk(500) as $chunk) {
                        DB::table('cie10_codes')->whereIn('code', $chunk->all())->update(['is_active' => 0, 'updated_at' => $now]);
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('FALLA: no se guardó nada (' . $e->getMessage() . ')');

            return self::FAILURE;
        }

        $this->info(sprintf('LISTO: el catálogo pasó de %d a %d códigos.', $before, (int) DB::table('cie10_codes')->count()));

        return self::SUCCESS;
    }

    private function readFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                throw new \RuntimeException('Este servidor no puede leer Excel. Abre el archivo y guárdalo como «CSV UTF-8 (delimitado por comas)», luego usa ese .csv.');
            }

            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
            $h = fopen('php://temp', 'r+');

            foreach ($sheet as $row) {
                fputcsv($h, array_map(fn ($v) => (string) $v, $row), ',', '"', '');
            }

            rewind($h);
            $out = (string) stream_get_contents($h);
            fclose($h);

            return $out;
        }

        return (string) file_get_contents($path);
    }
}
