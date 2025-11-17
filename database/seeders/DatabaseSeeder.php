<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        if (! User::where('email', 'test@example.com')->exists()) {
            // trasaccional para asegurar integridad
            DB::transaction(function () {
                User::factory()->create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);

                // Ejecutar seeders en orden de dependencias
                $this->call([
                    PlantaSeeder::class,
                    LineaProduccionSeeder::class,
                    MaquinaSeeder::class,
                ]);
            });
        }
    }
}
