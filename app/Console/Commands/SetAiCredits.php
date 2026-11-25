<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetAiCredits extends Command
{
    protected $signature = 'ai:credits {provider : anthropic|openai|deepseek|twilio|deepgram} {amount? : saldo actual en USD, tal como lo ves en la consola del proveedor} {--alert= : umbral de alerta (avisa cuando el saldo baje de este valor)}';

    protected $description = 'Registra el saldo actual de un proveedor de IA y/o su umbral de alerta';

    public function handle(): int
    {
        $provider = (string) $this->argument('provider');
        if (!in_array($provider, ['anthropic', 'openai', 'deepseek', 'twilio', 'deepgram'], true)) {
            $this->error('Proveedor no válido. Usa: anthropic, openai, deepseek, twilio o deepgram.');

            return self::FAILURE;
        }

        $update = [];
        $amount = $this->argument('amount');
        if ($amount !== null) {
            if (in_array($provider, ['deepseek', 'twilio'], true)) {
                $this->error('DeepSeek y Twilio se consultan solos por API; no hace falta registrar el saldo.');

                return self::FAILURE;
            }
            if (!is_numeric($amount) || (float) $amount < 0) {
                $this->error('El monto debe ser un número positivo en USD.');

                return self::FAILURE;
            }
            $update += [
                'baseline_usd' => (float) $amount, 'baseline_at' => now(),
                'last_balance' => (float) $amount, 'last_currency' => 'USD', 'last_available' => (float) $amount > 0 ? 1 : 0,
                'last_source' => 'manual', 'spent_since' => 0, 'last_checked_at' => now(), 'billing_error_at' => null,
            ];
        }
        if ($this->option('alert') !== null) {
            if (!is_numeric($this->option('alert'))) {
                $this->error('--alert debe ser un número.');

                return self::FAILURE;
            }
            $update['alert_amount'] = (float) $this->option('alert');
        }

        if ($update) {
            DB::table('ai_provider_credits')->where('provider', $provider)->update($update + ['updated_at' => now()]);
        }

        $r = DB::table('ai_provider_credits')->where('provider', $provider)->first();
        $this->info(sprintf('%s: saldo=%s %s · alerta<=%s · fuente=%s · último chequeo=%s', $provider,
            $r->last_balance ?? '—', $r->last_currency ?? '', $r->alert_amount, $r->last_source ?? '—', $r->last_checked_at ?? '—'));

        return self::SUCCESS;
    }
}
