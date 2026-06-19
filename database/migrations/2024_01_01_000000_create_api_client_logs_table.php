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
        Schema::dropIfExists('api_client_logs');
        
        Schema::create('api_client_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->onDelete('cascade');
            $table->string('endpoint');
            $table->string('method');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->integer('status_code')->nullable();
            $table->timestamps();

            $table->index('api_client_id');
            $table->index('created_at');
            $table->index(['api_client_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_client_logs');
    }
};
