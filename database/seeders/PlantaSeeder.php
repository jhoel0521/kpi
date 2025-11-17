<?php

namespace Database\Seeders;

use App\Models\Planta;
use Illuminate\Database\Seeder;

class PlantaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Planta::create([
            'nombre' => 'Planta Principal',
            'zona_horaria' => 'America/La_Paz',
        ]);
    }
}
