<?php

namespace Database\Seeders;

use App\Models\LineaProduccion;
use App\Models\Maquina;
use Illuminate\Database\Seeder;

class MaquinaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lineas = LineaProduccion::all();

        $maquinas = [
            // Línea A - 2 máquinas
            [
                'linea_produccion_id' => $lineas->first()->id,
                'nombre' => 'Máquina A1 - Ensambladora Automática',
                'estado' => 'operativa',
            ],
            [
                'linea_produccion_id' => $lineas->first()->id,
                'nombre' => 'Máquina A2 - Soldadora Láser',
                'estado' => 'operativa',
            ],

            // Línea B - 2 máquinas
            [
                'linea_produccion_id' => $lineas->skip(1)->first()->id,
                'nombre' => 'Máquina B1 - Inspector Óptico',
                'estado' => 'operativa',
            ],
            [
                'linea_produccion_id' => $lineas->skip(1)->first()->id,
                'nombre' => 'Máquina B2 - Tester Funcional',
                'estado' => 'operativa',
            ],

            // Línea C - 2 máquinas
            [
                'linea_produccion_id' => $lineas->skip(2)->first()->id,
                'nombre' => 'Máquina C1 - Empacadora Automática',
                'estado' => 'operativa',
            ],
            [
                'linea_produccion_id' => $lineas->skip(2)->first()->id,
                'nombre' => 'Máquina C2 - Etiquetadora',
                'estado' => 'operativa',
            ],
        ];

        foreach ($maquinas as $maquina) {
            Maquina::create($maquina);
        }
    }
}
