<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// <-- AÑADIR ESTE IMPORT

class CambioOperadorJornada extends Model
{
    use HasFactory;

    protected $fillable = [
        'jornada_id',
        'operador_anterior_id',
        'operador_nuevo_id',
        'ts_cambio',
        'razon',
        'creado_por',
    ];

    protected $casts = [
        'ts_cambio' => 'datetime',
    ];

    /**
     * Relación con la jornada a la que pertenece el cambio.
     */
    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    /**
     * Relación con el operador anterior.
     */
    public function operadorAnterior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_anterior_id');
    }

    /**
     * Relación con el nuevo operador.
     */
    public function operadorNuevo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_nuevo_id');
    }

    /**
     * Relación con el supervisor que registró el cambio.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
