<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Plans\PlanRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Reembolsos de planes (suscripciones): el dinero se devuelve en el panel de Wompi y aquí se registra. */
class PlanRefundController extends Controller
{
    private const STATUSES = ['approved' => 'Aprobados', 'refunded' => 'Reembolsados', 'voided' => 'Anulados'];

    public function index(Request $request)
    {
        $status = (string) $request->input('status', 'approved');
        $statuses = array_key_exists($status, self::STATUSES) ? [$status] : array_keys(self::STATUSES);

        $payments = DB::table('payments as p')
            ->join('subscriptions as s', 's.id', '=', 'p.payable_id')
            ->join('plans as pl', 'pl.id', '=', 's.plan_id')
            ->join('users as u', 'u.id', '=', 's.doctor_id')
            ->where('p.kind', 'subscription')
            ->whereIn('p.status', $statuses)
            ->orderByDesc('p.id')
            ->select('p.id', 'p.reference', 'p.status', 'p.total_in_cents', 'p.approved_at', 'p.provider_transaction_id',
                's.id as sub_id', 's.status as sub_status', 's.ends_at', 'pl.name as plan', 'u.name as user_name', 'u.email', 'u.role')
            ->paginate(15)
            ->withQueryString();

        $count = fn (string $s) => DB::table('payments')->where('kind', 'subscription')->where('status', $s)->count();

        return view('administrator.plan-refunds.index', [
            'payments' => $payments,
            'statuses' => self::STATUSES,
            'filter'   => array_key_exists($status, self::STATUSES) ? $status : 'all',
            'counts'   => ['approved' => $count('approved'), 'refunded' => $count('refunded'), 'voided' => $count('voided')],
        ]);
    }

    public function refund(Request $request, Payment $payment, PlanRefundService $service)
    {
        $data = $request->validate([
            'provider_reference' => ['required', 'string', 'max:120'],
            'note'               => ['nullable', 'string', 'max:300'],
            'confirm'            => ['accepted'],
        ], [
            'provider_reference.required' => 'Escribe la referencia de la devolución en Wompi.',
            'confirm.accepted'            => 'Confirma que ya devolviste el dinero en el panel de Wompi.',
        ]);

        try {
            $out = $service->refundPayment($payment, $request->user(), trim($data['provider_reference']), $data['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        $service->notify($out);

        if (!$out['changed']) {
            return back()->with('success', 'Reembolso registrado. La suscripción ya estaba retirada, no hubo cambios de plan.');
        }
        $msg = $out['kept_until']
            ? 'Reembolso registrado. El usuario conserva su plan hasta el ' . $out['kept_until']->copy()->timezone('America/Bogota')->format('d/m/Y') . ' por su periodo anterior.'
            : 'Reembolso registrado y plan retirado' . ($out['to_plan'] ? ' (pasó al ' . $out['to_plan']->name . ')' : '') . '. El usuario recibió un correo.';

        return back()->with('success', $msg);
    }
}
