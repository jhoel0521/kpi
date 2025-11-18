<?php

namespace App\Http\Services\Interfaces;

use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;

interface IncidenciaParadaServiceInterface
{
    /**
     * Registra una nueva parada no planificada.
     *
     * @param PuestaEnMarcha $puestaEnMarcha
     * @param string $motivo
     * @param string $notas
     * @return IncidenciaParada
     */
    public function registrarParada(PuestaEnMarcha $puestaEnMarcha, string $motivo, string $notas): IncidenciaParada;

    /**
     * Finaliza una parada no planificada.
     *
     * @param IncidenciaParada $incidenciaParada
     * @param string $notasFinalizacion
     * @return IncidenciaParada
     */
    public function finalizarParada(IncidenciaParada $incidenciaParada, string $notasFinalizacion): IncidenciaParada;
}