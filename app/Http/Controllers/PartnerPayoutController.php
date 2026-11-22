<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\PayoutReport;
use App\Services\Payments\BankAccountService;
use App\Services\Payments\PayoutBatchService;
use App\Services\Payments\PayoutReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** "Mis liquidaciones" del médico o la clínica (solo ve lo suyo). */
class PartnerPayoutController extends Controller
{
    /** @return array{0: string, 1: int, 2: string} [clase, id, prefijo de rutas] */
    private function owner(): array
    {
        $user = Auth::user();
        if ($user?->role === 'clinic' && $user->clinic) { return [Clinic::class, (int) $user->clinic->id, 'partner.clinic.payouts.']; }
        if ($user?->role === 'doctor' && $user->doctor) { return [Doctor::class, (int) $user->doctor->id, 'partner.payouts.']; }
        abort(403);
    }

    public function index()
    {
        [$class, $id, $prefix] = $this->owner();

        $pending = DoctorPayout::where('payable_type', $class)->where('payable_id', $id)->where('status', 'pending')
            ->with(['appointment.service', 'appointment.address', 'batch'])->orderBy('id')->limit(100)->get();
        $pendingTotal = DoctorPayout::where('payable_type', $class)->where('payable_id', $id)->where('status', 'pending')->sum('amount_to_pay');

        $year = now('America/Bogota')->startOfYear()->timezone(config('app.timezone', 'UTC'));
        $paidYear = PayoutReport::where('payable_type', $class)->where('payable_id', $id)->where('paid_at', '>=', $year)->sum('total_amount');

        $reports = PayoutReport::where('payable_type', $class)->where('payable_id', $id)->orderByDesc('paid_at')->paginate(10);

        $nextPayDate = PayoutBatchService::nextPayDate();
        $nextCutoff  = PayoutBatchService::lastCutoff($nextPayDate->copy()->setTime(12, 0));

        $account = BankAccountService::forOwner($class, $id);
        $banks   = BankAccountService::BANKS;

        return view('partner.payouts.index', compact('pending', 'pendingTotal', 'paidYear', 'reports', 'prefix', 'nextPayDate', 'nextCutoff', 'account', 'banks'));
    }

    public function download(PayoutReport $report)
    {
        [$class, $id, $prefix] = $this->owner();
        abort_unless($report->payable_type === $class && (int) $report->payable_id === $id, 404);

        $path = PayoutReportService::verifiedFile($report);
        if (!$path) {
            return redirect()->route($prefix . 'index')->with('error', 'No pudimos abrir ese soporte en este momento. Escríbenos y te lo enviamos de nuevo.');
        }

        return Storage::disk(PayoutReportService::DISK)->download($path, 'soporte-liquidacion-' . $report->number . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** Guarda la cuenta donde el beneficiario recibe sus liquidaciones. */
    public function saveAccount(Request $request)
    {
        [$class, $id, $prefix] = $this->owner();
        $existing = BankAccountService::forOwner($class, $id);

        $digitsOk = function (int $min, int $max) {
            return function ($attr, $value, $fail) use ($min, $max) {
                if ($value === null || $value === '') { return; }
                $n = strlen(BankAccountService::digits((string) $value));
                if ($n < $min || $n > $max) { $fail('Debe tener entre ' . $min . ' y ' . $max . ' dígitos.'); }
            };
        };

        $data = $request->validate([
            'bank_name'           => 'required|string|max:80',
            'account_type'        => 'required|in:savings,checking',
            'account_number'      => [$existing ? 'nullable' : 'required', 'regex:/^[0-9\s\-]+$/', $digitsOk(6, 20)],
            'account_holder_name' => 'required|string|max:120',
            'id_type'             => 'required|in:CC,NIT,CE',
            'account_holder_id'   => ['required', 'regex:/^[0-9.\-\s]+$/', $digitsOk(5, 15)],
        ], [
            'account_number.regex'    => 'El número de cuenta solo puede tener dígitos.',
            'account_holder_id.regex' => 'El documento solo puede tener dígitos.',
        ]);

        [, $created, $changed] = BankAccountService::save($class, $id, $data, Auth::user(), $request->ip());

        $msg = $created ? 'Cuenta registrada. Te enviamos un correo de confirmación.' : ($changed ? 'Cuenta actualizada. Te enviamos un correo de confirmación y la verificaremos en tu próxima transferencia.' : 'No había cambios que guardar.');

        return redirect()->route($prefix . 'index')->with('success', $msg);
    }
}
