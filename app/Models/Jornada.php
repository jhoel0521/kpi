<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// <-- AÑADIR ESTE IMPORT

class Jornada extends Model
{
    use HasFactory;

    protected $fillable = [
        'maquina_id',
        'nombre',
        'ts_inicio',
        'ts_fin',
        'operador_id_inicio',
        'operador_id_actual',
        'cantidad_producida_esperada',
        'estado',
    ];

    protected $casts = [
        'ts_inicio' => 'datetime',
        'ts_fin' => 'datetime',
    ];

    /**
     * Relación con la máquina a la que pertenece la jornada.
     */
    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    /**
     * Relación con el usuario que inició la jornada.
     */
    public function operadorInicio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id_inicio');
    }

    /**
     * Relación con el usuario actualmente a cargo.
     */
    public function operadorActual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id_actual');
    }

    /**
     * Relación con las puestas en marcha que ocurrieron en esta jornada.
     */
    public function puestasEnMarcha(): HasMany
    {
        return $this->hasMany(PuestaEnMarcha::class);
    }

    /**
     * Relación con los cambios de operador ocurridos en esta jornada.
     */
    public function cambiosOperador(): HasMany
    {
        return $this->hasMany(CambioOperadorJornada::class);
    }
}
