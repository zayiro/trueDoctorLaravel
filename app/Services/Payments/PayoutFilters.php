<?php

namespace App\Services\Payments;

use App\Models\Clinic;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtros de la pantalla de Liquidaciones y de su exportación (una sola fuente de la verdad).
 */
class PayoutFilters
{
    public const DATE_FIELDS = [
        'cita'        => 'Fecha de la cita',
        'cobro'       => 'Fecha del cobro al paciente',
        'liquidacion' => 'Fecha de liquidación',
    ];

    public static function normalize(array $in): array
    {
        $pick = fn ($v, array $allowed, $default) => in_array($v, $allowed, true) ? $v : $default;
        $date = function ($v) {
            if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) { return ''; }
            try { return Carbon::createFromFormat('Y-m-d', $v)->format('Y-m-d') === $v ? $v : ''; } catch (\Throwable $e) { return ''; }
        };

        return [
            'status'      => $pick($in['status'] ?? null, ['pending', 'paid', 'all'], 'pending'),
            'modalidad'   => $pick($in['modalidad'] ?? null, ['virtual', 'presencial'], ''),
            'beneficiario' => $pick($in['beneficiario'] ?? null, ['doctor', 'clinic'], ''),
            'date_field'  => $pick($in['date_field'] ?? null, array_keys(self::DATE_FIELDS), 'cita'),
            'from'        => $date($in['from'] ?? null),
            'to'          => $date($in['to'] ?? null),
            'batch'       => (is_string($in['batch'] ?? null) || is_int($in['batch'] ?? null)) && ctype_digit((string) $in['batch']) ? (int) $in['batch'] : 0,
            'q'           => is_string($in['q'] ?? null) ? mb_substr(trim($in['q']), 0, 100) : '',
        ];
    }

    public static function apply(Builder $q, array $f): Builder
    {
        if ($f['status'] !== 'all') {
            $q->where('status', $f['status']);
        }

        if ($f['modalidad'] === 'virtual') {
            $q->whereHas('appointment.service', fn ($s) => $s->where('type', 'virtual'));
        } elseif ($f['modalidad'] === 'presencial') {
            $q->whereHas('appointment', fn ($a) => $a->whereDoesntHave('service', fn ($s) => $s->where('type', 'virtual')));
        }

        if (!empty($f['batch'])) { $q->where('batch_id', $f['batch']); }

        if ($f['beneficiario'] === 'doctor') { $q->where('payable_type', Doctor::class); }
        if ($f['beneficiario'] === 'clinic') { $q->where('payable_type', Clinic::class); }

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $q->where(function ($w) use ($like) {
                $w->whereHas('appointment.doctor.user', fn ($u) => $u->where('name', 'like', $like))
                  ->orWhereHas('appointment.clinic.user', fn ($u) => $u->where('name', 'like', $like))
                  ->orWhere('transfer_reference', 'like', $like);
            });
        }

        if ($f['from'] !== '' || $f['to'] !== '') {
            $tz  = 'America/Bogota';
            $app = config('app.timezone', 'UTC');
            $ini = $f['from'] !== '' ? Carbon::parse($f['from'], $tz)->startOfDay()->setTimezone($app) : null;
            $fin = $f['to'] !== '' ? Carbon::parse($f['to'], $tz)->endOfDay()->setTimezone($app) : null;

            if ($f['date_field'] === 'cita') {
                $q->whereHas('appointment', function ($a) use ($f) {
                    if ($f['from'] !== '') { $a->whereDate('date', '>=', $f['from']); }
                    if ($f['to'] !== '') { $a->whereDate('date', '<=', $f['to']); }
                });
            } elseif ($f['date_field'] === 'cobro') {
                $q->whereHas('payment', function ($p) use ($ini, $fin) {
                    if ($ini) { $p->where('approved_at', '>=', $ini); }
                    if ($fin) { $p->where('approved_at', '<=', $fin); }
                });
            } else {
                if ($ini) { $q->where('paid_at', '>=', $ini); }
                if ($fin) { $q->where('paid_at', '<=', $fin); }
            }
        }

        return $q;
    }

    /** Subtotales de TODO el filtro (no solo de la página visible). */
    public static function totals(Builder $q): object
    {
        $t = (clone $q)->toBase()->reorder()->selectRaw(
            'count(*) as n, coalesce(sum(total_charged),0) as total_charged, coalesce(sum(wompi_fee),0) as wompi_fee, '
            . 'coalesce(sum(platform_commission),0) as platform_commission, coalesce(sum(amount_to_pay),0) as amount_to_pay'
        )->first();

        return $t ?: (object) ['n' => 0, 'total_charged' => 0, 'wompi_fee' => 0, 'platform_commission' => 0, 'amount_to_pay' => 0];
    }
}
