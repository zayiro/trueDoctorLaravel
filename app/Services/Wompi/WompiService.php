<?php

namespace App\Services\Wompi;

use App\Models\Appointment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class WompiService
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $checkoutUrl;
    protected string $redirectUrl;

    public function __construct()
    {
        $this->publicKey    = config('services.wompi.public_key');
        $this->privateKey   = config('services.wompi.private_key');
        $this->checkoutUrl  = config('services.wompi.checkout_url');
        $this->redirectUrl  = config('services.wompi.redirect_url');
    }

    /**
     * Genera la URL de checkout de Wompi y crea la suscripción en estado pending.
     */
    public function buildCheckoutUrl(int $doctorId, Plan $plan): array
    {
        $reference     = 'OD-' . $doctorId . '-' . Str::upper(Str::random(8)) . '-' . time();
        $amountInCents = (int) ($plan->price * 100); // asegura que sea entero
        $currency      = 'COP';
        $integrity     = config('services.wompi.integrity_secret');

        // Debug temporal — eliminar después
        \Log::info('Wompi signature debug', [
            'reference'     => $reference,
            'amountInCents' => $amountInCents,
            'currency'      => $currency,
            'integrity'     => $integrity,
            'cadena'        => $reference . $amountInCents . $currency . $integrity,
            'signature'     => hash('sha256', $reference . $amountInCents . $currency . $integrity),
        ]);

        $signature = hash('sha256', $reference . $amountInCents . $currency . $integrity);

        $params = http_build_query([
            'public-key'          => $this->publicKey,
            'currency'            => $currency,
            'amount-in-cents'     => $amountInCents,
            'reference'           => $reference,
            'redirect-url'        => $this->redirectUrl,
            'signature:integrity' => $signature,
        ]);

        $subscription = Subscription::create([
            'doctor_id'       => $doctorId,
            'plan_id'         => $plan->id,
            'wompi_reference' => $reference,
            'status'          => 'pending',
            'amount_in_cents' => $amountInCents,
            'currency'        => $currency,
            'starts_at'       => now(),
            'ends_at'         => now()->addDays(30),
        ]);

        return [
            'subscription' => $subscription,
            'checkout_url' => config('services.wompi.checkout_url') . '?' . $params,
        ];
    }

    /**
     * Consulta el estado de una transacción en Wompi por referencia.
     */
    public function getTransactionByReference(string $reference): ?array
    {
        $response = Http::withToken($this->privateKey)
            ->get("https://production.wompi.co/v1/transactions", [
                'reference' => $reference,
            ]);

        if ($response->successful()) {
            $data = $response->json('data');
            return $data[0] ?? null;
        }

        return null;
    }

    /**
     * Valida la firma del webhook de Wompi.
     */
    public function validateWebhookSignature(array $payload, string $signature): bool
    {
        $properties = $payload['event'] . $payload['timestamp'];
        $computed    = hash_hmac('sha256', $properties, config('services.wompi.events_secret'));
        return hash_equals($computed, $signature);
    }

    /**
     * Genera la firma de integridad requerida por Wompi.
     */
    public function generateSignature(string $reference, int $amountInCents, string $currency): string
    {
        $integrity             = config('services.wompi.integrity_secret'); // clave de integridad, diferente a la privada
        $signatureIntegrity    = $reference . $amountInCents . $currency . $integrity;
        return hash('sha256', $signatureIntegrity);
    }

    /**
     * Genera la URL de checkout para pago de cita virtual.
     */
    public function buildAppointmentCheckoutUrl(Appointment $appointment): array
    {
        // Determinar si es clínica o médico particular
        $isClinic  = !is_null($appointment->clinic_id);

        // Obtener comisión correcta según tipo
        $commissionKey  = $isClinic
            ? 'virtual_commission_clinic'
            : 'virtual_commission_doctor';

        $commissionRate = Setting::where('key', $commissionKey)->value('value') ?? 15;

        // Fee de Wompi configurable
        $wompiFeeRate = Setting::where('key', 'wompi_fee')->value('value') ?? 2.9;

        // Cálculos
        $price            = $appointment->price;
        $commissionAmount = round($price * $commissionRate / 100, 2);
        $totalToPay       = $price + $commissionAmount;
        $wompiFee         = round($totalToPay * ($wompiFeeRate / 100), 2);
        $doctorAmount     = $price;
        $platformAmount   = round($commissionAmount - $wompiFee, 2);

        $amountInCents = (int) round($totalToPay * 100);
        $reference     = 'APT-' . $appointment->id . '-' . time();
        $currency      = 'COP';
        $signature     = $this->generateSignature($reference, $amountInCents, $currency);

        // Guardar en la cita
        $updated = $appointment->update([
            'wompi_reference'   => $reference,
            'payment_status'    => 'pending',
            'commission_amount' => $commissionAmount,
            'doctor_amount'     => $doctorAmount,
            'platform_amount'   => $platformAmount,
        ]);

        \Log::info('Wompi reference update', [
            'appointment_id'  => $appointment->id,
            'wompi_reference' => $reference,
            'updated'         => $updated,
        ]);

        $params = http_build_query([
            'public-key'          => $this->publicKey,
            'currency'            => $currency,
            'amount-in-cents'     => $amountInCents,
            'reference'           => $reference,
            'redirect-url'        => route('appointments.payment.result'),
            'signature:integrity' => $signature,
        ]);

        return [
            'checkout_url'      => $this->checkoutUrl . '?' . $params,
            'commission_rate'   => $commissionRate,
            'commission_amount' => $commissionAmount,
            'total'             => $totalToPay,
            'wompi_fee'         => $wompiFee,
            'wompi_fee_rate'    => $wompiFeeRate,
            'doctor_amount'     => $doctorAmount,
            'platform_amount'   => $platformAmount,
            'is_clinic'         => $isClinic,
        ];
    }
}