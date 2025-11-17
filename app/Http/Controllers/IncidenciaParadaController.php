<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidenciaParadaRequest;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidenciaParadaController extends Controller
{
    /**
     * Almacena (registra) una nueva parada no planificada.
     * Tarea T2.4: store (registrar parada no planificada)
     */
    public function store(StoreIncidenciaParadaRequest $request, PuestaEnMarcha $puestaEnMarcha)
    {
        $datosValidados = $request->validated();

        // Crear la incidencia de parada
        $puestaEnMarcha->incidenciasParada()->create([
            'maquina_id' => $puestaEnMarcha->maquina_id,
            'ts_inicio_parada' => now(),
            'motivo' => $datosValidados['motivo'],
            'notas' => $datosValidados['notas'] ?? null,
            'creado_por' => Auth::id(),
        ]);

        // Opcional: Cambiar estado de la puesta en marcha a 'parada'
        // $puestaEnMarcha->update(['estado' => 'parada']);

        return redirect()->route('jornadas.show', $puestaEnMarcha->jornada_id)
            ->with('success', 'Parada no planificada registrada exitosamente.');
    }

    /**
     * Actualiza (finaliza) una parada no planificada.
     * Tarea T2.4: update (finalizar parada)
     */
    public function update(Request $request, IncidenciaParada $incidenciaParada)
    {
        // Validación básica
        $request->validate([
            'notas_finalizacion' => 'nullable|string|max:1000',
        ]);

        // Lógica FSM: Solo se pueden finalizar paradas que no estén finalizadas
        if ($incidenciaParada->ts_fin_parada !== null) {
            return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
                ->with('error', 'Esta parada ya está finalizada.');
        }

        $tsFin = now();
        $duracionSegundos = $incidenciaParada->ts_inicio_parada->diffInSeconds($tsFin);

        // Finalizar la parada
        $incidenciaParada->update([
            'ts_fin_parada' => $tsFin,
            'duracion_segundos' => $duracionSegundos,
            'notas' => $incidenciaParada->notas.($request->notas_finalizacion ? "\n\nFinalización: ".$request->notas_finalizacion : ''),
        ]);

        // Opcional: Revertir estado de la puesta en marcha a 'en_marcha'
        // $incidenciaParada->puestaEnMarcha->update(['estado' => 'en_marcha']);

        return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
            ->with('success', 'Parada finalizada exitosamente.');
    }
}
