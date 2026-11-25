<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\SearchLog;
use App\Models\PromoCode;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('doctorSettings');        
        $now = Carbon::now();

        $usersByRole = [];
        $popularSpecialties = []; 
        $popularCities = [];

        // 1. CONDICIONAL DE SEGURIDAD MULTI-PERFIL
        if ($user->role === 'admin') {
            $owner = null;
            $ownerColumn = null;

            $popularSpecialties = SearchLog::topSpecialties(15);
            $popularCities = SearchLog::topCities(15);

            $usersByRole = User::select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role')
                ->toArray();
        } elseif ($user->role === 'clinic') {
            $owner = $user->clinic;
            $ownerColumn = 'clinic_id';
        } elseif ($user->role === 'doctor') {
            $owner = $user->doctor;
            $ownerColumn = 'doctor_id';
        } else {
            $owner = $user->patient;
            $ownerColumn = 'patient_id';
        }
        
        if ($user->role !== 'admin' && !$owner) {
            return redirect()->route('profile.show')->with('error', 'Perfil no encontrado.');
        }

        $promoCode = null;
        
        if ($user->role === 'doctor' || $user->role === 'patient') {
            // Busca el código si es GLOBAL o si pertenece al USUARIO LOGUEADO
            $promoCode = PromoCode::where('user_id', $user->id)->first();            
            if(!$promoCode) {
                $promoCode = Str::upper(Str::random(7));

                // Si no lo tiene, lo crea y lo retorna automáticamente
                PromoCode::create([
                    'user_id'    => $user->id,
                    'code'       => $promoCode, // Ej: DA8F3KL
                    'applies_to' => [
                        PromoCode::MODULE_EXAM,
                        PromoCode::MODULE_SKIN_ANALYSIS,
                    ],
                    'type'       => 'percent',
                    'reward'     => 100.00, // 100% de descuento de bienvenida
                    'max_uses'   => 1,     // Un solo uso permitido
                    'expires_at' => null,  // Nunca expira (nulable)
                    'is_active'  => true,
                ]);                
            }
        }

                // =========================================================================
        // FLUJO ESPECÍFICO PARA EL ROL DE PACIENTE (PATIENT)
        // =========================================================================
        if ($user->role === 'patient') {
            
            // Cargamos únicamente los conteos de las relaciones clínicas (sin traer todos los registros)
            $owner->loadCount([
                'allergies',
                'medications' => fn ($query) => $query->where('active', true), // Solo medicamentos activos
                'surgeries',
                'familyHistories',
                'attachments',
            ]);

            $upcomingAppointments = Appointment::where('patient_id', $owner->id)
                ->whereDate('date', '>=', $now->toDateString())
                ->whereIn('status', ['confirmed', 'pending'])
                ->with('doctor.user')
                ->orderBy('date', 'asc')
                ->take(5)
                ->get();

            $pastAppointments = Appointment::where('patient_id', $owner->id)
                ->whereIn('status', ['completed'])
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            $healthMetrics = [
                'bmi'               => $owner->imc,
                'bmi_status'        => $owner->imc_status,
                'age'               => $owner->age,
                'blood_type'        => $owner->blood_type ?? 'No registrada',
                'allergies_count'   => $owner->allergies_count,
                'medications_count' => $owner->medications_count,
                'surgeries_count'   => $owner->surgeries_count,
                'family_count'      => $owner->family_histories_count,
                'attachments_count' => $owner->attachments_count,
            ];  
            
            return view('admin.dashboard', compact(
                'user', 'owner', 'upcomingAppointments', 'pastAppointments', 'healthMetrics'
            ));
        }

        // =========================================================================
        // FLUJO CORPORATIVO (ADMINISTRADORES, CLÍNICAS Y MÉDICOS)
        // =========================================================================
        $appointmentsQuery = Appointment::query();

        if ($user->role !== 'admin') {
            $appointmentsQuery->where($ownerColumn, $owner->id);
        }

        // =====================================================================
        // ETAPA 1 (dashfix1): definiciones claras y fuentes correctas.
        //  - Una "consulta" cuenta solo si está confirmada o completada (no canceladas, expiradas ni pendientes).
        //  - Modalidad según services.type: 'virtual' y 'physical' (presencial).
        //  - El dinero real sale de pagos aprobados y liquidaciones; el precio de lista es solo referencia.
        // =====================================================================
        $countable = ['confirmed', 'completed'];
        $isAdminUser = $user->role === 'admin';
        $virtualIds = DB::table('services')->where('type', 'virtual')->select('id');
        $physicalIds = DB::table('services')->whereIn('type', ['physical', 'presencial'])->select('id');
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $moneyShort = function (float $v): string {
            $sign = $v < 0 ? '-' : '';
            $a = abs($v);
            if ($a >= 1000000) {
                return $sign . '$' . rtrim(rtrim(number_format($a / 1000000, 2), '0'), '.') . ' M';
            }

            return $sign . '$' . number_format($a, 0);
        };
        $moneyFull = fn (float $v): string => ($v < 0 ? '-' : '') . '$' . number_format(abs($v), 2);
        $tone = fn (float $v): string => $v < 0 ? 'bad' : 'neutral';

        // ========== CONSULTAS DE HOY ==========
        $appointmentsTodayQuery = (clone $appointmentsQuery)
            ->whereDate('date', $now->toDateString())
            ->whereIn('status', $countable);

        $appointmentsToday = (clone $appointmentsTodayQuery)->count();
        $appointmentsTodayVirtual = (clone $appointmentsTodayQuery)->whereIn('service_id', $virtualIds)->count();
        $appointmentsTodayPresencial = (clone $appointmentsTodayQuery)->whereIn('service_id', $physicalIds)->count();

        // ========== CITAS PRÓXIMAS (confirmadas) ==========
        $upcomingAppointmentsCount = (clone $appointmentsQuery)
            ->whereDate('date', '>=', $now->toDateString())
            ->where('status', 'confirmed')
            ->count();

        // ========== VALOR DE LAS CONSULTAS DEL MES (precio de lista, solo pagadas) ==========
        $monthlyRevenue = (clone $appointmentsQuery)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->whereIn('status', $countable)
            ->where('payment_status', 'paid')
            ->sum('price');

        // ========== CANCELACIONES Y RESERVAS SIN PAGAR ==========
        $validCount = (clone $appointmentsQuery)->whereIn('status', $countable)->count();
        $cancelledCount = (clone $appointmentsQuery)->where('status', 'cancelled')->count();
        $expiredCount = (clone $appointmentsQuery)->where('status', 'expired')->count();
        $bookingsTotal = $validCount + $cancelledCount + $expiredCount;

        $cancellationRate = ($validCount + $cancelledCount) > 0
            ? round(($cancelledCount / ($validCount + $cancelledCount)) * 100, 1)
            : 0;
        $abandonRate = $bookingsTotal > 0 ? round(($expiredCount / $bookingsTotal) * 100, 1) : 0;

        // ========== PRESENCIALES POR COBRAR EN SEDE ==========
        $onsiteQuery = (clone $appointmentsQuery)
            ->where('status', 'confirmed')
            ->where('payment_status', 'pending')
            ->whereIn('service_id', $physicalIds);
        $onsiteCount = (clone $onsiteQuery)->count();
        $onsiteValue = (float) (clone $onsiteQuery)->sum('price');

        // ========== DINERO REAL: pagos aprobados y liquidaciones ==========
        $payouts = function () use ($isAdminUser, $owner) {
            $q = DB::table('doctor_payouts');
            if (!$isAdminUser) {
                $q->where('payable_type', $owner->getMorphClass())->where('payable_id', $owner->id);
            }

            return $q;
        };

        $pendingPayoutsQuery = $payouts()->where('status', 'pending');
        $pendingPayouts = (float) (clone $pendingPayoutsQuery)->sum('amount_to_pay');
        $pendingPayoutsCount = (clone $pendingPayoutsQuery)->count();
        $accruedMonth = (float) $payouts()->whereBetween('created_at', [$monthStart, $monthEnd])->sum('amount_to_pay');

        if ($isAdminUser) {
            $cashMonth = (float) DB::table('payments')
                ->where('status', 'approved')
                ->whereBetween('approved_at', [$monthStart, $monthEnd])
                ->sum('total_in_cents') / 100;

            $payoutsMonth = $payouts()->whereBetween('created_at', [$monthStart, $monthEnd]);
            $platformResult = (float) (clone $payoutsMonth)->sum('platform_commission')
                - (float) (clone $payoutsMonth)->sum('wompi_fee');

            $kpiMoney = [
                ['label' => 'Cobrado a pacientes (mes)', 'raw' => $cashMonth, 'value' => $moneyShort($cashMonth), 'tone' => 'neutral',
                    'sub' => 'Pagos aprobados este mes',
                    'hint' => 'Dinero que realmente pagaron los pacientes este mes (pagos aprobados, con recargo de pasarela). Exacto: ' . $moneyFull($cashMonth)],
                ['label' => 'Por pagar a médicos y clínicas', 'raw' => $pendingPayouts, 'value' => $moneyShort($pendingPayouts), 'tone' => 'neutral',
                    'sub' => $pendingPayoutsCount . ' liquidaciones pendientes',
                    'hint' => 'Suma de las liquidaciones pendientes de pago. Exacto: ' . $moneyFull($pendingPayouts)],
                ['label' => 'Resultado de la plataforma (mes)', 'raw' => $platformResult, 'value' => $moneyShort($platformResult), 'tone' => $tone($platformResult),
                    'sub' => 'Comisión menos costo de pasarela',
                    'hint' => 'Comisión de las liquidaciones del mes menos lo que cobra la pasarela. Es negativo cuando las promociones superan lo cobrado. Exacto: ' . $moneyFull($platformResult)],
                ['label' => 'Valor de consultas (precio de lista)', 'raw' => (float) $monthlyRevenue, 'value' => $moneyShort((float) $monthlyRevenue), 'tone' => 'neutral',
                    'sub' => 'Consultas pagadas del mes, sin descontar promociones',
                    'hint' => 'Suma del precio de lista de las consultas pagadas este mes. No es dinero cobrado. Exacto: ' . $moneyFull((float) $monthlyRevenue)],
            ];
            $chartMoney2Label = 'Cobrado a pacientes';
            $chartMoney2Map = DB::table('payments')
                ->where('status', 'approved')
                ->where('approved_at', '>=', $now->copy()->startOfMonth()->subMonths(4))
                ->selectRaw("DATE_FORMAT(approved_at, '%Y-%m') as mes, sum(total_in_cents) / 100 as total")
                ->groupBy('mes')
                ->pluck('total', 'mes');
        } else {
            $paidMonth = (float) $payouts()->where('status', 'paid')->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount_to_pay');

            $kpiMoney = [
                ['label' => 'Pagado a tu cuenta (mes)', 'raw' => $paidMonth, 'value' => $moneyShort($paidMonth), 'tone' => 'good',
                    'sub' => 'Liquidaciones pagadas este mes',
                    'hint' => 'Lo que ya se te transfirió este mes. Exacto: ' . $moneyFull($paidMonth)],
                ['label' => 'Pendiente de pago', 'raw' => $pendingPayouts, 'value' => $moneyShort($pendingPayouts), 'tone' => 'neutral',
                    'sub' => 'Se paga en el próximo corte',
                    'hint' => 'Liquidaciones generadas que aún no se han transferido. Exacto: ' . $moneyFull($pendingPayouts)],
                ['label' => 'Devengado del mes', 'raw' => $accruedMonth, 'value' => $moneyShort($accruedMonth), 'tone' => 'neutral',
                    'sub' => 'Lo generado por tus consultas este mes',
                    'hint' => 'Suma de lo que te corresponde por las consultas del mes (pagado o pendiente). Exacto: ' . $moneyFull($accruedMonth)],
                ['label' => 'Valor de tus consultas (precio de lista)', 'raw' => (float) $monthlyRevenue, 'value' => $moneyShort((float) $monthlyRevenue), 'tone' => 'neutral',
                    'sub' => 'Consultas pagadas del mes',
                    'hint' => 'Suma del precio de lista de tus consultas pagadas este mes. Exacto: ' . $moneyFull((float) $monthlyRevenue)],
            ];
            $chartMoney2Label = 'Devengado';
            $chartMoney2Map = $payouts()
                ->where('created_at', '>=', $now->copy()->startOfMonth()->subMonths(4))
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, sum(amount_to_pay) as total")
                ->groupBy('mes')
                ->pluck('total', 'mes');
        }

        $kpiOps = [
            ['label' => 'Consultas hoy', 'raw' => $appointmentsToday, 'value' => (string) $appointmentsToday, 'tone' => 'neutral', 'link' => false,
                'sub' => $appointmentsTodayVirtual . ' virtuales · ' . $appointmentsTodayPresencial . ' presenciales',
                'hint' => 'Consultas confirmadas o completadas con fecha de hoy.'],
            ['label' => 'Próximos espacios', 'raw' => $upcomingAppointmentsCount, 'value' => (string) $upcomingAppointmentsCount, 'tone' => 'neutral', 'link' => true,
                'sub' => 'Confirmadas desde hoy',
                'hint' => 'Citas confirmadas con fecha de hoy en adelante.'],
            ['label' => 'Cancelaciones', 'raw' => $cancellationRate, 'value' => $cancellationRate . '%', 'tone' => $cancellationRate > 20 ? 'bad' : 'neutral', 'link' => false,
                'sub' => $cancelledCount . ' de ' . ($validCount + $cancelledCount) . ' citas',
                'hint' => 'Citas canceladas sobre las citas confirmadas, completadas y canceladas. No incluye las reservas que expiraron sin pagar.'],
            ['label' => 'Reservas sin pagar', 'raw' => $expiredCount, 'value' => (string) $expiredCount, 'tone' => 'neutral', 'link' => false,
                'sub' => $abandonRate . '% de las reservas',
                'hint' => 'Reservas que expiraron sin pago. Mide cuántas se pierden antes de pagar.'],
            ['label' => 'Por cobrar en sede', 'raw' => $onsiteCount, 'value' => (string) $onsiteCount, 'tone' => 'neutral', 'link' => false,
                'sub' => $moneyShort($onsiteValue) . ' en presenciales confirmadas sin pago',
                'hint' => 'Presenciales confirmadas cuyo pago aún no se ha registrado. Valor de lista: ' . $moneyFull($onsiteValue)],
        ];

        // =====================================================================
        // ETAPA 2 (dashfix2): filtro de periodo y comparación contra el periodo anterior.
        //  Las tarjetas de flujo (dinero, consultas, cancelaciones) siguen el periodo elegido.
        //  Las de saldo (por pagar, próximos, por cobrar en sede, hoy) siempre muestran el estado actual.
        // =====================================================================
        $periodOptions = ['hoy' => 'Hoy', '7d' => 'Últimos 7 días', 'mes' => 'Este mes', 'mes_anterior' => 'Mes anterior'];
        $periodKey = array_key_exists((string) request('periodo'), $periodOptions) ? (string) request('periodo') : 'mes';
        $periodLabel = $periodOptions[$periodKey];

        if ($periodKey === 'hoy') {
            $pStart = $now->copy()->startOfDay();
            $pEnd = $now->copy()->endOfDay();
            $qStart = $pStart->copy()->subDay();
            $qEnd = $pEnd->copy()->subDay();
        } elseif ($periodKey === '7d') {
            $pStart = $now->copy()->subDays(6)->startOfDay();
            $pEnd = $now->copy()->endOfDay();
            $qStart = $pStart->copy()->subDays(7);
            $qEnd = $pStart->copy()->subDay()->endOfDay();
        } elseif ($periodKey === 'mes_anterior') {
            $pStart = $now->copy()->startOfMonth()->subMonthNoOverflow()->startOfMonth();
            $pEnd = $pStart->copy()->endOfMonth();
            $qStart = $pStart->copy()->subMonthNoOverflow()->startOfMonth();
            $qEnd = $qStart->copy()->endOfMonth();
        } else {
            // Este mes (hasta hoy) frente al mes anterior, mismos días transcurridos.
            $pStart = $now->copy()->startOfMonth();
            $pEnd = $now->copy()->endOfDay();
            $qStart = $pStart->copy()->subMonthNoOverflow()->startOfMonth();
            $qEnd = $qStart->copy()->addDays($now->day - 1)->endOfDay();
            if ($qEnd->gt($qStart->copy()->endOfMonth())) {
                $qEnd = $qStart->copy()->endOfMonth();
            }
        }

        $metrics = function ($from, $to) use ($appointmentsQuery, $countable, $isAdminUser, $payouts) {
            $base = fn () => (clone $appointmentsQuery)
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString());

            $m = [
                'valid' => (clone $base())->whereIn('status', $countable)->count(),
                'cancelled' => (clone $base())->where('status', 'cancelled')->count(),
                'expired' => (clone $base())->where('status', 'expired')->count(),
                'list' => (float) (clone $base())->whereIn('status', $countable)->where('payment_status', 'paid')->sum('price'),
            ];
            $m['cancelRate'] = ($m['valid'] + $m['cancelled']) > 0 ? round($m['cancelled'] / ($m['valid'] + $m['cancelled']) * 100, 1) : 0.0;

            if ($isAdminUser) {
                $m['cash'] = (float) DB::table('payments')->where('kind', '!=', 'subscription')->where('status', 'approved')->whereBetween('approved_at', [$from, $to])->sum('total_in_cents') / 100;
                $po = $payouts()->whereBetween('created_at', [$from, $to]);
                $m['platform'] = (float) (clone $po)->sum('platform_commission') - (float) (clone $po)->sum('wompi_fee');
            } else {
                $m['paid'] = (float) $payouts()->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount_to_pay');
                $m['accrued'] = (float) $payouts()->whereBetween('created_at', [$from, $to])->sum('amount_to_pay');
            }

            return $m;
        };
        $cur = $metrics($pStart, $pEnd);
        $prev = $metrics($qStart, $qEnd);

        $deltaOf = function (float $c, float $p, bool $lowerIsGood = false, bool $points = false): array {
            if ($points) {
                $raw = round($c - $p, 1);
                $txt = number_format(abs($raw), 1, ',', '.') . ' pts';
            } else {
                if (abs($p) < 0.005) {
                    return [];
                }
                $raw = round((($c - $p) / abs($p)) * 100, 1);
                $txt = number_format(abs($raw), 1, ',', '.') . '%';
            }
            if ($raw == 0) {
                return ['delta' => '= igual', 'deltaRaw' => 0.0, 'deltaTone' => 'neutral'];
            }
            $up = $raw > 0;

            return ['delta' => ($up ? '▲ ' : '▼ ') . $txt, 'deltaRaw' => $raw, 'deltaTone' => ($lowerIsGood ? !$up : $up) ? 'good' : 'bad'];
        };

        $range = $pStart->format('d/m') . ' – ' . $pEnd->format('d/m');
        $stock = 'Saldo actual';

        if ($isAdminUser) {
            $kpiMoney = [
                array_merge(['label' => 'Cobrado a pacientes', 'raw' => $cur['cash'], 'value' => $moneyShort($cur['cash']), 'tone' => 'neutral',
                    'sub' => $periodLabel . ' (' . $range . ')',
                    'hint' => 'Pagos aprobados en el periodo, con recargo de pasarela. Exacto: ' . $moneyFull($cur['cash'])], $deltaOf($cur['cash'], $prev['cash'])),
                ['label' => 'Por pagar a médicos y clínicas', 'raw' => $pendingPayouts, 'value' => $moneyShort($pendingPayouts), 'tone' => 'neutral',
                    'sub' => $stock . ' · ' . $pendingPayoutsCount . ' liquidaciones',
                    'hint' => 'Liquidaciones pendientes de pago, sin importar el periodo. Exacto: ' . $moneyFull($pendingPayouts)],
                array_merge(['label' => 'Resultado de la plataforma', 'raw' => $cur['platform'], 'value' => $moneyShort($cur['platform']), 'tone' => $tone($cur['platform']),
                    'sub' => $periodLabel . ' · comisión menos pasarela',
                    'hint' => 'Comisión de las liquidaciones del periodo menos el costo de la pasarela. Exacto: ' . $moneyFull($cur['platform'])], $deltaOf($cur['platform'], $prev['platform'])),
                array_merge(['label' => 'Valor de consultas (precio de lista)', 'raw' => $cur['list'], 'value' => $moneyShort($cur['list']), 'tone' => 'neutral',
                    'sub' => $periodLabel . ' · sin descontar promociones',
                    'hint' => 'Precio de lista de las consultas pagadas del periodo. No es dinero cobrado. Exacto: ' . $moneyFull($cur['list'])], $deltaOf($cur['list'], $prev['list'])),
            ];
        } else {
            $kpiMoney = [
                array_merge(['label' => 'Pagado a tu cuenta', 'raw' => $cur['paid'], 'value' => $moneyShort($cur['paid']), 'tone' => 'good',
                    'sub' => $periodLabel . ' (' . $range . ')',
                    'hint' => 'Liquidaciones que ya se te transfirieron en el periodo. Exacto: ' . $moneyFull($cur['paid'])], $deltaOf($cur['paid'], $prev['paid'])),
                ['label' => 'Pendiente de pago', 'raw' => $pendingPayouts, 'value' => $moneyShort($pendingPayouts), 'tone' => 'neutral',
                    'sub' => $stock . ' · se paga en el próximo corte',
                    'hint' => 'Liquidaciones generadas que aún no se han transferido. Exacto: ' . $moneyFull($pendingPayouts)],
                array_merge(['label' => 'Devengado', 'raw' => $cur['accrued'], 'value' => $moneyShort($cur['accrued']), 'tone' => 'neutral',
                    'sub' => $periodLabel . ' · lo generado por tus consultas',
                    'hint' => 'Lo que te corresponde por las consultas del periodo (pagado o pendiente). Exacto: ' . $moneyFull($cur['accrued'])], $deltaOf($cur['accrued'], $prev['accrued'])),
                array_merge(['label' => 'Valor de tus consultas (precio de lista)', 'raw' => $cur['list'], 'value' => $moneyShort($cur['list']), 'tone' => 'neutral',
                    'sub' => $periodLabel . ' · consultas pagadas',
                    'hint' => 'Precio de lista de tus consultas pagadas en el periodo. Exacto: ' . $moneyFull($cur['list'])], $deltaOf($cur['list'], $prev['list'])),
            ];
        }

        $abandonPeriod = ($cur['valid'] + $cur['cancelled'] + $cur['expired']) > 0
            ? round($cur['expired'] / ($cur['valid'] + $cur['cancelled'] + $cur['expired']) * 100, 1) : 0;

        $kpiOps = [
            ['label' => 'Consultas hoy', 'raw' => $appointmentsToday, 'value' => (string) $appointmentsToday, 'tone' => 'neutral', 'link' => false,
                'sub' => $appointmentsTodayVirtual . ' virtuales · ' . $appointmentsTodayPresencial . ' presenciales',
                'hint' => 'Consultas confirmadas o completadas con fecha de hoy, sin importar el periodo.'],
            array_merge(['label' => 'Consultas del periodo', 'raw' => $cur['valid'], 'value' => (string) $cur['valid'], 'tone' => 'neutral', 'link' => false,
                'sub' => $periodLabel,
                'hint' => 'Consultas confirmadas o completadas con fecha dentro del periodo.'], $deltaOf((float) $cur['valid'], (float) $prev['valid'])),
            ['label' => 'Próximos espacios', 'raw' => $upcomingAppointmentsCount, 'value' => (string) $upcomingAppointmentsCount, 'tone' => 'neutral', 'link' => true,
                'sub' => $stock . ' · confirmadas desde hoy',
                'hint' => 'Citas confirmadas con fecha de hoy en adelante.'],
            array_merge(['label' => 'Cancelaciones', 'raw' => $cur['cancelRate'], 'value' => $cur['cancelRate'] . '%', 'tone' => $cur['cancelRate'] > 20 ? 'bad' : 'neutral', 'link' => false,
                'sub' => $cur['cancelled'] . ' de ' . ($cur['valid'] + $cur['cancelled']) . ' citas',
                'hint' => 'Citas canceladas sobre confirmadas, completadas y canceladas del periodo. La variación es en puntos porcentuales.'], $deltaOf((float) $cur['cancelRate'], (float) $prev['cancelRate'], true, true)),
            array_merge(['label' => 'Reservas sin pagar', 'raw' => $cur['expired'], 'value' => (string) $cur['expired'], 'tone' => 'neutral', 'link' => false,
                'sub' => $abandonPeriod . '% de las reservas',
                'hint' => 'Reservas del periodo que expiraron sin pago.'], $deltaOf((float) $cur['expired'], (float) $prev['expired'], true)),
            ['label' => 'Por cobrar en sede', 'raw' => $onsiteCount, 'value' => (string) $onsiteCount, 'tone' => 'neutral', 'link' => false,
                'sub' => $stock . ' · ' . $moneyShort($onsiteValue),
                'hint' => 'Presenciales confirmadas cuyo pago aún no se registra. Valor de lista: ' . $moneyFull($onsiteValue)],
        ];

        // =====================================================================
        // ETAPA 3b (dashcredits): saldo de los proveedores de IA (solo admin).
        //  Lo escribe el comando ai:check-credits (cada 30 min); aquí solo se lee y se interpreta.
        // =====================================================================
        $aiCredits = [];
        if ($isAdminUser && \Illuminate\Support\Facades\Schema::hasTable('ai_provider_credits')) {
            $creditMeta = [
                'anthropic' => ['Claude (Anthropic)', 'Análisis de piel y chat de salud con IA'],
                'openai' => ['OpenAI', 'Visión de exámenes médicos'],
                'deepseek' => ['DeepSeek', 'Estructurar consultas (notas SOAP)'],
                'twilio' => ['Twilio', 'SMS y WhatsApp (confirmaciones y recordatorios)'],
                'deepgram' => ['Deepgram', 'Transcripción de las consultas'],
            ];
            $creditRows = DB::table('ai_provider_credits')->get()->keyBy('provider');

            foreach ($creditMeta as $pKey => [$pLabel, $pUsage]) {
                $r = $creditRows->get($pKey);
                if (!$r) {
                    continue;
                }
                $bal = $r->last_balance !== null ? (float) $r->last_balance : null;
                $alert = (float) $r->alert_amount;
                $checked = $r->last_checked_at ? \Illuminate\Support\Carbon::parse($r->last_checked_at) : null;
                $billingErr = $r->billing_error_at && \Illuminate\Support\Carbon::parse($r->billing_error_at)->gt($now->copy()->subDay());

                if ($billingErr) {
                    $status = 'out';
                } elseif ($bal === null) {
                    $status = 'unknown';
                } elseif (($r->last_available !== null && !$r->last_available) || $bal <= 0) {
                    $status = 'out';
                } elseif ($bal <= $alert) {
                    $status = 'low';
                } else {
                    $status = 'ok';
                }

                $daysLeft = null;
                if ($r->last_source === 'estimate' && $r->baseline_at && (float) $r->spent_since > 0 && $bal !== null && $bal > 0) {
                    $elapsed = max(1.0, \Illuminate\Support\Carbon::parse($r->baseline_at)->diffInHours($now) / 24);
                    $perDay = (float) $r->spent_since / $elapsed;
                    $daysLeft = $perDay > 0 ? (int) floor($bal / $perDay) : null;
                }

                $sym = ['USD' => '$', 'CNY' => '¥'][(string) $r->last_currency] ?? ((string) $r->last_currency !== '' ? $r->last_currency . ' ' : '$');
                $detail = match ($r->last_source) {
                    'api' => 'Saldo real de la API' . ($checked ? ' · ' . $checked->diffForHumans() : ''),
                    'estimate' => 'Estimado: saldo registrado menos gasto' . ($checked ? ' · ' . $checked->diffForHumans() : ''),
                    'manual' => 'Registrado a mano' . ($r->baseline_at ? ' el ' . \Illuminate\Support\Carbon::parse($r->baseline_at)->format('d/m H:i') : ''),
                    default => 'Aún sin chequeo',
                };

                $aiCredits[] = [
                    'provider' => $pKey,
                    'label' => $pLabel,
                    'usage' => $pUsage,
                    'status' => $status,
                    'value' => $bal === null ? '—' : $sym . number_format($bal, 2, '.', ','),
                    'raw' => $bal,
                    'alert' => $alert,
                    'detail' => $detail,
                    'days_left' => $daysLeft,
                    'error' => $r->last_error,
                    'stale' => $checked ? $checked->lt($now->copy()->subHours((int) config('ai_credits.stale_hours', 3))) : false,
                ];
            }
        }

        // =====================================================================
        // ETAPA 3c (dashbills): próximos cobros recurrentes (solo admin).
        //  Se registran con: php artisan bills:add ...  (ver bills:list y bills:paid)
        // =====================================================================
        $upcomingBills = null;
        if ($isAdminUser && \Illuminate\Support\Facades\Schema::hasTable('recurring_bills')) {
            $upcomingBills = [];
            $billToday = $now->copy()->startOfDay();
            $billAdvance = fn ($d, $c) => match ($c) {
                'yearly' => $d->copy()->addYear(),
                'quarterly' => $d->copy()->addMonths(3),
                default => $d->copy()->addMonth(),
            };
            $cadenceLabels = ['monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', 'once' => 'Único'];

            foreach (DB::table('recurring_bills')->where('active', 1)->get() as $b) {
                $due = \Illuminate\Support\Carbon::parse($b->due_at)->startOfDay();
                if ($b->auto_renew && $b->cadence !== 'once') {
                    $guard = 0;
                    while ($due->lt($billToday) && $guard++ < 60) {
                        $due = $billAdvance($due, $b->cadence);
                    }
                }
                $days = (int) $billToday->diffInDays($due, false);
                $status = $days < 0 ? 'overdue' : ($days <= (int) $b->remind_days ? 'soon' : 'ok');
                $amount = $b->amount === null
                    ? 'Monto por definir'
                    : ($b->currency === 'COP' ? '$' . number_format((float) $b->amount, 0, ',', '.') . ' COP' : '$' . number_format((float) $b->amount, 2, '.', ',') . ' ' . $b->currency);

                $upcomingBills[] = [
                    'id' => $b->id,
                    'name' => $b->name,
                    'amount' => $amount,
                    'cadence' => $cadenceLabels[$b->cadence] ?? $b->cadence,
                    'due' => $due->format('d/m/Y'),
                    'days' => $days,
                    'status' => $status,
                    'auto' => (bool) $b->auto_renew,
                    'note' => $b->note,
                ];
            }
            usort($upcomingBills, fn ($a, $b) => $a['days'] <=> $b['days']);
            $upcomingBills = array_slice($upcomingBills, 0, 10);
        }

        // =====================================================================
        // ETAPA 3d (dashpromos): códigos promocionales usados (solo admin).
        //  Fuente: citas (confirmed/completed), exámenes (pagados) y análisis de piel (pagados),
        //  por fecha de creación (cuándo se usó el código) dentro del periodo elegido.
        //  Solo se cuentan códigos que existen en promo_codes; los que ya no existen van aparte.
        // =====================================================================
        $promoStats = null;
        if ($isAdminUser) {
            try {
                $promoModules = ['appointment' => 'Citas médicas', 'exam' => 'Exámenes', 'skin_analysis' => 'Análisis de piel'];
                $promoCodes = DB::table('promo_codes')->get()->keyBy(fn ($c) => strtoupper(trim((string) $c->code)));

                $promoRows = function ($from, $to) {
                    $rows = [];
                    $push = function (string $module, $q) use (&$rows) {
                        foreach ($q->selectRaw('promo_code as code, count(*) as n, coalesce(sum(discount_amount), 0) as v')->groupBy('promo_code')->get() as $r) {
                            $rows[] = ['module' => $module, 'code' => strtoupper(trim((string) $r->code)), 'n' => (int) $r->n, 'v' => (float) $r->v];
                        }
                    };
                    $push('appointment', DB::table('appointments')
                        ->whereIn('status', ['confirmed', 'completed'])
                        ->whereNotNull('promo_code')->where('promo_code', '!=', '')
                        ->whereBetween('created_at', [$from, $to]));
                    $push('exam', DB::table('medical_analyses')
                        ->where('payment_status', 'paid')
                        ->whereNotNull('promo_code')->where('promo_code', '!=', '')
                        ->whereBetween('created_at', [$from, $to]));
                    $push('skin_analysis', DB::table('skin_analyses')
                        ->whereNotNull('paid_at')
                        ->whereNotNull('promo_code')->where('promo_code', '!=', '')
                        ->whereBetween('created_at', [$from, $to]));

                    return $rows;
                };
                $known = fn (array $rows) => array_values(array_filter($rows, fn ($r) => $promoCodes->has($r['code'])));

                $cur = $promoRows($pStart, $pEnd);
                $prev = $promoRows($qStart, $qEnd);
                $curKnown = $known($cur);
                $prevKnown = $known($prev);

                $totN = (int) array_sum(array_column($curKnown, 'n'));
                $totV = (float) array_sum(array_column($curKnown, 'v'));
                $prevV = (float) array_sum(array_column($prevKnown, 'v'));

                $mods = [];
                foreach ($promoModules as $mk => $mn) {
                    $rows = array_filter($curKnown, fn ($r) => $r['module'] === $mk);
                    $n = (int) array_sum(array_column($rows, 'n'));
                    $v = (float) array_sum(array_column($rows, 'v'));
                    $mods[] = [
                        'key' => $mk, 'name' => $mn, 'n' => $n, 'raw' => $v, 'value' => $moneyShort($v),
                        'share' => $totV > 0 ? round($v / $totV * 100, 1) : 0.0,
                        'hint' => $mn . ': valor de descuento otorgado. Exacto: ' . $moneyFull($v),
                    ];
                }

                $byCode = [];
                $warnings = [];
                foreach ($curKnown as $r) {
                    $byCode[$r['code']]['n'] = ($byCode[$r['code']]['n'] ?? 0) + $r['n'];
                    $byCode[$r['code']]['v'] = ($byCode[$r['code']]['v'] ?? 0.0) + $r['v'];
                    $byCode[$r['code']]['mods'][$r['module']] = true;
                    $applies = json_decode((string) $promoCodes[$r['code']]->applies_to, true);
                    if (is_array($applies) && $applies && !in_array($r['module'], $applies, true)) {
                        $warnings[$r['code'] . '|' . $r['module']] = $r['code'] . ' se usó ' . $r['n'] . ' vez/veces en «' . $promoModules[$r['module']] . '», pero está configurado solo para: ' . implode(', ', array_map(fn ($a) => $promoModules[$a] ?? $a, $applies)) . '.';
                    }
                }
                uasort($byCode, fn ($a, $b) => [$b['n'], $b['v']] <=> [$a['n'], $a['v']]);
                $top = [];
                foreach (array_slice($byCode, 0, 6, true) as $code => $x) {
                    $pc = $promoCodes[$code];
                    $max = $pc->max_uses !== null ? (int) $pc->max_uses : null;
                    $top[] = [
                        'code' => $code,
                        'n' => $x['n'],
                        'value' => $moneyShort($x['v']),
                        'kind' => $pc->type === 'percent' ? rtrim(rtrim(number_format((float) $pc->reward, 2, '.', ''), '0'), '.') . '%' : $moneyShort((float) $pc->reward),
                        'modules' => implode(', ', array_map(fn ($m) => $promoModules[$m], array_keys($x['mods']))),
                        'stock' => $max === null ? ((int) $pc->uses . ' usos') : ((int) $pc->uses . ' / ' . $max . ((int) $pc->uses >= $max ? ' · agotado' : '')),
                        'exhausted' => $max !== null && (int) $pc->uses >= $max,
                    ];
                }

                $orphanRows = array_values(array_filter($cur, fn ($r) => !$promoCodes->has($r['code'])));
                $orphan = $orphanRows ? ['n' => (int) array_sum(array_column($orphanRows, 'n')), 'codes' => implode(', ', array_unique(array_column($orphanRows, 'code')))] : null;

                $promoStats = [
                    'uses' => $totN,
                    'raw' => $totV,
                    'value' => $moneyShort($totV),
                    'hint' => 'Valor de descuentos otorgados con códigos. Exacto: ' . $moneyFull($totV),
                    'modules' => $mods,
                    'top' => $top,
                    'orphan' => $orphan,
                    'warnings' => array_values($warnings),
                ] + array_replace($deltaOf($totV, $prevV), ['deltaTone' => 'neutral']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('dashpromos: ' . $e->getMessage());
                $promoStats = null;
            }
        }

        // =====================================================================
        // ETAPA 3e (dashconv): conversión de reserva a pago (solo admin).
        //  Fuente: appointments creadas en el periodo (sin borradas). Cada reserva cae en UNA categoría:
        //   gratis (precio 0 o código que cubre el 100%)  → no cuenta en la conversión
        //   pagada (payment_status = paid) | expirada (sin pago) | cancelada sin pago | pendiente (en curso)
        //  Conversión = pagadas / (pagadas + expiradas + canceladas sin pago). Las pendientes aún no se resuelven.
        //  Intentos de pago: tabla payments (kind = appointment) del mismo periodo.
        // =====================================================================
        $funnel = null;
        if ($isAdminUser) {
            try {
                $funnelCats = ['paid' => 'Pagadas', 'expired' => 'Expiradas sin pago', 'cancelled' => 'Canceladas sin pago', 'pending' => 'Pendientes de pago'];
                $funnelOf = function ($from, $to) {
                    $rows = DB::table('appointments as a')
                        ->leftJoin('services as s', 's.id', '=', 'a.service_id')
                        ->whereNull('a.deleted_at')
                        ->whereBetween('a.created_at', [$from, $to])
                        ->selectRaw("a.status as st, a.payment_status as ps, coalesce(s.type, 'virtual') as stype, (case when a.price <= 0 or (a.promo_code is not null and a.promo_code <> '' and a.discount_amount >= a.price) then 1 else 0 end) as free, count(*) as n, coalesce(sum(a.price), 0) as v")
                        ->groupBy('a.status', 'a.payment_status', 'stype', 'free')
                        ->get();
                    $o = ['total' => 0, 'free' => 0, 'cat' => ['paid' => 0, 'expired' => 0, 'cancelled' => 0, 'pending' => 0],
                          'val' => ['paid' => 0.0, 'expired' => 0.0, 'cancelled' => 0.0, 'pending' => 0.0], 'type' => []];
                    foreach ($rows as $r) {
                        $n = (int) $r->n;
                        $o['total'] += $n;
                        if ((int) $r->free === 1) {
                            $o['free'] += $n;
                            continue;
                        }
                        $c = $r->ps === 'paid' ? 'paid' : ($r->st === 'expired' ? 'expired' : ($r->st === 'cancelled' ? 'cancelled' : 'pending'));
                        $o['cat'][$c] += $n;
                        $o['val'][$c] += (float) $r->v;
                        $o['type'][$r->stype][$c] = ($o['type'][$r->stype][$c] ?? 0) + $n;
                    }

                    return $o;
                };
                $convOf = function (array $cat) {
                    $res = $cat['paid'] + $cat['expired'] + $cat['cancelled'];

                    return $res > 0 ? round($cat['paid'] / $res * 100, 1) : null;
                };

                $fc = $funnelOf($pStart, $pEnd);
                $fp = $funnelOf($qStart, $qEnd);
                $conv = $convOf($fc['cat']);
                $convPrev = $convOf($fp['cat']);
                $deltaTxt = null;
                $deltaTone = 'neutral';
                if ($conv !== null && $convPrev !== null) {
                    $dd = round($conv - $convPrev, 1);
                    $deltaTxt = ($dd > 0 ? '▲ ' : ($dd < 0 ? '▼ ' : '')) . rtrim(rtrim(number_format(abs($dd), 1, '.', ''), '0'), '.') . ' pts';
                    $deltaTone = $dd > 0 ? 'good' : ($dd < 0 ? 'bad' : 'neutral');
                    if ($dd == 0) {
                        $deltaTxt = 'igual';
                    }
                }

                $needPay = array_sum($fc['cat']);
                $steps = [];
                foreach ($funnelCats as $k => $name) {
                    $steps[] = [
                        'key' => $k, 'name' => $name, 'n' => $fc['cat'][$k],
                        'value' => $moneyShort($fc['val'][$k]),
                        'share' => $needPay > 0 ? round($fc['cat'][$k] / $needPay * 100, 1) : 0.0,
                        'hint' => $name . ': valor de lista de esas reservas. Exacto: ' . $moneyFull($fc['val'][$k]),
                    ];
                }

                $byType = [];
                foreach (['virtual' => 'Virtuales', 'physical' => 'Presenciales'] as $tk => $tn) {
                    $t = $fc['type'][$tk] ?? null;
                    $tc = $t ? $convOf(array_merge(['paid' => 0, 'expired' => 0, 'cancelled' => 0, 'pending' => 0], $t)) : null;
                    $tTot = $t ? array_sum($t) : 0;
                    if ($tTot > 0) {
                        $byType[] = ['name' => $tn, 'conv' => $tc, 'n' => $tTot];
                    }
                }

                $att = DB::table('payments')->where('kind', 'appointment')->whereBetween('created_at', [$pStart, $pEnd])
                    ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->all();
                $attTotal = (int) array_sum($att);
                $attFail = (int) (($att['declined'] ?? 0) + ($att['error'] ?? 0));

                $lostRaw = $fc['val']['expired'] + $fc['val']['cancelled'];
                $funnel = [
                    'conv' => $conv,
                    'delta' => $deltaTxt,
                    'deltaTone' => $deltaTone,
                    'total' => $fc['total'],
                    'free' => $fc['free'],
                    'needPay' => $needPay,
                    'steps' => $steps,
                    'byType' => $byType,
                    'lost' => $moneyShort($lostRaw),
                    'lostRaw' => $lostRaw,
                    'lostHint' => 'Valor de lista de reservas expiradas o canceladas sin pago. Exacto: ' . $moneyFull($lostRaw),
                    'attempts' => $attTotal,
                    'attApproved' => (int) ($att['approved'] ?? 0),
                    'attFail' => $attFail,
                    'attExpired' => (int) ($att['expired'] ?? 0),
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('dashconv: ' . $e->getMessage());
                $funnel = null;
            }
        }

        // =====================================================================
        // ETAPA 3f (dashchan): canal de reserva y reprogramaciones (solo admin).
        //  Fuente: appointments creadas en el periodo (sin borradas).
        //  Canal: columna channel (web = la reserva el paciente; doctor = la agenda el consultorio).
        //  Conversión por canal = misma regla que la tarjeta de conversión (gratuitas y pendientes no cuentan).
        //  Reprogramaciones: reschedule_count de las reservas no expiradas del periodo.
        // =====================================================================
        $chanStats = null;
        if ($isAdminUser) {
            try {
                $chanNames = ['web' => 'Web (reserva el paciente)', 'doctor' => 'Agendada por el consultorio', '' => 'Sin canal'];
                $chanRows = function ($from, $to) {
                    return DB::table('appointments as a')
                        ->whereNull('a.deleted_at')
                        ->whereBetween('a.created_at', [$from, $to])
                        ->selectRaw("coalesce(a.channel, '') as ch, a.status as st, a.payment_status as ps, (case when a.price <= 0 or (a.promo_code is not null and a.promo_code <> '' and a.discount_amount >= a.price) then 1 else 0 end) as free, count(*) as n")
                        ->groupBy('ch', 'a.status', 'a.payment_status', 'free')
                        ->get();
                };
                $byChannel = [];
                foreach ($chanRows($pStart, $pEnd) as $r) {
                    $c = &$byChannel[$r->ch];
                    $c = $c ?? ['n' => 0, 'free' => 0, 'paid' => 0, 'expired' => 0, 'cancelled' => 0, 'pending' => 0];
                    $c['n'] += (int) $r->n;
                    if ((int) $r->free === 1) {
                        $c['free'] += (int) $r->n;
                    } else {
                        $k = $r->ps === 'paid' ? 'paid' : ($r->st === 'expired' ? 'expired' : ($r->st === 'cancelled' ? 'cancelled' : 'pending'));
                        $c[$k] += (int) $r->n;
                    }
                    unset($c);
                }
                $chanList = [];
                foreach ($byChannel as $ch => $c) {
                    $res = $c['paid'] + $c['expired'] + $c['cancelled'];
                    $chanList[] = [
                        'name' => $chanNames[$ch] ?? ucfirst($ch),
                        'n' => $c['n'],
                        'free' => $c['free'],
                        'expired' => $c['expired'],
                        'conv' => $res > 0 ? round($c['paid'] / $res * 100, 1) : null,
                    ];
                }
                usort($chanList, fn ($a, $b) => $b['n'] <=> $a['n']);

                $maxResch = null;
                try {
                    $maxResch = (new \ReflectionClassConstant(\App\Services\Appointments\AppointmentRescheduler::class, 'MAX_PATIENT_RESCHEDULES'))->getValue();
                } catch (\Throwable $e) {
                    $maxResch = null;
                }
                $reschOf = function ($from, $to) use ($maxResch) {
                    $base = fn () => DB::table('appointments')->whereNull('deleted_at')->where('status', '!=', 'expired')->whereBetween('created_at', [$from, $to]);
                    $row = $base()->selectRaw('count(*) as n, coalesce(sum(case when reschedule_count > 0 then 1 else 0 end), 0) as r, coalesce(sum(reschedule_count), 0) as total')->first();
                    $maxed = $maxResch ? (int) $base()->where('reschedule_count', '>=', $maxResch)->count() : 0;

                    return ['n' => (int) $row->n, 'r' => (int) $row->r, 'total' => (int) $row->total, 'maxed' => $maxed];
                };
                $rc = $reschOf($pStart, $pEnd);
                $rp = $reschOf($qStart, $qEnd);

                $chanStats = [
                    'rows' => $chanList,
                    'total' => (int) array_sum(array_column($chanList, 'n')),
                    'resch' => [
                        'base' => $rc['n'],
                        'reservas' => $rc['r'],
                        'total' => $rc['total'],
                        'rate' => $rc['n'] > 0 ? round($rc['r'] / $rc['n'] * 100, 1) : null,
                        'maxed' => $rc['maxed'],
                        'max' => $maxResch,
                    ] + array_replace($deltaOf((float) $rc['r'], (float) $rp['r']), ['deltaTone' => 'neutral']),
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('dashchan: ' . $e->getMessage());
                $chanStats = null;
            }
        }

        // =====================================================================
        // ETAPA 3g (dashticket): ticket promedio por módulo (solo admin).
        //  Fuente única: pagos aprobados (payments) del periodo, agrupados por tipo.
        //  Ticket cobrado = total pagado / número de pagos. «Sin código» excluye pagos con código promocional.
        //  Valor de lista = total pagado + descuento (lo que habría costado sin código).
        // =====================================================================
        $ticketStats = null;
        if ($isAdminUser) {
            try {
                $tkNames = [
                    'appointment' => 'Citas médicas',
                    'exam' => 'Análisis de exámenes',
                    'skin_analysis' => 'Análisis de piel',
                    'ai_chat' => 'Chat de Salud con IA',
                    'subscription' => 'Suscripciones de plan',
                ];
                $tkOf = function ($from, $to) use ($tkNames) {
                    $rows = DB::table('payments')
                        ->where('status', 'approved')
                        ->whereBetween('approved_at', [$from, $to])
                        ->selectRaw('kind, count(*) as n, sum(total_in_cents) / 100 as t, sum(total_in_cents + discount_in_cents) / 100 as lista, sum(case when promo_code_id is null then 1 else 0 end) as n_np, sum(case when promo_code_id is null then total_in_cents else 0 end) / 100 as t_np')
                        ->groupBy('kind')
                        ->get();
                    $o = [];
                    foreach ($rows as $r) {
                        $k = array_key_exists($r->kind, $tkNames) ? $r->kind : 'other';
                        foreach (['n' => (int) $r->n, 't' => (float) $r->t, 'lista' => (float) $r->lista, 'n_np' => (int) $r->n_np, 't_np' => (float) $r->t_np] as $f => $v) {
                            $o[$k][$f] = ($o[$k][$f] ?? 0) + $v;
                        }
                    }

                    return $o;
                };
                $tkCur = $tkOf($pStart, $pEnd);
                $tkPrev = $tkOf($qStart, $qEnd);
                $tkNames['other'] = 'Otros';

                $tkRows = [];
                $allN = 0;
                $allT = 0.0;
                foreach ($tkNames as $k => $name) {
                    $c = $tkCur[$k] ?? ['n' => 0, 't' => 0.0, 'lista' => 0.0, 'n_np' => 0, 't_np' => 0.0];
                    $p = $tkPrev[$k] ?? ['n' => 0, 't' => 0.0];
                    if ($k === 'other' && $c['n'] == 0 && ($p['n'] ?? 0) == 0) {
                        continue;
                    }
                    $avg = $c['n'] > 0 ? $c['t'] / $c['n'] : null;
                    $avgPrev = ($p['n'] ?? 0) > 0 ? $p['t'] / $p['n'] : null;
                    $avgNp = $c['n_np'] > 0 ? $c['t_np'] / $c['n_np'] : null;
                    $avgList = $c['n'] > 0 ? $c['lista'] / $c['n'] : null;
                    $allN += $c['n'];
                    $allT += $c['t'];
                    $tkRows[] = [
                        'key' => $k, 'name' => $name, 'n' => (int) $c['n'], 'nNoPromo' => (int) $c['n_np'],
                        'avg' => $avg, 'avgText' => $avg === null ? '—' : $moneyShort($avg),
                        'avgNoPromo' => $avgNp, 'avgNoPromoText' => $avgNp === null ? '—' : $moneyShort($avgNp),
                        'avgList' => $avgList, 'avgListText' => $avgList === null ? '—' : $moneyShort($avgList),
                        'hint' => $name . ': ' . $c['n'] . ' pago(s) aprobado(s). Ticket cobrado exacto: ' . ($avg === null ? 'sin pagos' : $moneyFull($avg)),
                    ] + ($avg !== null && $avgPrev !== null ? array_replace($deltaOf($avg, $avgPrev), ['deltaTone' => 'neutral']) : []);
                }
                $ticketStats = [
                    'rows' => $tkRows,
                    'n' => (int) $allN,
                    'avgAll' => $allN > 0 ? $moneyShort($allT / $allN) : '—',
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('dashticket: ' . $e->getMessage());
                $ticketStats = null;
            }
        }

        // =====================================================================
        // ETAPA 3h (dashsubs): suscripciones de planes de médicos (solo admin).
        //  Dinero: tabla payments (tipo subscription, aprobados, por approved_at). Es la misma fuente de «Ingresos por línea».
        //  Estado del plan (activos, MRR, vencimientos, gracia): tabla subscriptions.
        //  «Nueva» = primer pago de plan aprobado de ese médico; el resto, renovación.
        // =====================================================================
        $subsStats = null;
        if ($isAdminUser) {
            try {
                $sbMorph = (new \App\Models\Subscription())->getMorphClass();
                $sbPays = fn ($from, $to) => DB::table('payments as p')
                    ->where('p.kind', 'subscription')->where('p.status', 'approved')->where('p.payable_type', $sbMorph)
                    ->whereBetween('p.approved_at', [$from, $to]);
                $sbOf = function ($from, $to) use ($sbPays) {
                    $r = $sbPays($from, $to)->selectRaw('count(*) as n, coalesce(sum(p.total_in_cents), 0) / 100 as t')->first();

                    return ['n' => (int) $r->n, 't' => (float) $r->t];
                };
                $sbCur = $sbOf($pStart, $pEnd);
                $sbPrev = $sbOf($qStart, $qEnd);
                $sbNew = (int) $sbPays($pStart, $pEnd)
                    ->join('subscriptions as s', 's.id', '=', 'p.payable_id')
                    ->whereRaw('not exists (select 1 from payments p2 join subscriptions s2 on s2.id = p2.payable_id where p2.kind = ? and p2.status = ? and p2.payable_type = ? and s2.doctor_id = s.doctor_id and p2.approved_at < p.approved_at)', ['subscription', 'approved', $sbMorph])
                    ->count();

                $sbLatest = fn () => DB::table('subscriptions as s')->where('s.status', 'approved')->where('s.ends_at', '>', now())
                    ->whereRaw('not exists (select 1 from subscriptions s2 where s2.doctor_id = s.doctor_id and s2.status = ? and s2.ends_at > s.ends_at)', ['approved']);
                $sbActive = (int) $sbLatest()->count();
                $sbMrr = (float) $sbLatest()->get(['s.amount_in_cents', 's.starts_at', 's.ends_at'])->sum(function ($r) {
                    // Cada plan vigente pesa lo que paga al mes: pago × 30 ÷ días que cubre (un plan anual pesa 1/12).
                    $days = ($r->starts_at && $r->ends_at)
                        ? max(1, abs(\Carbon\Carbon::parse($r->starts_at)->diffInDays(\Carbon\Carbon::parse($r->ends_at))))
                        : 30;

                    return $r->amount_in_cents * 30 / $days;
                }) / 100;
                $sbByPlan = $sbLatest()->join('plans as p', 'p.id', '=', 's.plan_id')
                    ->selectRaw('p.name as plan, count(*) as n')->groupBy('p.name')->orderByDesc('n')->pluck('n', 'plan')->all();

                $sbExpiring = DB::table('subscriptions as s')
                    ->join('plans as p', 'p.id', '=', 's.plan_id')
                    ->leftJoin('users as u', 'u.id', '=', 's.doctor_id')
                    ->where('s.status', 'approved')
                    ->whereBetween('s.ends_at', [now(), now()->addDays(7)])
                    ->whereRaw('not exists (select 1 from subscriptions s2 where s2.doctor_id = s.doctor_id and s2.status = ? and s2.ends_at > s.ends_at)', ['approved'])
                    ->orderBy('s.ends_at')->limit(5)
                    ->get(['u.name as who', 'p.name as plan', 's.ends_at'])
                    ->map(fn ($r) => ['who' => $r->who ?: 'Médico', 'plan' => $r->plan, 'date' => \Illuminate\Support\Carbon::parse($r->ends_at)->timezone('America/Bogota')->format('d/m')])
                    ->all();

                // En gracia: venció hace menos de N días, no ha renovado y todavía está en un plan de pago.
                $sbGraceDays = max(0, (int) config('plans.grace_days', 5));
                $sbGrace = (int) DB::table('subscriptions as s')
                    ->join('doctors as d', 'd.user_id', '=', 's.doctor_id')
                    ->join('doctor_settings as ds', 'ds.doctor_id', '=', 'd.id')
                    ->join('plans as pl', 'pl.id', '=', 'ds.plan_id')
                    ->where('s.status', 'approved')->where('s.ends_at', '<=', now())->where('s.ends_at', '>', now()->subDays($sbGraceDays))
                    ->where('pl.price', '>', 0)
                    ->whereRaw('not exists (select 1 from subscriptions s2 where s2.doctor_id = s.doctor_id and s2.status = ? and s2.ends_at > s.ends_at)', ['approved'])
                    ->count();

                // Bajaron por vencimiento en el periodo (historial del plan; si la tabla no existe, 0).
                $sbLapsed = 0;
                try {
                    $sbLapsed = (int) DB::table('plan_events')->where('type', 'expired')->whereBetween('created_at', [$pStart, $pEnd])->count();
                } catch (\Throwable $e) {
                    $sbLapsed = 0;
                }

                $sbReview = (int) DB::table('payments')->where('kind', 'subscription')->where('status', 'review')->count();

                $sbManual = (int) DB::table('doctor_settings as ds')
                    ->join('plans as p', 'p.id', '=', 'ds.plan_id')
                    ->join('doctors as d', 'd.id', '=', 'ds.doctor_id')
                    ->where('p.price', '>', 0)
                    ->whereRaw('not exists (select 1 from subscriptions x where x.doctor_id = d.user_id and x.status = ?)', ['approved'])
                    ->count();

                $subsStats = [
                    'raw' => $sbCur['t'],
                    'value' => $moneyShort($sbCur['t']),
                    'hint' => 'Pagos de plan aprobados en el periodo (lo que pagaron los médicos). Exacto: ' . $moneyFull($sbCur['t']),
                    'n' => $sbCur['n'],
                    'new' => $sbNew,
                    'renewals' => max(0, $sbCur['n'] - $sbNew),
                    'ticket' => $sbCur['n'] > 0 ? $moneyShort($sbCur['t'] / $sbCur['n']) : '—',
                    'active' => $sbActive,
                    'mrrRaw' => $sbMrr,
                    'mrr' => $moneyShort($sbMrr),
                    'mrrHint' => 'Ingreso mensual recurrente: cada plan vigente cuenta por lo que paga al mes (un plan anual pesa 1/12 de lo pagado). Exacto: ' . $moneyFull($sbMrr),
                    'byPlan' => $sbByPlan,
                    'expiring' => $sbExpiring,
                    'grace' => $sbGrace,
                    'graceDays' => $sbGraceDays,
                    'lapsed' => $sbLapsed,
                    'review' => $sbReview,
                    'manual' => $sbManual,
                ] + array_replace($deltaOf($sbCur['t'], $sbPrev['t']), ['deltaTone' => 'neutral']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('dashsubs: ' . $e->getMessage());
                $subsStats = null;
            }
        }

        // ========== DISTRIBUCIÓN POR MODALIDADES (solo consultas válidas) ==========
        $modalitiesQuery = Appointment::join('services', 'appointments.service_id', '=', 'services.id')
            ->whereIn('appointments.status', $countable);
        if (!$isAdminUser) {
            $modalitiesQuery->where('appointments.' . $ownerColumn, $owner->id);
        }

        $modalities = $modalitiesQuery->select('services.type', DB::raw('count(*) as total'))
            ->groupBy('services.type')
            ->pluck('total', 'type')
            ->toArray();

        // ========== TOP UBICACIONES CLÍNICAS (solo consultas válidas y pagadas) ==========
        $locationQuery = Appointment::join('addresses', 'appointments.address_id', '=', 'addresses.id')
            ->whereIn('appointments.status', $countable)
            ->where('appointments.payment_status', 'paid');
        if (!$isAdminUser) {
            $locationQuery->where('appointments.' . $ownerColumn, $owner->id);
        }

        $topClinicalLocations = $locationQuery->select('addresses.name', 'addresses.id', DB::raw('count(*) as cantidad'), DB::raw('sum(price) as ingresos'))
            ->groupBy('addresses.name', 'addresses.id')
            ->orderBy('ingresos', 'desc')
            ->take(3)
            ->get();

        // ========== HISTORIAL: ÚLTIMOS 5 MESES (con ceros), cantidades y valor ==========
        $monthNames = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
        ];
        $windowStart = $now->copy()->startOfMonth()->subMonths(4);

        $countsBy = function ($ids) use ($appointmentsQuery, $countable, $windowStart) {
            return (clone $appointmentsQuery)
                ->whereIn('status', $countable)
                ->whereIn('service_id', $ids)
                ->where('date', '>=', $windowStart->toDateString())
                ->selectRaw("DATE_FORMAT(date, '%Y-%m') as mes, count(*) as conteo, sum(price) as total")
                ->groupBy('mes')
                ->get()
                ->keyBy('mes');
        };
        $virtualByMonth = $countsBy($virtualIds);
        $physicalByMonth = $countsBy($physicalIds);

        $listValueByMonth = (clone $appointmentsQuery)
            ->whereIn('status', $countable)
            ->where('payment_status', 'paid')
            ->where('date', '>=', $windowStart->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as mes, sum(price) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $chartLabels = [];
        $chartData = [];
        $chartMoney2 = [];
        $chartDataVirtual = [];
        $chartDataPresencial = [];
        $monthlyHistorical = collect();

        for ($i = 0; $i < 5; $i++) {
            $m = $windowStart->copy()->addMonths($i);
            $key = $m->format('Y-m');

            $chartLabels[] = $monthNames[$m->format('m')] . ' ' . $m->format('y');
            $chartData[] = (float) ($listValueByMonth[$key] ?? 0);
            $chartMoney2[] = (float) ($chartMoney2Map[$key] ?? 0);
            $chartDataVirtual[] = (int) ($virtualByMonth[$key]->conteo ?? 0);
            $chartDataPresencial[] = (int) ($physicalByMonth[$key]->conteo ?? 0);

            if (isset($virtualByMonth[$key])) {
                $monthlyHistorical->push((object) ['mes' => $key, 'type' => 'virtual', 'total' => $virtualByMonth[$key]->total, 'conteo' => $virtualByMonth[$key]->conteo]);
            }
            if (isset($physicalByMonth[$key])) {
                $monthlyHistorical->push((object) ['mes' => $key, 'type' => 'physical', 'total' => $physicalByMonth[$key]->total, 'conteo' => $physicalByMonth[$key]->conteo]);
            }
        }

        // ========== DISTRIBUCIÓN FINANCIERA POR SEDES ==========
        $locationLabels = [];
        $locationRevenueData = [];

        foreach ($topClinicalLocations as $location) {
            $locationLabels[] = Str::limit($location->name, 20, '...');
            $locationRevenueData[] = (float) $location->ingresos;
        }
               
        return view('admin.dashboard', compact(
            'user', 'owner', 'appointmentsToday', 'appointmentsTodayVirtual', 'appointmentsTodayPresencial',
            'upcomingAppointmentsCount', 'monthlyRevenue', 'cancellationRate', 'modalities', 
            'topClinicalLocations', 'monthlyHistorical', 'usersByRole',
            'chartLabels', 'chartData', 'chartDataVirtual', 'chartDataPresencial', 'locationLabels',
            'locationRevenueData', 'popularSpecialties', 'popularCities', 'promoCode',
            'kpiMoney', 'kpiOps', 'chartMoney2', 'chartMoney2Label', 'periodKey', 'periodLabel', 'periodOptions', 'subsStats', 'ticketStats', 'chanStats', 'funnel', 'promoStats', 'aiCredits', 'upcomingBills'
        ));
    }
}