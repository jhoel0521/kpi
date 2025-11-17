<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidenciaParadaRequest;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $incidencia = $puestaEnMarcha->incidenciasParada()->create([
            'maquina_id' => $puestaEnMarcha->maquina_id,
            'ts_inicio_parada' => now(),
            'motivo' => $datosValidados['motivo'],
            'notas' => $datosValidados['notas'] ?? null,
            'creado_por' => Auth::id(),
        ]);

        // Opcional: Cambiar estado de la puesta en marcha a 'parada'
        // $puestaEnMarcha->update(['estado' => 'parada']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parada no planificada registrada exitosamente.',
                'data' => $incidencia,
            ]);
        }

        return redirect()->route('jornadas.show', $puestaEnMarcha->jornada_id)
            ->with('success', 'Parada no planificada registrada exitosamente.');
    }

    /**
     * Actualiza (finaliza) una parada no planificada.
     * Tarea T2.4: update (finalizar parada)
     */
    public function update(Request $request, IncidenciaParada $incidenciaParada)
    {
        Log::info('Finalizando parada ID: ' . $incidenciaParada->id . ', ts_fin_parada actual: ' . $incidenciaParada->ts_fin_parada);

        // Validación básica
        $request->validate([
            'notas_finalizacion' => 'nullable|string|max:1000',
        ]);

        // Lógica FSM: Solo se pueden finalizar paradas que no estén finalizadas
        if ($incidenciaParada->ts_fin_parada !== null) {
            Log::warning('Intento de finalizar parada ya finalizada ID: ' . $incidenciaParada->id);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta parada ya está finalizada.',
                ], 422);
            }

            return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
                ->with('error', 'Esta parada ya está finalizada.');
        }

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
            'notas' => ($incidenciaParada->notas ?? '') . ($request->notas_finalizacion ? "\n\nFinalización: " . $request->notas_finalizacion : ''),
        ]);

        Log::info('Parada ID: ' . $incidenciaParada->id . ' finalizada exitosamente, duración: ' . $duracionSegundos . ' segundos');

        // Opcional: Revertir estado de la puesta en marcha a 'en_marcha'
        // $incidenciaParada->puestaEnMarcha->update(['estado' => 'en_marcha']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parada finalizada exitosamente.',
                'data' => $incidenciaParada,
            ]);
        }

        return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
            ->with('success', 'Parada finalizada exitosamente.');
    }
}
