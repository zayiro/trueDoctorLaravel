<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Crea las llaves del módulo solo si no existen (no pisa nada). */
    public function up(): void
    {
        $now = now();

        foreach (config('ai_chat.defaults') as $key => $value) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => (string) $value,
                'group' => 'ai_chat',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_keys(config('ai_chat.defaults')))
            ->where('group', 'ai_chat')
            ->delete();
    }
};
