<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\CommissionAcceptance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Política de pago de las citas presenciales de un médico o clínica:
 *   onsite  → se paga en sede (sin comisión)
 *   choice  → el paciente elige pagar en línea o en sede
 *   online  → pago en línea obligatorio
 *
 * `accepts_online_payments` conserva su significado histórico ("pago en línea obligatorio") y queda sincronizado con `online`.
 * Activar el pago en línea exige haber aceptado la comisión de servicio al porcentaje vigente.
 */
final class PresentialPolicy
{
    public const MODES = ['onsite', 'choice', 'online'];

    /** @return array{presential_payment_mode: string, accepts_online_payments: bool} */
    public static function prepare(Model $owner, Request $request): array
    {
        $current = (string) ($owner->settings->presential_payment_mode ?? 'onsite');
        $mode    = (string) $request->input('presential_payment_mode', $current);

        Validator::make(['presential_payment_mode' => $mode], [
            'presential_payment_mode' => 'required|in:' . implode(',', self::MODES),
        ])->validate();

        if ($mode !== 'onsite') {
            $rate = CommissionRates::forOwner($owner, 'presential');

            if (!CommissionAcceptance::isAcceptedFor($owner, $rate) && !$request->boolean('presential_terms_accepted')) {
                throw ValidationException::withMessages([
                    'presential_terms_accepted' => 'Para activar el pago en línea de tus citas presenciales debes aceptar la comisión de servicio.',
                ]);
            }
        }

        return [
            'presential_payment_mode' => $mode,
            'accepts_online_payments' => $mode === 'online',
        ];
    }

    /** Deja constancia de la aceptación (una sola vez por porcentaje vigente). */
    public static function recordAcceptance(Model $owner, array $data, Request $request): void
    {
        $mode = (string) ($data['presential_payment_mode'] ?? 'onsite');

        if ($mode === 'onsite') {
            return;
        }

        $rate = CommissionRates::forOwner($owner, 'presential');

        if (!CommissionAcceptance::isAcceptedFor($owner, $rate)) {
            CommissionAcceptance::record($owner, $mode, $rate, $request);
        }
    }
}
