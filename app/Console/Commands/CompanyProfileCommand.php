<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyProfileCommand extends Command
{
    protected $signature = 'payouts:company {--name=} {--nit=} {--address=} {--city=} {--email=}';

    protected $description = 'Muestra o guarda los datos de empresa que salen en el encabezado de los soportes de liquidación.';

    public function handle(): int
    {
        $map = ['name' => 'company_legal_name', 'nit' => 'company_nit', 'address' => 'company_address', 'city' => 'company_city', 'email' => 'company_email'];
        $stamps = Schema::hasColumn('settings', 'updated_at') ? ['updated_at' => now()] : [];

        foreach ($map as $opt => $key) {
            $v = $this->option($opt);
            if ($v !== null && trim($v) !== '') {
                $exists = DB::table('settings')->where('key', $key)->exists();
                $row = ['value' => trim($v)] + $stamps;
                if (!$exists && Schema::hasColumn('settings', 'created_at')) { $row['created_at'] = now(); }
                DB::table('settings')->updateOrInsert(['key' => $key], $row);
                $this->info("Guardado $key");
            }
        }

        foreach ($map as $opt => $key) {
            $this->line(str_pad($key, 20) . ': ' . (DB::table('settings')->where('key', $key)->value('value') ?: '(vacío)'));
        }

        return self::SUCCESS;
    }
}
