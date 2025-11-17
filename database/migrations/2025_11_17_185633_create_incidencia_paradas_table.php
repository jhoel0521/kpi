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
        Schema::create('incidencia_paradas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('puesta_en_marcha_id')->constrained('puesta_en_marchas')->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');

            $table->timestamp('ts_inicio_parada');
            $table->timestamp('ts_fin_parada')->nullable();
            $table->bigInteger('duracion_segundos')->nullable();

            $table->string('motivo')->comment('Falla eléctrica, Falta material, Atasco');
            $table->text('notas')->nullable();

            $table->foreignId('creado_por')->nullable()->comment('operador/supervisor')->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencia_paradas');
    }
};
