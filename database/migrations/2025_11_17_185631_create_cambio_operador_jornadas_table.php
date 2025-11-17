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
        Schema::create('cambio_operador_jornadas', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('jornada_id')->constrained('jornadas')->onDelete('cascade');
            
            $table->foreignId('operador_anterior_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('operador_nuevo_id')->constrained('users')->onDelete('restrict');
            
            $table->timestamp('ts_cambio')->useCurrent();
            $table->string('razon')->nullable()->comment('cambio de turno, relevo');
            
            $table->foreignId('creado_por')->nullable()->comment('supervisor')->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cambio_operador_jornadas');
    }
};