<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumenProduccion extends Model
{
    use HasFactory;

    // Nombre de la tabla si no sigue la convención
    protected $table = 'resumen_produccions';

    protected $fillable = [
        'puesta_en_marcha_id',
        'maquina_id',
        'jornada_id',
        'cantidad_total_producida',
        'cantidad_total_buena',
        'cantidad_total_fallada',
        'cantidad_esperada',
        'total_paradas_no_planificadas_segundos',
        'tiempo_marcha_segundos',
        'oee_calculado',
        'disponibilidad_calculada',
        'rendimiento_calculado',
        'calidad_calculada',
    ];

    protected $casts = [
        'oee_calculado' => 'decimal:2',
        'disponibilidad_calculada' => 'decimal:2',
        'rendimiento_calculado' => 'decimal:2',
        'calidad_calculada' => 'decimal:2',
    ];

    /**
     * Relación con la puesta en marcha que generó este resumen.
     */
    public function puestaEnMarcha(): BelongsTo
    {
        return $this->belongsTo(PuestaEnMarcha::class);
    }

    /**
     * Relación con la jornada (para agrupación).
     */
    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    /**
     * Relación con la máquina (para agrupación).
     */
    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }
}
