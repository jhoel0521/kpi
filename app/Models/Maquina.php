<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maquina extends Model
{
    use HasFactory;

    protected $table = 'maquinas';
    protected $fillable = ['linea_produccion_id', 'nombre', 'serie', 'estado'];

    public function lineaProduccion(): BelongsTo
    {
        return $this->belongsTo(LineaProduccion::class);
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(Jornada::class);
    }

    public function puestasEnMarcha(): HasMany
    {
        return $this->hasMany(PuestaEnMarcha::class);
    }
    public function incidenciasParada(): HasMany
    {
        return $this->hasMany(IncidenciaParada::class);
    }
}
