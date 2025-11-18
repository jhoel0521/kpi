<?php

namespace Database\Factories;

use App\Models\Jornada;
use App\Models\Maquina;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jornada>
 */
class JornadaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maquina_id' => Maquina::factory(),
            'nombre' => fake()->randomElement(['Dia', 'Noche', 'Madrugada']),
            'ts_inicio' => fake()->dateTimeBetween('-1 day', 'now'),
            'ts_fin' => null,
            'operador_id_inicio' => User::factory(),
            'operador_id_actual' => User::factory(),
            'cantidad_producida_esperada' => fake()->numberBetween(1000, 5000),
            'estado' => 'activa',
        ];
    }
}