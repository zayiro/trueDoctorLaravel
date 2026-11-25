<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Patients\PatientAccessLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Registro de accesos a fichas de pacientes, visto por tres actores:
 *  - el paciente: quién ha visto su historia (nombre del profesional, fecha y qué hizo);
 *  - el médico: sus propios accesos;
 *  - el administrador: auditoría global SIN datos de salud (el paciente aparece enmascarado).
 */
class AccessLogController extends Controller
{
    private const PER_PAGE = 20;

    /** Aplica el rango de fechas (desde/hasta) si vienen y son válidas. */
    private function applyDates($query, Request $request)
    {
        foreach (['desde' => '>=', 'hasta' => '<='] as $field => $op) {
            $value = trim((string) $request->input($field));
            if ($value !== '' && strtotime($value) !== false) {
                $query->whereDate('l.created_at', $op, Carbon::parse($value)->toDateString());
            }
        }

        return $query;
    }

    /** Paciente: quién ha visto mi historia. */
    public function mine(Request $request)
    {
        $user = Auth::user();
        $patient = $user?->patient;

        $logs = DB::table('patient_access_logs as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.patient_id', (int) ($patient->id ?? 0))
            ->whereNotIn('l.action', ['denegado', 'consola'])
            ->orderByDesc('l.id')
            ->select('l.id', 'l.action', 'l.created_at', 'u.name as actor');
        $this->applyDates($logs, $request);

        return view('patient.access-log.index', [
            'logs' => $logs->paginate(self::PER_PAGE)->withQueryString(),
        ]);
    }

    /** Médico: sus propios accesos. */
    public function doctor(Request $request)
    {
        $user = Auth::user();
        $user?->doctor ?? abort(403, 'Perfil de médico no encontrado o no configurado.');

        $logs = DB::table('patient_access_logs as l')
            ->join('patients as p', 'p.id', '=', 'l.patient_id')
            ->join('users as pu', 'pu.id', '=', 'p.user_id')
            ->where('l.user_id', $user->id)
            ->orderByDesc('l.id')
            ->select('l.id', 'l.action', 'l.created_at', 'l.patient_id', 'pu.name as paciente');
        $this->applyDates($logs, $request);

        return view('partner.access-log.index', [
            'logs' => $logs->paginate(self::PER_PAGE)->withQueryString(),
        ]);
    }

    /** Administrador: auditoría global. El paciente se muestra enmascarado; nunca datos de salud. */
    public function admin(Request $request)
    {
        $logs = DB::table('patient_access_logs as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->join('patients as p', 'p.id', '=', 'l.patient_id')
            ->join('users as pu', 'pu.id', '=', 'p.user_id')
            ->orderByDesc('l.id')
            ->select('l.id', 'l.action', 'l.created_at', 'l.ip', 'l.patient_id', 'u.name as actor', 'u.role as actor_role', 'pu.name as paciente_nombre');
        $this->applyDates($logs, $request);

        $action = (string) $request->input('action');
        if ($action !== '' && array_key_exists($action, PatientAccessLog::LABELS)) {
            $logs->where('l.action', $action);
        }
        $actor = trim((string) $request->input('actor'));
        if ($actor !== '') {
            $logs->where('u.name', 'like', '%' . addcslashes($actor, '\\%_') . '%');
        }
        $patientId = trim((string) $request->input('paciente'));
        if ($patientId !== '' && ctype_digit($patientId)) {
            $logs->where('l.patient_id', (int) $patientId);
        }

        $page = $logs->paginate(self::PER_PAGE)->withQueryString();
        // El administrador no ve el nombre del paciente: solo su número y las iniciales.
        $page->through(function ($r) {
            $initials = collect(preg_split('/\s+/', trim((string) $r->paciente_nombre)))->filter()
                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(3)->implode('.');

            return (object) [
                'id'          => $r->id,
                'action'      => $r->action,
                'created_at'  => $r->created_at,
                'ip'          => $r->ip,
                'actor'       => $r->actor,
                'actor_role'  => $r->actor_role,
                'paciente'    => 'Paciente #' . $r->patient_id . ($initials !== '' ? ' · ' . $initials . '.' : ''),
            ];
        });

        $count = fn ($since, $onlyDenied = false) => DB::table('patient_access_logs')
            ->where('created_at', '>=', $since)
            ->when($onlyDenied, fn ($q) => $q->where('action', 'denegado'))
            ->count();

        return view('administrator.access-log.index', [
            'logs'   => $page,
            'stats'  => [
                'hoy'        => $count(now()->startOfDay()),
                'semana'     => $count(now()->subDays(7)),
                'bloqueados' => $count(now()->subDays(7), true),
            ],
            'labels' => PatientAccessLog::LABELS,
        ]);
    }
}
