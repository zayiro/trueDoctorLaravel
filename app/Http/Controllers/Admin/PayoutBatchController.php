<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorPayout;
use App\Models\PayoutBatch;
use App\Models\PayoutReport;
use App\Models\BankAccount;
use App\Services\Payments\BankAccountService;
use App\Services\Payments\PayoutReportService;
use Illuminate\Support\Facades\Storage;
use App\Services\Payments\PayoutBatchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutBatchController extends Controller
{
    public function index()
    {
        $batches  = PayoutBatch::orderByDesc('id')->paginate(15);
        $cutoff   = PayoutBatchService::lastCutoff();
        $eligible = PayoutBatchService::eligible($cutoff);
        $eligibleCount = (clone $eligible)->count();
        $eligibleTotal = (float) (clone $eligible)->sum('amount_to_pay');
        $unbatched = DoctorPayout::where('status', 'pending')->whereNull('batch_id')->count();

        return view('administrator.payouts.batches.index', compact('batches', 'cutoff', 'eligibleCount', 'eligibleTotal', 'unbatched'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['cutoff' => 'nullable|date_format:Y-m-d']);
        $cutoff = !empty($data['cutoff'])
            ? Carbon::createFromFormat('Y-m-d', $data['cutoff'], PayoutBatchService::TZ)->endOfDay()
            : PayoutBatchService::lastCutoff();

        if ($cutoff->isFuture()) {
            return back()->with('error', 'El corte no puede ser una fecha futura.');
        }

        $batch = PayoutBatchService::createDraft($cutoff, Auth::id());
        if (!$batch) {
            return back()->with('error', 'No hay liquidaciones elegibles para ese corte (citas realizadas, pagadas y sin reembolso).');
        }

        return redirect()->route('administrator.payouts.batches.show', $batch)->with('success', 'Lote ' . $batch->number . ' creado como borrador. Revísalo antes de aprobarlo.');
    }

    public function show(PayoutBatch $batch)
    {
        $lines = $batch->payouts()->with(['appointment.service', 'appointment.address', 'appointment.doctor.user', 'appointment.clinic.user', 'payment'])
            ->orderBy('id')->get();

        $groups = $lines->groupBy(fn ($p) => $p->payable_type . '|' . $p->payable_id);
        $events = $batch->events()->orderByDesc('id')->limit(15)->get();

        $reports = PayoutReport::where('batch_id', $batch->id)->get()->keyBy(fn ($r) => $r->payable_type . '|' . $r->payable_id);

        $accounts = [];
        foreach ($groups as $key => $lines) {
            $f = $lines->first();
            $accounts[$key] = ['account' => BankAccountService::forOwner($f->payable_type, (int) $f->payable_id), 'flags' => BankAccountService::flags($f->payable_type, (int) $f->payable_id)];
        }

        return view('administrator.payouts.batches.show', compact('batch', 'groups', 'events', 'reports', 'accounts'));
    }

    public function approve(PayoutBatch $batch)
    {
        [$ok, $msg] = PayoutBatchService::approve($batch, Auth::id());

        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function cancel(PayoutBatch $batch)
    {
        [$ok, $msg] = PayoutBatchService::cancel($batch, Auth::id());

        return redirect()->route('administrator.payouts.batches.' . ($ok ? 'index' : 'show'), $ok ? [] : $batch)->with($ok ? 'success' : 'error', $msg);
    }

    public function release(PayoutBatch $batch, DoctorPayout $payout)
    {
        [$ok, $msg] = PayoutBatchService::releaseLine($batch, $payout, Auth::id());

        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function pay(Request $request, PayoutBatch $batch)
    {
        $data = $request->validate([
            'payable_type'       => 'required|in:doctor,clinic',
            'payable_id'         => 'required|integer|min:1',
            'transfer_reference' => 'required|string|max:255',
        ]);

        [$ok, $msg] = PayoutBatchService::payBeneficiary($batch, $data['payable_type'], (int) $data['payable_id'], $data['transfer_reference'], Auth::id());

        return back()->with($ok ? 'success' : 'error', $msg);
    }

    public function downloadReport(PayoutReport $report)
    {
        $path = PayoutReportService::verifiedFile($report);
        if (!$path) {
            return back()->with('error', 'El archivo del soporte no está disponible o no coincide con su huella. Revisa el log (payout_report.*).');
        }

        return Storage::disk(PayoutReportService::DISK)->download($path, 'soporte-liquidacion-' . $report->number . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function resendReport(PayoutReport $report)
    {
        try {
            $sent = PayoutReportService::send($report, true);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo enviar el correo: ' . $e->getMessage());
        }

        return back()->with($sent ? 'success' : 'error', $sent ? 'Soporte reenviado por correo.' : 'El beneficiario no tiene correo registrado.');
    }

    /** Cuenta del beneficiario si, y solo si, tiene líneas en este lote. */
    private function accountInBatch(Request $request, PayoutBatch $batch): array
    {
        $data = $request->validate(['payable_type' => 'required|in:doctor,clinic', 'payable_id' => 'required|integer|min:1']);
        $class = PayoutReportService::classFor($data['payable_type']);
        $has = DoctorPayout::where('batch_id', $batch->id)->where('payable_type', $class)->where('payable_id', $data['payable_id'])->exists();

        return [$has ? BankAccountService::forOwner($class, (int) $data['payable_id']) : null, $data];
    }

    /** Muestra el número completo una vez y deja constancia de quién lo vio. */
    public function revealAccount(Request $request, PayoutBatch $batch)
    {
        [$acc, $data] = $this->accountInBatch($request, $batch);
        if (!$acc) { return back()->with('error', 'Ese beneficiario no tiene cuenta registrada en este lote.'); }

        BankAccountService::logView($acc, Auth::id(), $request->ip(), $batch->id);

        return back()->with('revealed', [$data['payable_type'] . '-' . $data['payable_id'] => (string) $acc->account_number]);
    }

    public function verifyAccount(Request $request, PayoutBatch $batch)
    {
        [$acc, $data] = $this->accountInBatch($request, $batch);
        $request->validate(['method' => 'required|in:' . implode(',', array_keys(BankAccount::METHODS))]);
        if (!$acc) { return back()->with('error', 'Ese beneficiario no tiene cuenta registrada en este lote.'); }

        BankAccountService::verify($acc, (string) $request->input('method'), Auth::id(), $request->ip());

        return back()->with('success', 'Cuenta marcada como verificada.');
    }
}
