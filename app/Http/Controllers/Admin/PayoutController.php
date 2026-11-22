<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\DoctorPayout;
use App\Services\Payments\PayoutFilters;
use App\Services\Payments\PayoutSettlement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    private const WITH = ['appointment.service', 'appointment.address', 'appointment.doctor.user', 'appointment.clinic.user', 'payment', 'paidBy', 'batch'];

    public function index(Request $request)
    {
        $filters = PayoutFilters::normalize($request->query());

        $base    = PayoutFilters::apply(DoctorPayout::query(), $filters);
        $totals  = PayoutFilters::totals($base);
        $payouts = (clone $base)->with(self::WITH)
            ->orderBy('id', $filters['status'] === 'pending' ? 'asc' : 'desc')
            ->paginate(20)->withQueryString();

        $totalPending = DoctorPayout::where('status', DoctorPayout::STATUS_PENDING)->sum('amount_to_pay');

        return view('administrator.payouts.index', compact('payouts', 'totalPending', 'filters', 'totals'));
    }

    public function markPaid(Request $request, DoctorPayout $payout)
    {
        $request->validate(['transfer_reference' => 'required|string|max:255']);

        [$ok, $msg] = PayoutSettlement::markPaid($payout, (string) $request->transfer_reference, Auth::id());

        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function export(Request $request)
    {
        $filters = PayoutFilters::normalize($request->query());
        $query   = PayoutFilters::apply(DoctorPayout::query(), $filters)->with(self::WITH)->orderBy('id');
        $name    = 'liquidaciones-' . now('America/Bogota')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien las tildes
            fputcsv($out, [
                'Liquidación', 'Cita', 'Fecha cita', 'Fecha cobro', 'Modalidad', 'Tipo beneficiario', 'Beneficiario',
                'Médico que atendió', 'Sede', 'Referencia del pago', 'Cobrado al paciente', 'Fee Wompi',
                'Comisión plataforma', 'A pagar', 'Estado', 'Ref. transferencia', 'Fecha de pago', 'Registrado por', 'Lote',
            ], ';', '"', '\\');

            $money = fn ($v) => number_format((float) $v, 2, ',', '');
            $safe  = function ($v) {
                $v = (string) $v;

                return $v !== '' && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; // evita fórmulas en Excel
            };
            $fmt = fn ($d, $f = 'd/m/Y H:i') => $d ? Carbon::parse($d)->timezone('America/Bogota')->format($f) : '';

            $query->chunkById(500, function ($rows) use ($out, $money, $safe, $fmt) {
                foreach ($rows as $p) {
                    $a        = $p->appointment;
                    $isClinic = $p->payable_type === Clinic::class;
                    $virtual  = ($a?->service?->type) === 'virtual';
                    $doctor   = $a?->doctor?->user?->name;
                    fputcsv($out, [
                        $p->id,
                        $p->appointment_id,
                        $a?->date ? Carbon::parse($a->date)->format('d/m/Y') : '',
                        $fmt($p->payment?->approved_at),
                        $virtual ? 'Virtual' : 'Presencial',
                        $isClinic ? 'Clínica' : 'Médico',
                        $safe($isClinic ? ($a?->clinic?->user?->name ?? '') : ($doctor ?? '')),
                        $safe($doctor ?? ''),
                        $safe($virtual ? 'Virtual' : ($a?->address?->name ?? '')),
                        $safe($p->payment?->reference ?? ''),
                        $money($p->total_charged),
                        $money($p->wompi_fee),
                        $money($p->platform_commission),
                        $money($p->amount_to_pay),
                        $p->status === 'paid' ? 'Pagada' : ($p->status === 'pending' ? 'Pendiente' : $p->status),
                        $safe($p->transfer_reference ?? ''),
                        $fmt($p->paid_at),
                        $safe($p->paidBy?->name ?? ''),
                        $safe($p->batch?->number ?? ''),
                    ], ';', '"', '\\');
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
