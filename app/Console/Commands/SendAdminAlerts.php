<?php

namespace App\Console\Commands;

use App\Mail\AdminAlertsMail;
use App\Services\Alerts\AdminAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendAdminAlerts extends Command
{
    protected $signature = 'alerts:send {--dry-run : Muestra lo que enviaría, sin enviar ni registrar nada} {--test : Envía un correo de prueba ahora}';

    protected $description = 'Envía al administrador un correo con los avisos activos (saldos bajos, sin crédito, cobros próximos)';

    public function handle(AdminAlerts $alerts): int
    {
        $to = config('ai_credits.alerts_address') ?: config('mail.admin_address') ?: config('services.mail_site.mail_admin_address');
        if (!$to) {
            $this->error('No hay destinatario: define MAIL_ADMIN_ADDRESS (o MAIL_ALERTS_ADDRESS) en el .env.');

            return self::FAILURE;
        }

        if ($this->option('test')) {
            Mail::to($to)->send(new AdminAlertsMail([['key' => 'test', 'level' => 'warn', 'title' => 'Prueba de avisos', 'detail' => 'Si ves este correo, la entrega funciona.']], true));
            $this->info("Correo de prueba enviado a {$to} (sale por la cola; puede tardar unos segundos).");

            return self::SUCCESS;
        }

        $items = $alerts->collect();
        $keys = array_column($items, 'key');
        $dry = (bool) $this->option('dry-run');

        // Avisos que ya se resolvieron: se olvidan, para volver a avisar si reaparecen.
        if (!$dry) {
            DB::table('admin_alerts_sent')->when($keys, fn ($q) => $q->whereNotIn('alert_key', $keys))->delete();
        }

        foreach ($items as $i) {
            $this->line(sprintf('  [%s] %s — %s', $i['level'], $i['title'], $i['detail']));
        }
        if (!$items) {
            $this->info('Sin avisos activos.');

            return self::SUCCESS;
        }

        $sent = DB::table('admin_alerts_sent')->pluck('last_sent_at', 'alert_key');
        $send = false;
        foreach ($items as $i) {
            $at = $sent[$i['key']] ?? null;
            if (!$at || Carbon::parse($at)->lt(now()->subDay())) {
                $send = true;
            }
        }
        if (!$send) {
            $this->info('Avisos activos, pero ya se notificaron en las últimas 24 horas: no se envía otro correo.');

            return self::SUCCESS;
        }
        if ($dry) {
            $this->info("DRY-RUN: se enviaría 1 correo con " . count($items) . " aviso(s) a {$to}.");

            return self::SUCCESS;
        }

        Mail::to($to)->send(new AdminAlertsMail($items));
        foreach ($items as $i) {
            DB::table('admin_alerts_sent')->updateOrInsert(['alert_key' => $i['key']], ['level' => $i['level'], 'last_sent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->info('Correo enviado a ' . $to . ' con ' . count($items) . ' aviso(s).');

        return self::SUCCESS;
    }
}
