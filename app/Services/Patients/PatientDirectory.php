<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Directorio de pacientes ATENDIDOS: los que tienen al menos una cita completada (Zoom o presencial)
 * con el médico, o en las sedes de la clínica. Una cita cancelada, expirada o sin completar no cuenta.
 *
 * $scope: ['doctor_id' => int]  ó  ['clinic_id' => int, 'address_ids' => int[]]
 */
class PatientDirectory
{
    public const SEARCH_MIN_LENGTH = 3;

    public function paginate(array $scope, bool $canSearch, int $cap, ?string $query, int $perPage = 15): LengthAwarePaginator
    {
        $query = $query !== null ? trim($query) : null;
        $searching = $canSearch && $query !== null && mb_strlen($query) >= self::SEARCH_MIN_LENGTH;

        $apply = function ($q) use ($scope) {
            $q->whereNull('a.deleted_at');
            if (!empty($scope['doctor_id'])) {
                $q->where('a.doctor_id', (int) $scope['doctor_id']);
            } else {
                $q->where(function ($w) use ($scope) {
                    $w->where('a.clinic_id', (int) ($scope['clinic_id'] ?? 0));
                    if (!empty($scope['address_ids'])) {
                        $w->orWhereIn('a.address_id', array_map('intval', $scope['address_ids']));
                    }
                });
            }
        };

        $agg = DB::table('appointments as a')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('a.status', 'completed');
        $apply($agg);

        if ($searching) {
            $like = '%' . addcslashes($query, '\\%_') . '%';
            // La cédula está cifrada: se busca exacta por su índice ciego (con y sin puntuación).
            $hashes = array_values(array_unique([
                Patient::generateBlindIndex($query),
                Patient::generateBlindIndex((string) preg_replace('/[^0-9A-Za-z]/', '', $query)),
            ]));
            $agg->where(function ($w) use ($like, $hashes) {
                $w->where('u.name', 'like', $like)
                  ->orWhere('u.email', 'like', $like)
                  ->orWhereIn('p.identification_blind_index', $hashes);
            });
        }

        $agg->groupBy('p.id')->selectRaw('p.id as patient_id, COUNT(*) as consultas, MAX(a.date) as ultima');

        if (!$canSearch) {
            // Sin buscador: solo los últimos N atendidos que permite el plan.
            $ids = (clone $agg)->orderByDesc('ultima')->orderByDesc('patient_id')->limit(max(0, $cap))->pluck('patient_id')->all();
            $agg->whereIn('p.id', $ids ?: [0]);
        }

        $page = $agg->orderByDesc('ultima')->orderByDesc('patient_id')->paginate($perPage);
        $rows = $page->getCollection();
        $ids = $rows->pluck('patient_id')->map(fn ($v) => (int) $v)->all();

        $patients = $ids ? Patient::with('user')->whereIn('id', $ids)->get()->keyBy('id') : collect();

        $next = collect();
        $last = collect();
        if ($ids) {
            $nextQ = DB::table('appointments as a')->whereIn('a.patient_id', $ids)->where('a.status', 'confirmed')
                ->where('a.date', '>=', Carbon::today()->toDateString());
            $apply($nextQ);
            $next = $nextQ->groupBy('a.patient_id')->selectRaw('a.patient_id as pid, MIN(a.date) as proxima')->pluck('proxima', 'pid');

            $lastQ = DB::table('appointments as a')
                ->join('doctors as d', 'd.id', '=', 'a.doctor_id')
                ->join('users as du', 'du.id', '=', 'd.user_id')
                ->whereIn('a.patient_id', $ids)->where('a.status', 'completed');
            $apply($lastQ);
            $last = $lastQ->orderByDesc('a.date')->orderByDesc('a.start_time')->get(['a.patient_id as pid', 'du.name as medico'])
                ->unique('pid')->pluck('medico', 'pid');
        }

        $items = $rows->map(function ($r) use ($patients, $next, $last) {
            $p = $patients[(int) $r->patient_id] ?? null;
            if (!$p) {
                return null;
            }
            $doc = null;
            try {
                $raw = (string) $p->identification;
                $doc = $raw !== '' ? '****' . substr($raw, -4) : null;
            } catch (\Throwable $e) {
                $doc = null;
            }

            return (object) [
                'patient'   => $p,
                'documento' => $doc,
                'consultas' => (int) $r->consultas,
                'ultima'    => Carbon::parse($r->ultima),
                'proxima'   => isset($next[$r->patient_id]) ? Carbon::parse($next[$r->patient_id]) : null,
                'medico'    => $last[$r->patient_id] ?? null,
            ];
        })->filter()->values();

        $page->setCollection($items);

        return $page;
    }
}
