<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PuestaEnMarcha extends Model
{
    use HasFactory;

    // Nombre de la tabla si no sigue la convención
    protected $table = 'puesta_en_marchas';

    protected $fillable = [
        'jornada_id',
        'maquina_id',
        'ts_inicio',
        'ts_fin',
        'estado',
        'cantidad_producida_esperada',
    ];

    protected $casts = [
        'ts_inicio' => 'datetime',
        'ts_fin' => 'datetime',
    ];

    /**
     * Relación con la jornada a la que pertenece.
     */
    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    /**
     * Relación con la máquina asociada.
     */
    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    /**
     * Relación con los detalles de producción (mediciones granulares).
     */
    public function detallesProduccion(): HasMany
    {
        return $this->hasMany(ProduccionDetalle::class);
    }

    /**
     * Relación con las paradas no planificadas ocurridas durante esta sesión.
     */
    public function incidenciasParada(): HasMany
    {
        return $this->hasMany(IncidenciaParada::class);
    }

    /**
     * Relación con el snapshot de resumen.
     */
    public function resumen(): HasOne
    {
        return $this->hasOne(ResumenProduccion::class);
    }
}
