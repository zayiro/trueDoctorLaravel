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
        Schema::create('api_client_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->onDelete('cascade');
            $table->string('endpoint'); // ej: /api/appointments/slots
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->unique(['api_client_id', 'endpoint']);
            $table->index('api_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_client_endpoints');
    }
};
