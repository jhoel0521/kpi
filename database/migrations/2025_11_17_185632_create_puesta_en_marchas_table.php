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
        Schema::create('puesta_en_marchas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jornada_id')->constrained('jornadas')->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');

            $table->timestamp('ts_inicio')->comment('Inicio de UPTIME');
            $table->timestamp('ts_fin')->nullable()->comment('Fin de UPTIME');

            $table->string('estado')->default('en_marcha')->comment('FSM: en_marcha, parada, finalizada');
            $table->bigInteger('cantidad_producida_esperada')->nullable()->comment('Meta (para Rendimiento)');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puesta_en_marchas');
    }
};
