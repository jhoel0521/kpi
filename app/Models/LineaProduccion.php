<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineaProduccion extends Model
{
    use HasFactory;

    protected $table = 'linea_produccions';

    protected $fillable = ['planta_id', 'nombre', 'estado'];

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class);
    }

    public function maquinas(): HasMany
    {
        return $this->hasMany(Maquina::class);
    }
}
