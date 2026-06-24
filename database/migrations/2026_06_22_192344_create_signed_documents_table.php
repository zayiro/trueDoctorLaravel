<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // php artisan make:migration create_signed_documents_table
        Schema::create('signed_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable');               // prescription_id, clinical_record_id, etc.
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->enum('type', ['prescription', 'clinical_record', 'incapacity', 'referral']);
            $table->string('document_hash');          // SHA-256 del contenido original
            $table->string('signature_hash');         // SHA-256(document_hash + doctor_id + timestamp + secret)
            $table->string('storage_path');           // PDF en S3
            $table->timestamp('signed_at');
            $table->string('signed_by_ip')->nullable();
            $table->enum('status', ['signed', 'revoked'])->default('signed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signed_documents');
    }
};
