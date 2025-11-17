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
        Schema::create('maquinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_produccion_id')->constrained('linea_produccions')->onDelete('cascade');
            $table->string('nombre');
            $table->string('serie')->unique()->nullable();
            $table->string('estado')->default('operativa')->comment('FSM: operativa, parada, mantenimiento, falla');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquinas');
    }
};
