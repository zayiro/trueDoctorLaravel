<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Carbon\Carbon;

/**
 * Arma, para cada línea de negocio de config/business_lines.php, lo que pinta el panel:
 * KPIs, embudo (con % contra el paso anterior y contra el primero), conversión y datos reales de la base de datos.
 *
 * $ga es el GoogleAnalyticsService (o cualquier objeto con getEventCounts() y getPageViewsByPath()).
 */
final class BusinessLineReport
{
    public function __construct(private readonly object $ga, private readonly ?BusinessLineDb $db = null)
    {
    }

    /**
     * @param array<string, array<string, mixed>> $lines
     * @return array<string, array<string, mixed>>
     */
    public function build(array $lines, string $start, string $end): array
    {
        $needsEvents = false;
        $needsPages = false;

        foreach ($lines as $line) {
            foreach (array_merge($line['funnel'] ?? [], $line['side'] ?? []) as $step) {
                $needsEvents = $needsEvents || isset($step['event']);
                $needsPages = $needsPages || isset($step['page']);
            }
        }

        [$events, $eventsError] = $needsEvents ? $this->safe(fn () => $this->ga->getEventCounts($start, $end)) : [[], null];
        [$pages, $pagesError] = $needsPages ? $this->safe(fn () => $this->ga->getPageViewsByPath($start, $end)) : [[], null];

        $from = Carbon::parse($start, (string) config('app.timezone'))->startOfDay();
        $to = Carbon::parse($end, (string) config('app.timezone'))->endOfDay();

        $out = [];

        foreach ($lines as $key => $line) {
            $out[$key] = $this->line((string) $key, $line, $events, $eventsError, $pages, $pagesError, $from, $to);
        }

        return $out;
    }

    /** @return array{0: mixed, 1: ?string} */
    private function safe(callable $fn): array
    {
        try {
            return [$fn(), null];
        } catch (\Throwable $e) {
            return [[], $e->getMessage()];
        }
    }

    private function line(string $key, array $line, array $events, ?string $eventsError, array $pages, ?string $pagesError, Carbon $from, Carbon $to): array
    {
        $usesEvents = false;
        $usesPages = false;

        foreach (array_merge($line['funnel'] ?? [], $line['side'] ?? []) as $step) {
            $usesEvents = $usesEvents || isset($step['event']);
            $usesPages = $usesPages || isset($step['page']);
        }

        $result = [
            'key' => $key,
            'label' => (string) ($line['label'] ?? $key),
            'emoji' => (string) ($line['emoji'] ?? ''),
            'color' => (string) ($line['color'] ?? '#4f46e5'),
            'soft' => (string) ($line['soft'] ?? '#eef2ff'),
            'notes' => (array) ($line['notes'] ?? []),
            'error' => null,
            'kpis' => [],
            'funnel' => [],
            'side' => [],
            'conversion' => null,
            'business' => null,
            'business_error' => null,
            'has_data' => false,
        ];

        $gaError = ($usesEvents ? $eventsError : null) ?? ($usesPages ? $pagesError : null);

        if ($gaError !== null) {
            $result['error'] = $gaError;
        } else {
            $counts = [];

            foreach ((array) ($line['funnel'] ?? []) as $step) {
                [$count, $users] = $this->measure($step, $events, $pages);
                $counts[(string) $step['key']] = $count;
                $result['funnel'][] = ['key' => (string) $step['key'], 'label' => (string) $step['label'], 'count' => $count, 'users' => $users, 'kind' => isset($step['event']) ? 'event' : 'page', 'code' => (string) ($step['event'] ?? ($step['page']['exact'] ?? $step['page']['prefix'] ?? ''))];
            }

            $prev = null;
            $first = $result['funnel'][0]['count'] ?? 0;

            foreach ($result['funnel'] as $i => $step) {
                $result['funnel'][$i]['pct_prev'] = ($prev !== null && $prev > 0) ? round($step['count'] / $prev * 100, 1) : null;
                $result['funnel'][$i]['pct_first'] = $first > 0 ? round($step['count'] / $first * 100, 1) : 0.0;
                $prev = $step['count'];
            }

            foreach ((array) ($line['side'] ?? []) as $step) {
                [$count, $users] = $this->measure($step, $events, $pages);
                $result['side'][] = ['key' => (string) $step['key'], 'label' => (string) $step['label'], 'count' => $count, 'users' => $users, 'code' => (string) ($step['event'] ?? '')];
            }

            $total = array_sum(array_column($result['funnel'], 'count')) + array_sum(array_column($result['side'], 'count'));
            $maxUsers = max(array_merge([0], array_column($result['funnel'], 'users'), array_column($result['side'], 'users')));
            $result['has_data'] = $total > 0;
            $result['kpis'] = [
                ['label' => $usesEvents ? 'Total de eventos' : 'Total de vistas', 'value' => $total, 'hint' => 'En los últimos 7 días'],
                ['label' => 'Usuarios únicos', 'value' => $maxUsers, 'hint' => 'El mayor número de usuarios en un paso'],
            ];
        }

        // Datos reales de la base de datos (independientes de Google Analytics)
        if (!empty($line['db']) && $this->db !== null) {
            try {
                $result['business'] = $this->db->metrics((string) $line['db'], $from, $to);
                $result['has_data'] = $result['has_data'] || array_sum(array_column($result['business'], 'value')) > 0;
            } catch (\Throwable $e) {
                $result['business_error'] = 'No se pudieron leer los datos de la base de datos: ' . $e->getMessage();
            }
        }

        $result['conversion'] = $this->conversion($line, $result);

        $result['kpis'][] = [
            'label' => 'Tasa de conversión',
            'value' => $result['conversion'] === null ? '—' : number_format($result['conversion']['rate'], 1, ',', '.') . '%',
            'hint' => $result['conversion']['label'] ?? 'Sin datos suficientes',
        ];

        return $result;
    }

    /** @return array{0:int,1:int} [conteo, usuarios] */
    private function measure(array $step, array $events, array $pages): array
    {
        if (isset($step['event'])) {
            $e = $events[$step['event']] ?? null;

            return [(int) ($e['count'] ?? 0), (int) ($e['users'] ?? 0)];
        }

        $views = 0;
        $users = 0;

        foreach ($pages as $path => $p) {
            if ($this->pathMatches((string) $path, (array) ($step['page'] ?? []))) {
                $views += (int) ($p['views'] ?? 0);
                $users = max($users, (int) ($p['users'] ?? 0));
            }
        }

        return [$views, $users];
    }

    /** exact: coincide exactamente (con o sin «/» final). prefix: empieza con el prefijo. exclude: se descarta si empieza con eso. */
    public function pathMatches(string $path, array $spec): bool
    {
        if (isset($spec['exclude']) && str_starts_with($path, (string) $spec['exclude'])) {
            return false;
        }

        if (isset($spec['exact'])) {
            return rtrim($path, '/') === rtrim((string) $spec['exact'], '/');
        }

        if (isset($spec['prefix'])) {
            return str_starts_with($path, (string) $spec['prefix']);
        }

        return false;
    }

    /** @return array{rate: float, from: int, to: int, label: string}|null */
    private function conversion(array $line, array $result): ?array
    {
        $conv = $line['conversion'] ?? null;

        if (!$conv || ($result['error'] ?? null) !== null) {
            return null;
        }

        $steps = array_column($result['funnel'], 'count', 'key');
        $from = $steps[$conv['from']] ?? null;
        $to = null;

        if (str_starts_with((string) $conv['to'], 'db:')) {
            $want = substr((string) $conv['to'], 3);

            foreach ((array) ($result['business'] ?? []) as $b) {
                if ($b['key'] === $want) {
                    $to = (int) $b['value'];
                }
            }
        } else {
            $to = $steps[$conv['to']] ?? null;
        }

        if ($from === null || $to === null || $from <= 0) {
            return null;
        }

        return ['rate' => round($to / $from * 100, 1), 'from' => (int) $from, 'to' => (int) $to, 'label' => (string) ($conv['label'] ?? '')];
    }
}
