<?php

namespace Database\Seeders;

use App\Models\LineaProduccion;
use App\Models\Planta;
use Illuminate\Database\Seeder;

class LineaProduccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planta = Planta::first(); // Obtener la primera planta creada

        $lineas = [
            [
                'nombre' => 'Línea A - Ensamblaje Principal',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Línea B - Control de Calidad',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Línea C - Empaque y Distribución',
                'estado' => 'activa',
            ],
        ];

        foreach ($lineas as $linea) {
            LineaProduccion::create([
                'planta_id' => $planta->id,
                ...$linea,
            ]);
        }
    }
}
