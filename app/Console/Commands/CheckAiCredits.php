<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckAiCredits extends Command
{
    protected $signature = 'ai:check-credits {--debug : Muestra el inicio de la respuesta cruda de las APIs de costos}';

    protected $description = 'Consulta el saldo real de DeepSeek, Twilio y Deepgram y estima el de Claude y OpenAI (saldo registrado menos gasto)';

    public function handle(): int
    {
        foreach (['deepseek', 'anthropic', 'openai', 'twilio', 'deepgram'] as $provider) {
            try {
                $res = match ($provider) {
                    'deepseek' => $this->deepseek(),
                    'anthropic' => $this->anthropic(),
                    'openai' => $this->openai(),
                    'twilio' => $this->twilio(),
                    'deepgram' => $this->deepgram(),
                };
            } catch (\Throwable $e) {
                $res = ['error' => mb_substr($e->getMessage(), 0, 240)];
                Log::warning('ai.credits_check_failed', ['provider' => $provider, 'error' => $res['error']]);
            }

            $this->store($provider, $res);
            $this->line(sprintf('%-10s %s', $provider, isset($res['error'])
                ? 'AVISO: ' . $res['error']
                : strtoupper($res['source']) . ' saldo=' . number_format($res['balance'], 2) . ' ' . $res['currency']));
        }

        return self::SUCCESS;
    }

    private function store(string $provider, array $res): void
    {
        $base = ['last_checked_at' => now(), 'updated_at' => now()];

        if (isset($res['error'])) {
            // Si hay un aviso reciente de "sin crédito", no lo pises con otro mensaje de error.
            $cur = DB::table('ai_provider_credits')->where('provider', $provider)->first();
            $keep = $cur && $cur->billing_error_at && Carbon::parse($cur->billing_error_at)->gt(now()->subDay());
            DB::table('ai_provider_credits')->where('provider', $provider)->update($base + ($keep ? [] : ['last_error' => $res['error']]));

            return;
        }

        $cur = DB::table('ai_provider_credits')->where('provider', $provider)->first();
        $recovered = $res['source'] === 'api' && $res['available'];
        $keepErr = !$recovered && $cur && $cur->billing_error_at && Carbon::parse($cur->billing_error_at)->gt(now()->subDay());

        DB::table('ai_provider_credits')->where('provider', $provider)->update($base + [
            'last_balance' => $res['balance'],
            'last_currency' => $res['currency'],
            'last_available' => $res['available'] ? 1 : 0,
            'last_source' => $res['source'],
            'spent_since' => $res['spent'],
            'last_error' => $keepErr ? $cur->last_error : ($res['note'] ?? null),
        ] + ($recovered ? ['billing_error_at' => null] : []));
    }

    private function row(string $provider): ?object
    {
        return DB::table('ai_provider_credits')->where('provider', $provider)->first();
    }

    private function deepseek(): array
    {
        $key = config('services.deepseek.key');
        if (!$key) {
            return ['error' => 'DEEPSEEK_API_KEY no configurada'];
        }

        $r = Http::withToken($key)->acceptJson()->timeout(15)->get('https://api.deepseek.com/user/balance');
        if ($r->failed()) {
            return ['error' => 'DeepSeek respondió HTTP ' . $r->status()];
        }

        $infos = $r->json('balance_infos') ?? [];
        $info = collect($infos)->firstWhere('currency', 'USD') ?? ($infos[0] ?? null);
        if (!$info) {
            return ['error' => 'DeepSeek respondió sin balance_infos'];
        }

        return [
            'balance' => (float) $info['total_balance'],
            'currency' => (string) $info['currency'],
            'available' => (bool) $r->json('is_available'),
            'source' => 'api',
            'spent' => null,
        ];
    }

    private function twilio(): array
    {
        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        if (!$sid || !$token) {
            return ['error' => 'Credenciales de Twilio no configuradas'];
        }

        $r = Http::withBasicAuth($sid, $token)->acceptJson()->timeout(15)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Balance.json");
        if ($r->failed()) {
            return ['error' => 'Twilio respondió HTTP ' . $r->status()];
        }
        if ($r->json('balance') === null) {
            return ['error' => 'Twilio respondió sin balance'];
        }

        $bal = (float) $r->json('balance');

        return ['balance' => $bal, 'currency' => strtoupper(substr((string) ($r->json('currency') ?: 'USD'), 0, 3)), 'available' => $bal > 0, 'source' => 'api', 'spent' => null];
    }

    /** Deepgram: la llave no trae el ID de proyecto, así que se pide primero la lista de proyectos. */
    private function deepgram(): array
    {
        $key = config('ai.transcription.deepgram.api_key');
        if (!$key) {
            return ['error' => 'DEEPGRAM_API_KEY no configurada'];
        }
        $h = ['Authorization' => 'Token ' . $key];

        $p = Http::withHeaders($h)->acceptJson()->timeout(15)->get('https://api.deepgram.com/v1/projects');
        if ($this->option('debug')) {
            $this->line('DEBUG deepgram proyectos: ' . mb_substr($p->body(), 0, 800));
        }
        if ($p->failed()) {
            return ['error' => 'Deepgram proyectos HTTP ' . $p->status() . ($p->status() === 403 ? ' (la llave no tiene permiso para ver proyectos y saldos)' : '')];
        }
        $first = ($p->json('projects') ?? [])[0] ?? null;
        $id = $first['project_id'] ?? $first['id'] ?? null;
        if (!$id) {
            return ['error' => 'Deepgram no devolvió proyectos'];
        }

        $b = Http::withHeaders($h)->acceptJson()->timeout(15)->get("https://api.deepgram.com/v1/projects/{$id}/balances");
        if ($this->option('debug')) {
            $this->line('DEBUG deepgram saldos: ' . mb_substr($b->body(), 0, 800));
        }
        if ($b->failed()) {
            return ['error' => 'Deepgram saldos HTTP ' . $b->status()];
        }
        $rows = collect($b->json('balances') ?? [])->filter(fn ($x) => strtolower((string) ($x['units'] ?? '')) === 'usd');
        if ($rows->isEmpty()) {
            return ['error' => 'Deepgram no reporta un saldo en USD (¿plan de pago por uso o crédito en otras unidades?)'];
        }
        $bal = (float) $rows->sum('amount');

        return ['balance' => $bal, 'currency' => 'USD', 'available' => $bal > 0, 'source' => 'api', 'spent' => null];
    }

    /** Saldo estimado = saldo registrado − gasto de la API de costos desde ese día (UTC, conservador). */
    private function anthropic(): array
    {
        $row = $this->row('anthropic');
        $admin = config('ai_credits.anthropic_admin_key');
        if (!$row || $row->baseline_usd === null) {
            return ['error' => 'Registra el saldo actual: php artisan ai:credits anthropic <monto en USD>'];
        }
        if (!$admin) {
            return ['error' => 'Falta ANTHROPIC_ADMIN_KEY: el saldo no se descuenta solo, es el registrado a mano'];
        }

        $start = Carbon::parse($row->baseline_at)->utc()->startOfDay();
        $spent = 0.0;
        $page = null;
        for ($i = 0; $i < 20; $i++) {
            $q = ['starting_at' => $start->format('Y-m-d\TH:i:s\Z')] + ($page ? ['page' => $page] : []);
            $r = Http::withHeaders(['x-api-key' => $admin, 'anthropic-version' => '2023-06-01', 'User-Agent' => 'OpenDoctor/1.0'])
                ->acceptJson()->timeout(30)->get('https://api.anthropic.com/v1/organizations/cost_report', $q);
            if ($r->failed()) {
                return ['error' => 'Anthropic costos HTTP ' . $r->status()];
            }
            if ($this->option('debug') && $i === 0) {
                $this->line('DEBUG anthropic: ' . mb_substr($r->body(), 0, 1500));
            }
            foreach ($r->json('data') ?? [] as $bucket) {
                foreach ($bucket['results'] ?? [] as $res) {
                    $a = $res['amount'] ?? 0;
                    $a = is_array($a) ? ($a['value'] ?? 0) : $a;
                    $spent += (float) $a / 100; // la API reporta centavos de USD
                }
            }
            $page = $r->json('has_more') ? $r->json('next_page') : null;
            if (!$page) {
                break;
            }
        }

        return $this->estimate($row, $spent);
    }

    private function openai(): array
    {
        $row = $this->row('openai');
        $admin = config('ai_credits.openai_admin_key');
        if (!$row || $row->baseline_usd === null) {
            return ['error' => 'Registra el saldo actual: php artisan ai:credits openai <monto en USD>'];
        }
        if (!$admin) {
            return ['error' => 'Falta OPENAI_ADMIN_KEY: el saldo no se descuenta solo, es el registrado a mano'];
        }

        $start = Carbon::parse($row->baseline_at)->utc()->startOfDay();
        $spent = 0.0;
        $page = null;
        for ($i = 0; $i < 20; $i++) {
            $q = ['start_time' => $start->timestamp, 'bucket_width' => '1d', 'limit' => 180] + ($page ? ['page' => $page] : []);
            $r = Http::withToken($admin)->acceptJson()->timeout(30)->get('https://api.openai.com/v1/organization/costs', $q);
            if ($r->failed()) {
                return ['error' => 'OpenAI costos HTTP ' . $r->status()];
            }
            if ($this->option('debug') && $i === 0) {
                $this->line('DEBUG openai: ' . mb_substr($r->body(), 0, 1500));
            }
            foreach ($r->json('data') ?? [] as $bucket) {
                foreach ($bucket['results'] ?? [] as $res) {
                    $spent += (float) ($res['amount']['value'] ?? 0); // ya viene en USD
                }
            }
            $page = $r->json('has_more') ? $r->json('next_page') : null;
            if (!$page) {
                break;
            }
        }

        return $this->estimate($row, $spent);
    }

    private function estimate(object $row, float $spent): array
    {
        $balance = round((float) $row->baseline_usd - $spent, 4);

        return ['balance' => $balance, 'currency' => 'USD', 'available' => $balance > 0, 'source' => 'estimate', 'spent' => round($spent, 4)];
    }
}
