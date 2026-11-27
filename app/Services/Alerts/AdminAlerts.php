<?php

namespace App\Services\Alerts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reúne los avisos activos para el administrador: saldos bajos o agotados de proveedores
 * y cobros recurrentes próximos o vencidos. Usa las mismas reglas que el dashboard.
 *
 * @return array<int, array{key:string, level:string, title:string, detail:string}>
 */
final class AdminAlerts
{
    private const LABELS = [
        'anthropic' => 'Claude (Anthropic)', 'openai' => 'OpenAI', 'deepseek' => 'DeepSeek',
        'twilio' => 'Twilio', 'deepgram' => 'Deepgram',
    ];

    public function collect(): array
    {
        return array_merge($this->credits(), $this->bills(), $this->reviewReports());
    }

    private function credits(): array
    {
        if (!Schema::hasTable('ai_provider_credits')) {
            return [];
        }

        $items = [];
        foreach (DB::table('ai_provider_credits')->get() as $r) {
            $label = self::LABELS[$r->provider] ?? $r->provider;
            $bal = $r->last_balance !== null ? (float) $r->last_balance : null;
            $sym = ['USD' => '$', 'CNY' => '¥'][(string) $r->last_currency] ?? '$';
            $billingErr = $r->billing_error_at && Carbon::parse($r->billing_error_at)->gt(now()->subDay());

            if ($billingErr) {
                $items[] = ['key' => "credit:{$r->provider}:out", 'level' => 'crit', 'title' => "{$label}: sin crédito",
                    'detail' => $r->last_error ?: 'El proveedor rechazó una llamada por falta de saldo. Recarga la cuenta.'];
            } elseif ($bal !== null && (($r->last_available !== null && !$r->last_available) || $bal <= 0)) {
                $items[] = ['key' => "credit:{$r->provider}:out", 'level' => 'crit', 'title' => "{$label}: sin saldo",
                    'detail' => "Saldo: {$sym}" . number_format($bal, 2) . '. Recarga la cuenta.'];
            } elseif ($bal !== null && $bal <= (float) $r->alert_amount) {
                $items[] = ['key' => "credit:{$r->provider}:low", 'level' => 'warn', 'title' => "{$label}: saldo bajo",
                    'detail' => "Saldo: {$sym}" . number_format($bal, 2) . " (tu umbral de aviso es {$sym}" . number_format((float) $r->alert_amount, 2) . ').'];
            }
        }

        return $items;
    }

    /** Reseñas reportadas que esperan decisión (se avisa una vez al día mientras haya pendientes). */
    private function reviewReports(): array
    {
        if (!Schema::hasTable('review_reports')) {
            return [];
        }

        $n = (int) DB::table('review_reports')->where('status', 'pending')->distinct()->count('review_id');

        if ($n === 0) {
            return [];
        }

        return [['key' => 'reviews:reports', 'level' => 'warn',
            'title' => $n === 1 ? '1 reseña reportada por revisar' : "{$n} reseñas reportadas por revisar",
            'detail' => 'Revísalas en Administración → Reseñas reportadas y decide si se mantienen u ocultan.']];
    }

    private function bills(): array
    {
        if (!Schema::hasTable('recurring_bills')) {
            return [];
        }

        $items = [];
        $today = now()->startOfDay();
        $advance = fn ($d, $c) => match ($c) {
            'yearly' => $d->copy()->addYear(),
            'quarterly' => $d->copy()->addMonths(3),
            default => $d->copy()->addMonth(),
        };

        foreach (DB::table('recurring_bills')->where('active', 1)->get() as $b) {
            $due = Carbon::parse($b->due_at)->startOfDay();
            if ($b->auto_renew && $b->cadence !== 'once') {
                $guard = 0;
                while ($due->lt($today) && $guard++ < 60) {
                    $due = $advance($due, $b->cadence);
                }
            }
            // Un cobro automático en $0 (ej. Zoom hoy) se ve en el dashboard, pero no merece correo.
            if ($b->auto_renew && $b->amount !== null && (float) $b->amount == 0.0) {
                continue;
            }

            $days = (int) $today->diffInDays($due, false);
            $amount = $b->amount === null ? 'monto por definir' : '$' . number_format((float) $b->amount, 2) . ' ' . $b->currency;
            $when = $days < 0 ? 'vencido hace ' . abs($days) . ' día(s)' : ($days === 0 ? 'vence hoy' : "vence en {$days} día(s)");

            if ($days < 0) {
                $items[] = ['key' => "bill:{$b->id}:{$due->toDateString()}:overdue", 'level' => 'crit', 'title' => "Cobro vencido: {$b->name}", 'detail' => "{$amount} · {$due->format('d/m/Y')} · {$when}"];
            } elseif ($days <= (int) $b->remind_days) {
                $items[] = ['key' => "bill:{$b->id}:{$due->toDateString()}:soon", 'level' => 'warn', 'title' => "Cobro próximo: {$b->name}", 'detail' => "{$amount} · {$due->format('d/m/Y')} · {$when}"];
            }
        }

        return $items;
    }
}
