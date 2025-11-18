<?php

namespace Database\Factories;

use App\Models\Maquina;
use App\Models\PuestaEnMarcha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncidenciaParada>
 */
class IncidenciaParadaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'puesta_en_marcha_id' => PuestaEnMarcha::factory(),
            'maquina_id' => Maquina::factory(),
            'ts_inicio_parada' => fake()->dateTimeBetween('-1 day', 'now'),
            'ts_fin_parada' => null,
            'duracion_segundos' => null,
            'motivo' => fake()->randomElement(['falla_electrica', 'falta_material', 'atasco', 'mantenimiento']),
            'notas' => fake()->sentence(),
            'creado_por' => 1, // Asumir user 1
        ];
    }
}
