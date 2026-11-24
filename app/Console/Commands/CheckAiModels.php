<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Services\AiModels\AiModelMonitor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckAiModels extends Command
{
    protected $signature = 'ai-models:check {--force : Ignora el intervalo y comprueba ahora}';

    protected $description = 'Pregunta a Anthropic y OpenAI si los modelos de IA en uso siguen existiendo (cada 15 días) y avisa por correo si alguno dejó de responder';

    public function handle(AiModelMonitor $monitor): int
    {
        $previous = $monitor->liveCheck();
        $every = max(1, (int) config('ai_models.check_every_days', 15));

        // El programador corre a diario; aquí se decide si ya toca (cada 15 días).
        if (! $this->option('force') && $previous['checked_at']
            && Carbon::parse($previous['checked_at'])->gt(now()->subDays($every)->addHours(2))) {
            return self::SUCCESS;
        }

        $results = [];
        foreach (array_keys($monitor->usage()) as $id) {
            $state = match (true) {
                str_starts_with($id, 'claude-') => $this->probe('https://api.anthropic.com/v1/models/'.$id, [
                    'x-api-key' => (string) config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                ], (string) config('services.anthropic.key')),
                str_starts_with($id, 'gpt-') => $this->probe('https://api.openai.com/v1/models/'.$id, [
                    'Authorization' => 'Bearer '.config('services.openai.key'),
                ], (string) config('services.openai.key')),
                default => null, // otros proveedores: no se comprueban
            };

            if ($state !== null) {
                $results[$id] = $state;
                $this->line(sprintf('  %-22s %s', $id, $state));
            }
        }

        $missing = array_keys(array_filter($results, fn ($s) => $s === 'missing'));

        Setting::updateOrCreate(
            ['key' => AiModelMonitor::LIVE_KEY],
            ['value' => json_encode(['checked_at' => now()->toIso8601String(), 'missing' => $missing, 'results' => $results]), 'group' => 'ai_models']
        );

        $new = array_diff($missing, $previous['missing']);
        if ($new !== []) {
            $this->notifyAdmin($new);
        }

        $this->info($missing === [] ? 'Todos los modelos en uso responden.' : 'Modelos que ya no responden: '.implode(', ', $missing));

        return self::SUCCESS;
    }

    /** ok = existe; missing = la API dice que no existe (404); unknown = no se pudo saber (red, key, etc.). */
    private function probe(string $url, array $headers, string $key): string
    {
        if ($key === '') {
            return 'unknown';
        }

        try {
            $res = Http::withHeaders($headers)->timeout(20)->get($url);
        } catch (Throwable $e) {
            return 'unknown';
        }

        return match (true) {
            $res->successful() => 'ok',
            $res->status() === 404 => 'missing',
            default => 'unknown',
        };
    }

    private function notifyAdmin(array $models): void
    {
        try {
            $to = Setting::where('key', 'support_email')->value('value')
                ?: User::where('role', 'admin')->value('email');

            if (! $to) {
                return;
            }

            Mail::raw(
                "OpenDoctor — aviso de modelos de IA\n\nEstos modelos que usa la plataforma ya no responden: ".implode(', ', $models)
                ."\n\nCámbialos desde Admin → Configuraciones (y Admin → Chat de Salud IA) o en el .env, y reinicia los workers de colas."
                ."\nPágina de deprecaciones: ".config('ai_models.source_url'),
                fn ($m) => $m->to($to)->subject('OpenDoctor: modelo de IA que ya no responde')
            );
        } catch (Throwable $e) {
            Log::warning('ai_models.notify_failed', ['message' => $e->getMessage()]);
        }
    }
}
