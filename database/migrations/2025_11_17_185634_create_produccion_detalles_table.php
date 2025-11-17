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
        Schema::create('produccion_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('puesta_en_marcha_id')->constrained('puesta_en_marchas')->onDelete('cascade');
            $table->unsignedBigInteger('maquina_id');

            $table->timestamp('ts')->comment('timestamp del reporte');
            $table->bigInteger('cantidad_producida')->comment('Total (para Rendimiento)');
            $table->bigInteger('cantidad_buena')->comment('Buenas (para Calidad)');
            $table->bigInteger('cantidad_fallada')->comment('Malas (para Calidad)');

            $table->decimal('tasa_defectos', 8, 2)->nullable();
            $table->json('payload_raw')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produccion_detalles');
    }
};
