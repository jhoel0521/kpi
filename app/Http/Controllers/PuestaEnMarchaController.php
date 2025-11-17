<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePuestaEnMarchaRequest;
use App\Http\Requests\UpdatePuestaEnMarchaRequest;
use App\Models\Jornada;
use App\Models\PuestaEnMarcha;
use Illuminate\Http\Request;

class PuestaEnMarchaController extends Controller
{
    /**
     * Muestra el formulario para crear (iniciar) una nueva puesta en marcha.
     * Esta acción está anidada bajo una jornada.
     */
    public function create(Jornada $jornada)
    {
        // Validar FSM (aunque el Request lo hará, es bueno pre-validar)
        if ($jornada->estado !== 'activa' || $jornada->maquina->estado !== 'operativa') {
            return redirect()->route('jornadas.show', $jornada)->with('error', 'La jornada o la máquina no están activas/operativas.');
        }

        return view('puestas-en-marcha.create', compact('jornada'));
    }

    /**
     * Almacena (inicia) una nueva puesta en marcha.
     * Tarea T2.3: store (iniciar)
     */
    public function store(StorePuestaEnMarchaRequest $request, Jornada $jornada)
    {
        $datosValidados = $request->validated();

        // Rellenar datos FSM
        $datosValidados['maquina_id'] = $jornada->maquina_id;
        $datosValidados['estado'] = 'en_marcha';

        $jornada->puestasEnMarcha()->create($datosValidados);

        return redirect()->route('jornadas.show', $jornada)->with('success', 'Puesta en Marcha iniciada exitosamente.');
    }

    /**
     * Muestra el formulario para editar (finalizar) una puesta en marcha.
     */
    public function edit(PuestaEnMarcha $puestaEnMarcha)
    {
        // Lógica FSM: Solo se pueden finalizar las que están 'en_marcha'
        if ($puestaEnMarcha->estado !== 'en_marcha') {
            return redirect()->route('jornadas.show', $puestaEnMarcha->jornada_id)->with('error', 'Esta puesta en marcha ya está finalizada.');
        }

        return view('puestas-en-marcha.edit', compact('puestaEnMarcha'));
    }

    /**
     * Actualiza (finaliza) una puesta en marcha.
     * Tarea T2.3: update (finalizar)
     */
    public function update(UpdatePuestaEnMarchaRequest $request, PuestaEnMarcha $puestaEnMarcha)
    {
        $datosValidados = $request->validated();

        // Rellenar datos FSM
        $datosValidados['estado'] = 'finalizada';

        $puestaEnMarcha->update($datosValidados);

        // TAREA T2.8 (FUTURO):
        // Aquí es donde llamaremos al servicio para calcular el resumen de OEE
        // \App\Services\ResumenProduccionService::generar($puestaEnMarcha);

        return redirect()->route('jornadas.show', $puestaEnMarcha->jornada_id)->with('success', 'Puesta en Marcha finalizada. Resumen en proceso.');
    }

    /**
     * Muestra los detalles de una puesta en marcha específica.
     */
    public function show(PuestaEnMarcha $puestaEnMarcha)
    {
        // Cargar relaciones
        $puestaEnMarcha->load(['jornada', 'maquina', 'detallesProduccion', 'incidenciasParada']);

        // Aquí podríamos tener una vista de detalles si fuera necesario
        // Por ahora, redirigimos a la jornada
        return redirect()->route('jornadas.show', $puestaEnMarcha->jornada_id);
    }

    /**
     * Elimina una puesta en marcha.
     */
    public function destroy(PuestaEnMarcha $puestaEnMarcha)
    {
        $jornadaId = $puestaEnMarcha->jornada_id;
        try {
            $puestaEnMarcha->delete();

            return redirect()->route('jornadas.show', $jornadaId)->with('success', 'Puesta en Marcha eliminada.');
        } catch (\Exception $e) {
            return redirect()->route('jornadas.show', $jornadaId)->with('error', 'No se pudo eliminar.');
        }
    }
}
