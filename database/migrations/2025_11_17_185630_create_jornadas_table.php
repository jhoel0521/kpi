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
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');

            $table->string('nombre')->comment('Día, Noche, Madrugada');
            $table->timestamp('ts_inicio')->comment('Tiempo Programado Inicio');
            $table->timestamp('ts_fin')->nullable()->comment('Tiempo Programado Fin');

            $table->foreignId('operador_id_inicio')->constrained('users')->onDelete('restrict');
            $table->foreignId('operador_id_actual')->nullable()->constrained('users')->onDelete('set null');

            $table->bigInteger('cantidad_producida_esperada')->nullable()->comment('Meta de la jornada');
            $table->string('estado')->default('activa')->comment('FSM: activa, completada, cancelada');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jornadas');
    }
};
