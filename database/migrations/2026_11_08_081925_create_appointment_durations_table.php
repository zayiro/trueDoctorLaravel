<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_durations', function (Blueprint $table) {
            $table->id();
            $table->integer('minutes')->unique()->comment('Duración en minutos');
            $table->decimal('additional_price', 10, 2)->default(0.00)->comment('Precio extra/recargo por esta duración');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_durations');
    }
};
