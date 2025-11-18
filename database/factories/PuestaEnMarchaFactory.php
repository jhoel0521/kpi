<?php

namespace Database\Factories;

use App\Models\Jornada;
use App\Models\Maquina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PuestaEnMarcha>
 */
class PuestaEnMarchaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jornada_id' => Jornada::factory(),
            'maquina_id' => Maquina::factory(),
            'ts_inicio' => fake()->dateTimeBetween('-1 day', 'now'),
            'ts_fin' => null,
            'estado' => 'en_marcha',
        ];
    }
}
