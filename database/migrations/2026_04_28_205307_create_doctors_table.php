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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');            
            $table->string('medical_license')->nullable();
            $table->string('identification')->unique();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->char('country_code', 2)->nullable()->index('idx_country_code');
            $table->string('experience_years')->nullable();            
            $table->json('languages')->nullable()->default('["es"]');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);            
            $table->string('validation_status')->default('missing')->comment('Estados: missing, pending_validation, approved, rejected');
            $table->string('identity_card_path')->nullable();
            $table->string('professional_card_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
