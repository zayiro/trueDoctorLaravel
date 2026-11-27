<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificates')) {
            return;
        }

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Código público de verificación (OD-XXXX-XXXX-XXXX). No es secuencial.
            $table->string('code', 24)->unique();

            $table->string('type', 20);                       // attendance | medical
            $table->string('status', 12)->default('issued');  // issued | revoked

            $table->unsignedBigInteger('appointment_id')->index();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->unsignedBigInteger('patient_history_id')->nullable();
            $table->unsignedBigInteger('signed_document_id')->nullable()->index();

            $table->timestamp('consultation_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->string('issued_by_ip', 45)->nullable();
            $table->timestamp('doctor_declaration_at')->nullable();

            // Contenido sensible: se guarda cifrado (cast "encrypted" en el modelo).
            $table->longText('body')->nullable();
            $table->longText('diagnosis_text')->nullable();
            $table->string('cie10_code', 20)->nullable();
            $table->boolean('diagnosis_authorized')->default(false);
            $table->timestamp('diagnosis_authorized_at')->nullable();

            // Datos con los que se emitió (nombre, tarjeta profesional, etc.), cifrados.
            // Así el certificado no cambia si luego el doctor o el paciente editan su perfil.
            $table->longText('snapshot')->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 255)->nullable();
            $table->unsignedBigInteger('revoked_by_user_id')->nullable();

            $table->timestamp('emailed_at')->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'type', 'status'], 'certificates_appt_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
