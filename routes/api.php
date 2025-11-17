<?php

use App\Http\Controllers\CambioOperadorController;
use App\Http\Controllers\IncidenciaParadaController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\PuestaEnMarchaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// T2.6: Rutas API para operaciones del KPI Dashboard
Route::middleware('auth:sanctum')->group(function () {
    // POST /api/jornadas (crear jornada)
    Route::post('/jornadas', [JornadaController::class, 'store']);

    // POST /api/puestas-en-marcha (iniciar puesta en marcha)
    Route::post('/puestas-en-marcha', [PuestaEnMarchaController::class, 'store']);

    // POST /api/incidencias-parada (registrar parada no planificada)
    Route::post('/incidencias-parada', [IncidenciaParadaController::class, 'store']);

    // POST /api/produccion-detalle (registrar dato de producción)
    Route::post('/produccion-detalle', [ProduccionController::class, 'storeDetalle']);

    // POST /api/cambios-operador (registrar cambio de operador)
    Route::post('/cambios-operador', [CambioOperadorController::class, 'store']);
});
