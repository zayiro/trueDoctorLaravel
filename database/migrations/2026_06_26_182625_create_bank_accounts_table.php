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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable'); // accountable_id + accountable_type (User o Clinic)
            $table->string('bank_name');
            $table->string('account_number');
            $table->enum('account_type', ['savings', 'checking']); // ahorros / corriente
            $table->string('account_holder_name');
            $table->string('account_holder_id');  // cédula o NIT
            $table->enum('id_type', ['CC', 'NIT', 'CE'])->default('CC');
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
