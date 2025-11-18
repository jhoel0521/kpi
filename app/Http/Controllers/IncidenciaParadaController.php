<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidenciaParadaRequest;
use App\Http\Services\Interfaces\IncidenciaParadaServiceInterface;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Http\Request;

class IncidenciaParadaController extends Controller
{
    protected $incidenciaParadaService;

    public function __construct(IncidenciaParadaServiceInterface $incidenciaParadaService)
    {
        $this->incidenciaParadaService = $incidenciaParadaService;
    }
    /**
     * Almacena (registra) una nueva parada no planificada.
     * Tarea T2.4: store (registrar parada no planificada)
     */
    public function store(StoreIncidenciaParadaRequest $request, PuestaEnMarcha $puestaEnMarcha)
    {
        $datosValidados = $request->validated();

        $incidencia = $this->incidenciaParadaService->registrarParada(
            $puestaEnMarcha,
            $datosValidados['motivo'],
            $datosValidados['notas'] ?? ''
        );

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
        // Validación básica
        $request->validate([
            'notas_finalizacion' => 'nullable|string|max:1000',
        ]);

        // Lógica FSM: Solo se pueden finalizar paradas que no estén finalizadas
        if ($incidenciaParada->ts_fin_parada !== null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta parada ya está finalizada.',
                ], 422);
            }

            return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
                ->with('error', 'Esta parada ya está finalizada.');
        }

        $notasFinalizacion = $request->input('notas_finalizacion', '');

        $incidencia = $this->incidenciaParadaService->finalizarParada($incidenciaParada, $notasFinalizacion);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parada finalizada exitosamente.',
                'data' => $incidencia,
            ]);
        }

        return redirect()->route('jornadas.show', $incidenciaParada->puestaEnMarcha->jornada_id)
            ->with('success', 'Parada finalizada exitosamente.');
    }
}
