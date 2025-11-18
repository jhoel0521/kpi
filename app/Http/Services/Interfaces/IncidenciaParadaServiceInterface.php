<?php

namespace App\Http\Services\Interfaces;

use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;

interface IncidenciaParadaServiceInterface
{
    /**
     * Registra una nueva parada no planificada.
     */
    public function registrarParada(PuestaEnMarcha $puestaEnMarcha, string $motivo, string $notas): IncidenciaParada;

    /**
     * Finaliza una parada no planificada.
     */
    public function finalizarParada(IncidenciaParada $incidenciaParada, string $notasFinalizacion): IncidenciaParada;
}
