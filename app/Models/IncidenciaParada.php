<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// <-- AÑADIR ESTE IMPORT

class IncidenciaParada extends Model
{
    use HasFactory;

    // Nombre de la tabla si no sigue la convención
    protected $table = 'incidencia_paradas';

    protected $fillable = [
        'puesta_en_marcha_id',
        'maquina_id',
        'ts_inicio_parada',
        'ts_fin_parada',
        'duracion_segundos',
        'motivo',
        'notas',
        'creado_por',
    ];

    protected $casts = [
        'ts_inicio_parada' => 'datetime',
        'ts_fin_parada' => 'datetime',
    ];

    /**
     * Relación con la puesta en marcha donde ocurrió la parada.
     */
    public function puestaEnMarcha(): BelongsTo
    {
        return $this->belongsTo(PuestaEnMarcha::class);
    }

    /**
     * Relación con la máquina afectada.
     */
    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    /**
     * Relación con el usuario que reportó la parada.
     */
    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
