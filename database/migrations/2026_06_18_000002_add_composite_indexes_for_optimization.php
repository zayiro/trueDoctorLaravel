<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('appointments', function (Blueprint $table) {
                // Composite index for common WHERE clauses: doctor_id, date range, status
                $table->index(['doctor_id', 'date', 'status'], 'idx_appointments_doctor_date_status');
                
                // Composite index for clinic-based queries
                $table->index(['clinic_id', 'date', 'status'], 'idx_appointments_clinic_date_status');
                
                // Composite index for patient appointments with date filtering
                $table->index(['patient_id', 'date', 'status'], 'idx_appointments_patient_date_status');
                
                // Composite index for address-based availability queries
                $table->index(['address_id', 'doctor_id', 'date'], 'idx_appointments_address_doctor_date');
                
                // Composite index for service-based queries
                $table->index(['service_id', 'date', 'status'], 'idx_appointments_service_date_status');
                
                // Composite index for virtual appointment filtering
                $table->index(['is_virtual', 'date', 'status'], 'idx_appointments_virtual_date_status');
            });

            Schema::table('addresses', function (Blueprint $table) {
                // Composite index for clinic address queries
                $table->index(['clinic_id', 'city_id'], 'idx_addresses_clinic_city');
                
                // Composite index for doctor address queries
                $table->index(['doctor_id', 'city_id'], 'idx_addresses_doctor_city');
                
                // Composite index for service availability in addresses
                $table->index(['clinic_id', 'is_virtual'], 'idx_addresses_clinic_virtual');
                
                // Composite index for geographic-based searches
                $table->index(['city_id', 'clinic_id'], 'idx_addresses_city_clinic');
            });
        } catch (\Exception $e) {
            Log::warning('Error al aplicar índices compuestos: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_doctor_date_status');
            $table->dropIndex('idx_appointments_clinic_date_status');
            $table->dropIndex('idx_appointments_patient_date_status');
            $table->dropIndex('idx_appointments_address_doctor_date');
            $table->dropIndex('idx_appointments_service_date_status');
            $table->dropIndex('idx_appointments_virtual_date_status');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('idx_addresses_clinic_city');
            $table->dropIndex('idx_addresses_doctor_city');
            $table->dropIndex('idx_addresses_clinic_virtual');
            $table->dropIndex('idx_addresses_city_clinic');
        });
    }
};
