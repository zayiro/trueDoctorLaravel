<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clinic_doctor', 'relationship')) {
            Schema::table('clinic_doctor', function (Blueprint $table) {
                // own = creado por la clínica | invited = médico existente que aceptó invitación
                $table->string('relationship', 20)->default('invited')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinic_doctor', 'relationship')) {
            Schema::table('clinic_doctor', function (Blueprint $table) {
                $table->dropColumn('relationship');
            });
        }
    }
};
