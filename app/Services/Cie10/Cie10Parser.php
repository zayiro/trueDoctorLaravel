<?php

declare(strict_types=1);

namespace App\Services\Cie10;

/**
 * Lee un catálogo CIE-10 en CSV (coma, punto y coma, tabulador o barra) en UTF-8 o Windows-1252/ISO-8859-1,
 * con o sin encabezado, y devuelve los códigos limpios. No toca la base de datos.
 */
final class Cie10Parser
{
    private const CODE_HEADS = ['codigo', 'cod', 'code', 'cie10', 'codigocie10', 'codigocie', 'clave', 'codigodiagnostico', 'codcie10', 'cod4'];
    /** En orden de preferencia: si el archivo trae «Nombre» y «Descripción», la descripción del código es «Nombre». */
    private const DESC_HEADS = ['descripcioncod4', 'nombre', 'nombrediagnostico', 'nombrecodigo', 'descripciondiagnostico', 'descripcioncodigo', 'diagnostico', 'description', 'descripcion', 'desc', 'detalle'];
    private const ACTIVE_HEADS = ['habilitado', 'activo', 'estado', 'isactive', 'vigente'];

    /**
     * @return array{rows: array<string, array{code: string, description: string, is_active: bool}>, skipped: array<int, string>, skipped_total: int, meta: array<string, mixed>}
     */
    public function parse(string $content): array
    {
        $encoding = 'UTF-8';
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
            $encoding = 'Windows-1252 → UTF-8';
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $delimiter = $this->delimiter($content);

        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);

        $records = [];

        while (($r = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
            if ($r === [null] || $r === []) {
                continue;
            }

            $records[] = array_map(fn ($v) => trim((string) $v), $r);
        }

        fclose($h);

        [$ci, $di, $ai, $start, $hasHeader] = $this->columns($records);

        $rows = [];
        $skipped = [];
        $skippedTotal = 0;

        for ($i = $start; $i < count($records); $i++) {
            $r = $records[$i];
            $code = Cie10Format::normalize($r[$ci] ?? '');
            $desc = $this->cleanDescription($r[$di] ?? '');

            if ($code === null || $desc === '') {
                $skippedTotal++;

                if (count($skipped) < 12) {
                    $skipped[] = mb_substr(implode(' | ', array_slice($r, 0, 3)), 0, 90);
                }

                continue;
            }

            $rows[$code] = ['code' => $code, 'description' => $desc, 'is_active' => $ai === null ? true : $this->truthy($r[$ai] ?? '1')];
        }

        return [
            'rows' => $rows,
            'skipped' => $skipped,
            'skipped_total' => $skippedTotal,
            'meta' => [
                'encoding' => $encoding,
                'delimiter' => $delimiter === "\t" ? 'tabulador' : $delimiter,
                'header' => $hasHeader,
                'lines' => count($records),
                'code_col' => $ci + 1,
                'description_col' => $di + 1,
                'active_col' => $ai === null ? null : $ai + 1,
            ],
        ];
    }

    private function delimiter(string $content): string
    {
        $sample = implode("\n", array_slice(explode("\n", $content), 0, 8));
        $best = ',';
        $max = -1;

        foreach ([',', ';', "\t", '|'] as $d) {
            $n = substr_count($sample, $d);

            if ($n > $max) {
                $max = $n;
                $best = $d;
            }
        }

        return $best;
    }

    private function key(string $v): string
    {
        $v = mb_strtolower($v);
        $v = strtr($v, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return (string) preg_replace('/[^a-z0-9]/', '', $v);
    }

    /** @return array{0:int,1:int,2:?int,3:int,4:bool} */
    private function columns(array $records): array
    {
        foreach (array_slice($records, 0, 12, true) as $idx => $r) {
            $ci = $di = $ai = null;

            $bestRank = PHP_INT_MAX;

            foreach ($r as $col => $cell) {
                $k = $this->key($cell);
                $rank = array_search($k, self::DESC_HEADS, true);

                if ($ci === null && in_array($k, self::CODE_HEADS, true)) {
                    $ci = $col;
                } elseif ($rank !== false && $rank < $bestRank) {
                    $bestRank = $rank;
                    $di = $col;
                } elseif ($ai === null && in_array($k, self::ACTIVE_HEADS, true)) {
                    $ai = $col;
                }
            }

            if ($ci !== null && $di !== null) {
                return [$ci, $di, $ai, $idx + 1, true];
            }
        }

        $first = $records[0] ?? [];

        if (count($first) >= 2 && Cie10Format::normalize($first[0] ?? '') !== null) {
            return [0, 1, null, 0, false];
        }

        throw new \RuntimeException('No reconozco las columnas del archivo. Necesito una columna de código y otra de descripción (por ejemplo «Código» y «Nombre»).');
    }

    private function cleanDescription(string $d): string
    {
        // El archivo de SISPRO quitó las comas: donde había una coma quedó un doble espacio («Cólera  no especificado»).
        $d = trim($d);
        $d = (string) preg_replace('/ {2,}/u', ', ', $d);
        $d = trim((string) preg_replace('/\s+/u', ' ', $d));

        return mb_strlen($d) > 255 ? mb_substr($d, 0, 255) : $d;
    }

    private function truthy(string $v): bool
    {
        return !in_array($this->key($v), ['0', 'no', 'n', 'false', 'inactivo', 'deshabilitado', 'noaplica', 'inactiva'], true);
    }
}
