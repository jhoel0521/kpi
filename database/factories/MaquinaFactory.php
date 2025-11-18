<?php

namespace Database\Factories;

use App\Models\LineaProduccion;
use App\Models\Maquina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Maquina>
 */
class MaquinaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lineaProduction = LineaProduccion::factory()->create();
        return [
            'linea_produccion_id' => $lineaProduction->id,
            'nombre' => fake()->word(),
            'serie' => fake()->unique()->word(),
            'estado' => 'operativa',
        ];
    }
}