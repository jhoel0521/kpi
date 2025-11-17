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
        Schema::table('jornadas', function (Blueprint $table) {
            $table->index(['maquina_id', 'ts_inicio']);
        });

        Schema::table('incidencia_paradas', function (Blueprint $table) {
            $table->index(['maquina_id', 'ts_inicio_parada']);
            // El FK 'puesta_en_marcha_id' ya suele estar indexado, pero podemos asegurarlo
            // $table->index('puesta_en_marcha_id');
        });

        Schema::table('produccion_detalles', function (Blueprint $table) {
            $table->index(['puesta_en_marcha_id', 'ts']);
            $table->index(['maquina_id', 'ts']);
        });

        Schema::table('resumen_produccions', function (Blueprint $table) {
            $table->index(['maquina_id', 'created_at']);
            // El FK 'jornada_id'
            // $table->index('jornada_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jornadas', function (Blueprint $table) {
            $table->dropIndex(['maquina_id', 'ts_inicio']);
        });

        Schema::table('incidencia_paradas', function (Blueprint $table) {
            $table->dropIndex(['maquina_id', 'ts_inicio_parada']);
        });

        Schema::table('produccion_detalles', function (Blueprint $table) {
            $table->dropIndex(['puesta_en_marcha_id', 'ts']);
            $table->dropIndex(['maquina_id', 'ts']);
        });

        Schema::table('resumen_produccions', function (Blueprint $table) {
            $table->dropIndex(['maquina_id', 'created_at']);
        });
    }
};
