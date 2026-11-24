<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW_KEYS = [
        'ai_chat_wompi_percent' => '2.65',
        'ai_chat_wompi_fixed_fee' => '700',
        'ai_chat_wompi_iva_percent' => '19',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::NEW_KEYS as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'group' => 'ai_chat',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // El chat todavía no está completo: las ventas quedan pausadas hasta que el admin las active.
        DB::table('settings')->where('key', 'ai_chat_sales_paused')->update(['value' => '1', 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(self::NEW_KEYS))->where('group', 'ai_chat')->delete();
        DB::table('settings')->where('key', 'ai_chat_sales_paused')->update(['value' => '0', 'updated_at' => now()]);
    }
};
