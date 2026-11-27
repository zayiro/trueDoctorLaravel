<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Números reales (de la base de datos) de las líneas que los tienen. Cada método devuelve una lista de
 * ['key', 'label', 'value', 'format' => 'int|money'] y es independiente de Google Analytics.
 */
final class BusinessLineDb
{
    /** @return array<int, array{key:string,label:string,value:int|float,format:string}> */
    public function metrics(string $name, Carbon $from, Carbon $to): array
    {
        return match ($name) {
            'skin' => $this->skin($from, $to),
            'chat' => $this->chat($from, $to),
            default => [],
        };
    }

    private function skin(Carbon $from, Carbon $to): array
    {
        $t = fn () => DB::table('skin_analyses');

        return [
            ['key' => 'created', 'label' => 'Solicitudes creadas', 'value' => (int) $t()->whereBetween('created_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'paid', 'label' => 'Análisis pagados', 'value' => (int) $t()->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'completed', 'label' => 'Informes entregados', 'value' => (int) $t()->whereNotNull('completed_at')->whereBetween('completed_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'failed', 'label' => 'Análisis con fallo', 'value' => (int) $t()->whereNotNull('failure_reason')->whereBetween('created_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'revenue', 'label' => 'Ingresos cobrados', 'value' => (int) $t()->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to])->sum('total_amount'), 'format' => 'money'],
        ];
    }

    private function chat(Carbon $from, Carbon $to): array
    {
        $s = fn () => DB::table('ai_chat_sessions');

        return [
            ['key' => 'created', 'label' => 'Sesiones creadas', 'value' => (int) $s()->whereBetween('created_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'paid', 'label' => 'Sesiones pagadas', 'value' => (int) $s()->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'used', 'label' => 'Sesiones con conversación', 'value' => (int) $s()->whereNotNull('paid_at')->where('user_message_count', '>', 0)->whereBetween('paid_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'reports', 'label' => 'Informes enviados', 'value' => (int) $s()->whereNotNull('report_sent_at')->whereBetween('report_sent_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'waitlist', 'label' => 'Inscritos en lista de espera', 'value' => (int) DB::table('ai_chat_waitlist')->whereBetween('created_at', [$from, $to])->count(), 'format' => 'int'],
            ['key' => 'revenue', 'label' => 'Ingresos cobrados', 'value' => (int) $s()->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to])->sum('total_cop'), 'format' => 'money'],
        ];
    }
}
