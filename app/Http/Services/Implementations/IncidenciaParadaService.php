<?php

namespace App\Http\Services\Implementations;

use App\Http\Services\Interfaces\IncidenciaParadaServiceInterface;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncidenciaParadaService implements IncidenciaParadaServiceInterface
{
    /**
     * Registra una nueva parada no planificada.
     *
     * @param PuestaEnMarcha $puestaEnMarcha
     * @param string $motivo
     * @param string $notas
     * @return IncidenciaParada
     */
    public function registrarParada(PuestaEnMarcha $puestaEnMarcha, string $motivo, string $notas): IncidenciaParada
    {
        return DB::transaction(function () use ($puestaEnMarcha, $motivo, $notas) {
            Log::info('Registrando parada para puesta en marcha ID: ' . $puestaEnMarcha->id);

            $incidencia = $puestaEnMarcha->incidenciasParada()->create([
                'maquina_id' => $puestaEnMarcha->maquina_id,
                'ts_inicio_parada' => now(),
                'motivo' => $motivo,
                'notas' => $notas,
                'creado_por' => Auth::id(),
            ]);

            Log::info('Parada registrada ID: ' . $incidencia->id);

            return $incidencia;
        });
    }

    /**
     * Finaliza una parada no planificada.
     *
     * @param IncidenciaParada $incidenciaParada
     * @param string $notasFinalizacion
     * @return IncidenciaParada
     */
    public function finalizarParada(IncidenciaParada $incidenciaParada, string $notasFinalizacion): IncidenciaParada
    {
        return DB::transaction(function () use ($incidenciaParada, $notasFinalizacion) {
            Log::info('Finalizando parada ID: ' . $incidenciaParada->id . ', ts_fin_parada actual: ' . $incidenciaParada->ts_fin_parada);

            // Verificar que tenga timestamp de inicio, si no, setearlo a now()
            if (!$incidenciaParada->ts_inicio_parada) {
                Log::warning('Parada ID: ' . $incidenciaParada->id . ' sin ts_inicio_parada, seteando a now()');
                $incidenciaParada->ts_inicio_parada = now();
            }

            $tsFin = now();
            $duracionSegundos = $incidenciaParada->ts_inicio_parada->diffInSeconds($tsFin);

            // Finalizar la parada
            $incidenciaParada->update([
                'ts_fin_parada' => $tsFin,
                'duracion_segundos' => $duracionSegundos,
                'notas' => ($incidenciaParada->notas ?? '') . ($notasFinalizacion ? "\n\nFinalización: " . $notasFinalizacion : ''),
            ]);

            Log::info('Parada ID: ' . $incidenciaParada->id . ' finalizada exitosamente, duración: ' . $duracionSegundos . ' segundos');

            return $incidenciaParada;
        });
    }
}