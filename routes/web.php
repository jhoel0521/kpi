<?php

use App\Http\Controllers\JornadaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('jornadas', JornadaController::class); // Proteger rutas
