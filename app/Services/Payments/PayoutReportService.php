<?php

namespace App\Services\Payments;

use App\Mail\PayoutReportMail;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\PayoutBatch;
use App\Models\PayoutReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Soporte de liquidación por beneficiario: se genera al registrar la transferencia, se guarda con su hash
 * y se envía por correo. No es una factura; es el soporte de un pago ya hecho.
 */
class PayoutReportService
{
    public const DISK = 'local';

    public static function classFor(string $type): ?string
    {
        return ['doctor' => Doctor::class, 'clinic' => Clinic::class][$type] ?? null;
    }

    /** Datos de empresa para el encabezado (se llenan con `php artisan payouts:company`). */
    public static function company(): array
    {
        $s = DB::table('settings')->whereIn('key', ['company_legal_name', 'company_nit', 'company_address', 'company_city', 'company_email'])->pluck('value', 'key');

        return [
            'name'    => trim((string) ($s['company_legal_name'] ?? '')) ?: 'OpenDoctor',
            'nit'     => trim((string) ($s['company_nit'] ?? '')),
            'address' => trim((string) ($s['company_address'] ?? '')),
            'city'    => trim((string) ($s['company_city'] ?? '')),
            'email'   => trim((string) ($s['company_email'] ?? '')),
        ];
    }

    public static function beneficiary(PayoutReport $r): array
    {
        $isClinic = $r->payable_type === Clinic::class;
        $model = $isClinic ? Clinic::with('user')->find($r->payable_id) : Doctor::with('user')->find($r->payable_id);

        return [
            'isClinic' => $isClinic,
            'name'     => $model?->user?->name ?? ($isClinic ? 'Clínica' : 'Médico'),
            'document' => $isClinic ? ($model?->nit ? 'NIT ' . $model->nit : '') : ($model?->identification ? 'Documento ' . $model->identification : ''),
            'email'    => $model?->user?->email,
        ];
    }

    public static function verificationCode(PayoutReport $r): string
    {
        return strtoupper(substr(hash_hmac('sha256', $r->number . '|' . number_format((float) $r->total_amount, 2, '.', '') . '|' . $r->transfer_reference, (string) config('app.key')), 0, 12));
    }

    public static function linesFor(PayoutReport $r)
    {
        return DoctorPayout::where('batch_id', $r->batch_id)->where('payable_type', $r->payable_type)->where('payable_id', $r->payable_id)
            ->where('status', DoctorPayout::STATUS_PAID)
            ->with(['appointment.service', 'appointment.address'])->orderBy('id')->get();
    }

    public static function html(PayoutReport $r): string
    {
        $lines = self::linesFor($r)->map(function ($p) {
            $a = $p->appointment;
            $virtual = ($a?->service?->type) === 'virtual';

            return [
                'appointment' => $p->appointment_id,
                'date'        => $a?->date ? \Carbon\Carbon::parse($a->date)->format('d/m/Y') : '',
                'modality'    => $virtual ? 'Virtual' : 'Presencial',
                'place'       => $virtual ? 'Virtual' : ($a?->address?->name ?? ''),
                'amount'      => (float) $p->amount_to_pay,
            ];
        });

        return view('pdf.payout_report', [
            'report'       => $r,
            'company'      => self::company(),
            'beneficiary'  => self::beneficiary($r),
            'lines'        => $lines,
            'verification' => self::verificationCode($r),
        ])->render();
    }

    /** Crea (o completa) el soporte del beneficiario en el lote. Devuelve null si no tiene líneas pagadas. */
    public static function ensure(PayoutBatch $batch, string $type, int $id): ?PayoutReport
    {
        $class = self::classFor($type);
        if (!$class) { return null; }

        $report = PayoutReport::where('batch_id', $batch->id)->where('payable_type', $class)->where('payable_id', $id)->first();
        if ($report && $report->pdf_path && Storage::disk(self::DISK)->exists($report->pdf_path)) {
            return $report;
        }

        $lines = DoctorPayout::where('batch_id', $batch->id)->where('payable_type', $class)->where('payable_id', $id)
            ->where('status', DoctorPayout::STATUS_PAID)->orderBy('id')->get();
        if ($lines->isEmpty()) { return null; }

        $report = DB::transaction(function () use ($report, $batch, $class, $id, $lines) {
            $data = [
                'transfer_reference' => $lines->first()->transfer_reference,
                'total_amount'       => $lines->sum('amount_to_pay'),
                'lines_count'        => $lines->count(),
                'paid_at'            => $lines->max('paid_at'),
            ];
            if ($report) { $report->update($data); return $report; }

            $seq = PayoutReport::where('batch_id', $batch->id)->count() + 1;

            return PayoutReport::create($data + [
                'batch_id'     => $batch->id,
                'payable_type' => $class,
                'payable_id'   => $id,
                'number'       => 'SL-' . substr($batch->number, 3) . '-' . str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
            ]);
        });

        if ($report->pdf_hash) {
            Log::warning('payout_report.regenerated', ['report' => $report->id, 'motivo' => 'archivo no encontrado']);
        }

        $bin  = Pdf::loadHTML(self::html($report))->setPaper('letter')->output();
        $path = 'payout-reports/' . $batch->id . '/' . $report->number . '.pdf';
        Storage::disk(self::DISK)->put($path, $bin);
        $report->update(['pdf_path' => $path, 'pdf_hash' => hash('sha256', $bin), 'generated_at' => now()]);

        return $report->fresh();
    }

    /** Envía el soporte por correo al beneficiario (una sola vez, salvo $force). */
    public static function send(PayoutReport $r, bool $force = false): bool
    {
        if ($r->emailed_at && !$force) { return false; }

        $email = self::beneficiary($r)['email'];
        if (!$email) {
            Log::warning('payout_report.no_email', ['report' => $r->id]);

            return false;
        }

        Mail::to($email)->send(new PayoutReportMail($r));
        $r->update(['emailed_at' => now(), 'email_to' => $email]);

        return true;
    }

    /** Ruta de descarga solo si el archivo existe y su hash coincide con el que se envió. */
    public static function verifiedFile(PayoutReport $r): ?string
    {
        if (!$r->pdf_path || !Storage::disk(self::DISK)->exists($r->pdf_path)) { return null; }
        if (!hash_equals((string) $r->pdf_hash, hash('sha256', Storage::disk(self::DISK)->get($r->pdf_path)))) {
            Log::error('payout_report.hash_mismatch', ['report' => $r->id]);

            return null;
        }

        return $r->pdf_path;
    }
}
