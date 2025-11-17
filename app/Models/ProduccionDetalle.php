<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduccionDetalle extends Model
{
    use HasFactory;

    // Nombre de la tabla si no sigue la convención
    protected $table = 'produccion_detalles';

    protected $fillable = [
        'puesta_en_marcha_id',
        'maquina_id',
        'ts',
        'cantidad_producida',
        'cantidad_buena',
        'cantidad_fallada',
        'tasa_defectos',
        'payload_raw',
    ];

    protected $casts = [
        'ts' => 'datetime',
        'payload_raw' => 'array',
        'tasa_defectos' => 'decimal:2',
    ];

    /**
     * Relación con la puesta en marcha a la que pertenece este detalle.
     */
    public function puestaEnMarcha(): BelongsTo
    {
        return $this->belongsTo(PuestaEnMarcha::class);
    }
}