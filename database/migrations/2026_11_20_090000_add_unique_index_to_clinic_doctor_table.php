<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'clinic_doctor_clinic_id_doctor_id_unique';

    public function up(): void
    {
        // Si ya existe un índice único sobre (clinic_id, doctor_id), no hacemos nada.
        $unique = DB::select(
            "SELECT index_name AS idx, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols
             FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'clinic_doctor' AND non_unique = 0
             GROUP BY index_name"
        );

        foreach ($unique as $row) {
            if (in_array($row->cols, ['clinic_id,doctor_id', 'doctor_id,clinic_id'], true)) {
                return;
            }
        }

        // Nunca borramos datos automáticamente: si hay vínculos duplicados, se aborta con el detalle.
        $duplicates = DB::table('clinic_doctor')
            ->select('clinic_id', 'doctor_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('clinic_id', 'doctor_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $detail = $duplicates
                ->map(fn ($d) => "clínica {$d->clinic_id} / médico {$d->doctor_id} ({$d->total} filas)")
                ->implode('; ');

            throw new RuntimeException("clinic_doctor tiene vínculos duplicados, resuélvelos a mano antes de migrar: {$detail}");
        }

        Schema::table('clinic_doctor', function (Blueprint $table) {
            $table->unique(['clinic_id', 'doctor_id'], self::INDEX);
        });
    }

    public function down(): void
    {
        $exists = DB::selectOne(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'clinic_doctor' AND index_name = ?",
            [self::INDEX]
        );

        if ($exists) {
            Schema::table('clinic_doctor', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }
};
