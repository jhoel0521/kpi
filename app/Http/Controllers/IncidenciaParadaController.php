<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidenciaParadaRequest;
use App\Http\Services\Interfaces\IncidenciaParadaServiceInterface;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IncidenciaParadaController extends Controller
{
    protected $incidenciaParadaService;

    public function __construct(IncidenciaParadaServiceInterface $incidenciaParadaService)
    {
        $this->incidenciaParadaService = $incidenciaParadaService;
    }

    public function store(StoreIncidenciaParadaRequest $request, PuestaEnMarcha $puestaEnMarcha)
    {
        $datosValidados = $request->validated();

        try {
            $incidencia = $this->incidenciaParadaService->registrarParada(
                $puestaEnMarcha,
                $datosValidados['motivo'],
                $datosValidados['notas'] ?? ''
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Parada registrada. Máquina detenida.',
                    'data' => $incidencia,
                ]);
            }

            return back()->with('success', 'Parada registrada.');
        } catch (\Exception $e) {
            Log::error('Error al registrar parada: '.$e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error interno al registrar parada'], 500);
            }

            return back()->with('error', 'Error al registrar parada.');
        }
    }

    // IMPORTANTE: El nombre de la variable ($incidenciaParada) debe coincidir
    // con el parámetro definido en routes/web.php ('incidenciaParada')
    public function update(Request $request, $id)
    {
        // Buscar manualmente para evitar problemas de binding con IDs vacíos
        $incidenciaParada = IncidenciaParada::findOrFail($id);

        $request->validate([
            'notas_finalizacion' => 'nullable|string|max:1000',
        ]);

        if ($incidenciaParada->ts_fin_parada !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Esta parada ya está finalizada.',
            ], 422);
        }

        try {
            $notas = $request->input('notas_finalizacion', '');
            $incidencia = $this->incidenciaParadaService->finalizarParada($incidenciaParada, $notas);

            return response()->json([
                'success' => true,
                'message' => 'Parada finalizada. Máquina en marcha nuevamente.',
                'data' => $incidencia,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al finalizar parada: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error interno al finalizar parada'], 500);
        }
    }
}
