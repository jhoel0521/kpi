<?php

namespace Database\Factories;

use App\Models\PuestaEnMarcha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProduccionDetalle>
 */
class ProduccionDetalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $puesta = PuestaEnMarcha::factory()->create();
        $producida = fake()->numberBetween(50, 200);
        $buena = fake()->numberBetween(0, $producida);
        $fallada = $producida - $buena;

        return [
            'puesta_en_marcha_id' => $puesta->id,
            'maquina_id' => $puesta->maquina_id,
            'ts' => fake()->dateTimeBetween('-1 day', 'now'),
            'cantidad_producida' => $producida,
            'cantidad_buena' => $buena,
            'cantidad_fallada' => $fallada,
            'tasa_defectos' => $producida > 0 ? ($fallada / $producida) * 100 : 0,
        ];
    }
}
