<?php

use App\Http\Controllers\CambioOperadorController;
use App\Http\Controllers\IncidenciaParadaController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PuestaEnMarchaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de jornadas protegidas
    Route::resource('jornadas', JornadaController::class);
    // T2.2: Rutas para Cambio de Operador (Anidadas en Jornada)
    // GET  /jornadas/{jornada}/cambios-operador/create
    // POST /jornadas/{jornada}/cambios-operador
    Route::resource('jornadas.cambios-operador', CambioOperadorController::class)
        ->shallow() // ->shallow() evita que rutas como 'edit' sean anidadas
        ->only(['create', 'store']);

    // T2.3: Rutas para Puesta en Marcha
    // GET  /jornadas/{jornada}/puestas-en-marcha/create
    // POST /jornadas/{jornada}/puestas-en-marcha
    Route::resource('jornadas.puestas-en-marcha', PuestaEnMarchaController::class)
        ->shallow()
        ->only(['create', 'store']);

    // Rutas no anidadas para 'show', 'edit', 'update', 'destroy'
    // (Gracias a shallow() de la definición anterior)
    // GET    /puestas-en-marcha/{puesta_en_marcha}
    // GET    /puestas-en-marcha/{puesta_en_marcha}/edit
    // PATCH  /puestas-en-marcha/{puesta_en_marcha}
    // DELETE /puestas-en-marcha/{puesta_en_marcha}
    Route::resource('puestas-en-marcha', PuestaEnMarchaController::class)
        ->only(['show', 'edit', 'update', 'destroy']);

    // T2.4: Rutas para Incidencia de Parada (Anidadas en Puesta en Marcha)
    // POST /puestas-en-marcha/{puesta_en_marcha}/incidencias-parada
    Route::resource('puestas-en-marcha.incidencias-parada', IncidenciaParadaController::class)
        ->shallow()
        ->only(['store']);

    // Rutas no anidadas para 'update'
    // PATCH  /incidencias-parada/{incidencia_parada}
    Route::resource('incidencias-parada', IncidenciaParadaController::class)
        ->only(['update']);
});

require __DIR__.'/auth.php';
