<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CERT_TYPES = ['certificate_attendance', 'certificate_medical'];

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function up(): void
    {
        if (!$this->isMysql() || !Schema::hasTable('signed_documents')) {
            return;
        }

        DB::statement("ALTER TABLE `signed_documents` MODIFY `type` ENUM('prescription','clinical_record','incapacity','referral','certificate_attendance','certificate_medical') NOT NULL");
    }

    public function down(): void
    {
        if (!$this->isMysql() || !Schema::hasTable('signed_documents')) {
            return;
        }

        // Si ya hay certificados firmados, no se reduce el enum (no se borra nada).
        if (DB::table('signed_documents')->whereIn('type', self::CERT_TYPES)->exists()) {
            return;
        }

        DB::statement("ALTER TABLE `signed_documents` MODIFY `type` ENUM('prescription','clinical_record','incapacity','referral') NOT NULL");
    }
};
