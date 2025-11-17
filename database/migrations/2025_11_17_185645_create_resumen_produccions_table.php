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
        Schema::create('resumen_produccions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('puesta_en_marcha_id')->unique()->constrained('puesta_en_marchas')->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
            $table->foreignId('jornada_id')->constrained('jornadas')->onDelete('cascade');

            // Agregados de produccion_detalle
            $table->bigInteger('cantidad_total_producida');
            $table->bigInteger('cantidad_total_buena');
            $table->bigInteger('cantidad_total_fallada');
            $table->bigInteger('cantidad_esperada')->nullable();

            // Agregados de incidencia_parada (v3.0)
            $table->bigInteger('total_paradas_no_planificadas_segundos')->default(0);

            // Agregados de puesta_en_marcha
            $table->bigInteger('tiempo_marcha_segundos')->comment('ts_fin - ts_inicio');

            // KPIs calculados (v3.0)
            $table->decimal('oee_calculado', 8, 2)->nullable();
            $table->decimal('disponibilidad_calculada', 8, 2)->nullable();
            $table->decimal('rendimiento_calculado', 8, 2)->nullable();
            $table->decimal('calidad_calculada', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumen_produccions');
    }
};