<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Asistencia a la cita y su efecto en las liquidaciones. */
final class PayoutAttendance
{
    /** Estados de cita que dan lugar a pago: atendida, o el paciente pagó en línea y no se presentó (se paga al médico). */
    public const PAYABLE_STATUSES = ['completed', 'no_show'];

    /**
     * Liquidaciones pendientes de citas presenciales pagadas en línea que ya pasaron
     * (hasta el corte) y siguen "confirmadas": el médico no las cerró y por eso no entran al lote.
     */
    public static function unclosed(Carbon $cutoff): int
    {
        $cut = $cutoff->copy()->timezone('America/Bogota')->format('Y-m-d H:i:s');

        return (int) DB::table('doctor_payouts')
            ->join('appointments', 'appointments.id', '=', 'doctor_payouts.appointment_id')
            ->leftJoin('services', 'services.id', '=', 'appointments.service_id')
            ->where('doctor_payouts.status', 'pending')
            ->whereNull('doctor_payouts.batch_id')
            ->where('appointments.status', 'confirmed')
            ->where('appointments.payment_status', 'paid')
            ->where(fn ($q) => $q->whereNull('services.type')->orWhere('services.type', '!=', 'virtual'))
            ->whereRaw('TIMESTAMP(appointments.date, appointments.end_time) <= ?', [$cut])
            ->count();
    }
}
