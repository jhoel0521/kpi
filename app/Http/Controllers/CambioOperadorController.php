<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCambioOperadorRequest;
use App\Models\Jornada;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CambioOperadorController extends Controller
{
    /**
     * Muestra el formulario para registrar un cambio de operador.
     * Esta acción está anidada bajo una jornada.
     */
    public function create(Jornada $jornada)
    {
        // Lógica de negocio: No se puede cambiar operador en jornada 'completada'
        if ($jornada->estado !== 'activa') {
            return redirect()->route('jornadas.show', $jornada)->with('error', 'No se puede cambiar el operador en una jornada que no está activa.');
        }

        $operadores = User::where('id', '!=', $jornada->operador_id_actual)->get(); // Excluir al operador actual

        return view('cambios-operador.create', compact('jornada', 'operadores'));
    }

    /**
     * Almacena el nuevo cambio de operador.
     * Tarea T2.2: "Actualizar `operador_id_actual` en `jornada`"
     */
    public function store(StoreCambioOperadorRequest $request, Jornada $jornada)
    {
        $datosValidados = $request->validated();

        $operadorAnteriorId = $jornada->operador_id_actual;

        // 1. Crear el registro de auditoría
        $jornada->cambiosOperador()->create([
            'operador_anterior_id' => $operadorAnteriorId,
            'operador_nuevo_id' => $datosValidados['operador_nuevo_id'],
            'razon' => $datosValidados['razon'],
            'creado_por' => Auth::id(), // El supervisor que hace el cambio
            'ts_cambio' => now(),
        ]);

        // 2. Actualizar el operador actual en la jornada (Lógica T2.2)
        $jornada->update([
            'operador_id_actual' => $datosValidados['operador_nuevo_id'],
        ]);

        return redirect()->route('jornadas.show', $jornada)->with('success', 'Operador actualizado exitosamente.');
    }
}
