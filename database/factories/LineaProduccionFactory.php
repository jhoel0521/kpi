<?php

namespace Database\Factories;

use App\Models\LineaProduccion;
use App\Models\Planta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LineaProduccion>
 */
class LineaProduccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planta_id' => Planta::factory(),
            'nombre' => fake()->word(),
            'estado' => 'activa',
        ];
    }
}