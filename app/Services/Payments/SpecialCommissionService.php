<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Mail\SpecialCommissionChangedMail;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\SpecialCommissionChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;

/**
 * Porcentajes especiales de comisión por médico o clínica (vacío = tarifa general).
 * Cada cambio queda en special_commission_changes con motivo, usuario e IP.
 */
final class SpecialCommissionService
{
    public const TYPES = [
        'doctor' => ['model' => Doctor::class, 'table' => 'doctor_settings', 'fk' => 'doctor_id', 'label' => 'Médicos'],
        'clinic' => ['model' => Clinic::class, 'table' => 'clinic_settings', 'fk' => 'clinic_id', 'label' => 'Clínicas'],
    ];

    public const COLUMNS = ['virtual' => 'special_virtual_commission', 'presential' => 'special_presential_commission'];
    public const MODALITY_LABELS = ['virtual' => 'Virtual', 'presential' => 'Presencial'];

    /** Un porcentaje de 0 % o mayor a este valor pide confirmación explícita. */
    public const CONFIRM_ABOVE = 30.0;

    /** Texto del formulario → porcentaje con 2 decimales, o null si está vacío (tarifa general). */
    public static function parse(mixed $raw, string $label): ?float
    {
        $s = str_replace([' ', '%'], '', trim((string) ($raw ?? '')));
        if ($s === '') {
            return null;
        }
        $s = str_replace(',', '.', $s);
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $s) || (float) $s > 100) {
            throw new InvalidArgumentException("{$label}: escribe un número entre 0 y 100 (máximo 2 decimales) o déjalo vacío para usar la tarifa general.");
        }

        return round((float) $s, 2);
    }

    public static function needsConfirmation(?float $value): bool
    {
        return $value !== null && ($value == 0.0 || $value > self::CONFIRM_ABOVE);
    }

    public static function percent(?float $v): string
    {
        return $v === null ? '—' : rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',') . ' %';
    }

    /** Porcentaje general (sin especial) que se aplicaría. */
    public static function general(string $type, string $modality): float
    {
        return CommissionRates::resolve($type === 'clinic', $modality === 'virtual', null);
    }

    /**
     * @param array{virtual?: ?float, presential?: ?float} $values  null = quitar el especial
     * @return array<int, array{modality: string, old: ?float, new: ?float}> cambios realmente aplicados
     */
    public static function update(string $type, int $id, array $values, string $reason, ?int $userId, ?string $ip): array
    {
        $cfg = self::TYPES[$type] ?? throw new InvalidArgumentException('Tipo de beneficiario no válido.');
        $reason = trim($reason);
        $changes = [];

        DB::transaction(function () use ($type, $id, $values, $reason, $userId, $ip, $cfg, &$changes) {
            $row = DB::table($cfg['table'])->where($cfg['fk'], $id)->lockForUpdate()->first();
            if (!$row) {
                throw new RuntimeException('Este beneficiario no tiene configuración de pagos creada todavía.');
            }

            foreach (self::COLUMNS as $mod => $col) {
                if (!array_key_exists($mod, $values)) {
                    continue;
                }
                $old = $row->{$col} === null ? null : round((float) $row->{$col}, 2);
                $new = $values[$mod] === null ? null : round((float) $values[$mod], 2);
                if ($old === $new) {
                    continue;
                }
                if (mb_strlen($reason) < 5) {
                    throw new InvalidArgumentException('Escribe el motivo del cambio (mínimo 5 caracteres).');
                }

                DB::table($cfg['table'])->where('id', $row->id)->update([$col => $new, 'updated_at' => now()]);
                SpecialCommissionChange::create([
                    'owner_type' => $type, 'owner_id' => $id, 'modality' => $mod,
                    'old_value' => $old, 'new_value' => $new,
                    'reason' => mb_substr($reason, 0, 255), 'changed_by' => $userId, 'ip' => $ip,
                ]);
                $changes[] = ['modality' => $mod, 'old' => $old, 'new' => $new];
            }
        });

        if ($changes) {
            self::notify($type, $id);
        }

        return $changes;
    }

    private static function notify(string $type, int $id): void
    {
        try {
            $owner = self::TYPES[$type]['model']::with(['user', 'settings'])->find($id);
            $user = $owner?->user;
            if (!$user || !$user->email) {
                return;
            }
            $s = $owner->settings;
            $text = function (string $mod) use ($owner, $s) {
                $special = $s?->getAttribute(self::COLUMNS[$mod]) !== null;
                $rate = CommissionRates::forOwner($owner, $mod);

                return self::percent($rate) . ($special ? ' (porcentaje especial)' : ' (tarifa general)');
            };
            Mail::to($user->email)->queue(new SpecialCommissionChangedMail(
                (string) $user->name, $text('virtual'), $text('presential'), $type === 'clinic', now('America/Bogota')->format('d/m/Y H:i'),
            ));
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar el cambio de porcentaje especial: ' . $e->getMessage());
        }
    }
}
