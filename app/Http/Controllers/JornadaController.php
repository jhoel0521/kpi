<?php

namespace App\Http\Controllers;

use App\Models\Jornada;
use App\Models\Maquina;
use App\Models\User;
use App\Http\Requests\StoreJornadaRequest; // Tarea T2.7
use Illuminate\Http\Request;

class JornadaController extends Controller
{
    /**
     * Muestra una lista paginada de todas las jornadas.
     * MÉTODO NUEVO (COMPLETO)
     */
    public function index()
    {
        $jornadas = Jornada::with(['maquina', 'operadorActual'])
            ->orderBy('ts_inicio', 'desc')
            ->paginate(15);

        return view('jornadas.index', compact('jornadas'));
    }

    /**
     * Muestra el formulario para crear una nueva jornada.
     * Pasa 'null' al componente de formulario.
     */
    public function create()
    {
        $maquinas = Maquina::where('estado', 'operativa')->get();
        $operadores = User::all(); // Asumiendo que los operadores están en la tabla 'users'

        // Pasamos las colecciones necesarias para los dropdowns
        return view('jornadas.create', compact('maquinas', 'operadores'));
    }

    /**
     * Almacena una nueva jornada en la base de datos.
     * Usa el StoreJornadaRequest (T2.7) para validación.
     */
    public function store(StoreJornadaRequest $request)
    {
        $datosValidados = $request->validated();

        // Lógica FSM T2.7: Actualizar el operador_id_actual al crear
        $datosValidados['operador_id_actual'] = $datosValidados['operador_id_inicio'];

        Jornada::create($datosValidados);

        return redirect()->route('jornadas.index')->with('success', 'Jornada creada exitosamente.');
    }

    /**
     * Muestra los detalles de una jornada específica.
     * MÉTODO NUEVO (COMPLETO)
     */
    public function show(Jornada $jornada)
    {
        // Cargar relaciones para mostrar detalles completos
        $jornada->load([
            'maquina',
            'operadorInicio',
            'operadorActual',
            'puestasEnMarcha',
            'cambiosOperador.operadorAnterior',
            'cambiosOperador.operadorNuevo'
        ]);

        return view('jornadas.show', compact('jornada'));
    }

    /**
     * Muestra el formulario para editar una jornada existente.
     * Pasa el modelo $jornada (con datos) al componente de formulario.
     */
    public function edit(Jornada $jornada)
    {
        $maquinas = Maquina::all();
        $operadores = User::all();

        // Pasamos el modelo $jornada y las colecciones
        return view('jornadas.edit', compact('jornada', 'maquinas', 'operadores'));
    }

    /**
     * Actualiza una jornada existente en la base de datos.
     * Usa el StoreJornadaRequest (T2.7) para validación.
     */
    public function update(StoreJornadaRequest $request, Jornada $jornada)
    {
        $jornada->update($request->validated());

        return redirect()->route('jornadas.index')->with('success', 'Jornada actualizada exitosamente.');
    }

    /**
     * Elimina una jornada de la base de datos.
     * MÉTODO NUEVO (COMPLETO)
     */
    public function destroy(Jornada $jornada)
    {
        // Lógica de negocio: Opcionalmente, solo permitir borrar si no tiene
        // puestas en marcha activas, etc. Por ahora, es un borrado simple.
        try {
            $jornada->delete();
            return redirect()->route('jornadas.index')->with('success', 'Jornada eliminada exitosamente.');
        } catch (\Exception $e) {
            // Manejar error (ej. si está protegida por Foreign Key)
            return redirect()->route('jornadas.index')->with('error', 'No se pudo eliminar la jornada. Asegúrese de que no tenga datos asociados.');
        }
    }
}
