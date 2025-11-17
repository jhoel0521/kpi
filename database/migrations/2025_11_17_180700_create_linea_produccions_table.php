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
        Schema::create('linea_produccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planta_id')->constrained('plantas')->onDelete('cascade');
            $table->string('nombre');
            $table->string('estado')->default('activa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linea_produccions');
    }
};
