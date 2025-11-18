<?php

use App\Http\Services\Interfaces\IncidenciaParadaServiceInterface;
use App\Models\Jornada;
use App\Models\Maquina;
use App\Models\ProduccionDetalle;
use App\Models\PuestaEnMarcha;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('completes full jornada workflow', function () {
    $incidenciaParadaService = app(IncidenciaParadaServiceInterface::class);
    DB::transaction(function () use ($incidenciaParadaService) {
        // Crear datos de prueba
        $user = User::factory()->create();
        $maquina = Maquina::factory()->create();

        // 1. Iniciar Jornada
        $jornada = Jornada::factory()->create([
            'maquina_id' => $maquina->id,
            'operador_id_inicio' => $user->id,
            'operador_id_actual' => $user->id,
            'estado' => 'activa',
        ]);

        expect($jornada->estado)->toBe('activa');
        expect($jornada->maquina_id)->toBe($maquina->id);

        // 2. Iniciar Puesta en Marcha
        $puestaEnMarcha = PuestaEnMarcha::factory()->create([
            'jornada_id' => $jornada->id,
            'maquina_id' => $maquina->id,
            'estado' => 'en_marcha',
        ]);

        expect($puestaEnMarcha->estado)->toBe('en_marcha');

        // 3. Registrar Producción inicial
        $produccion1 = ProduccionDetalle::factory()->create([
            'puesta_en_marcha_id' => $puestaEnMarcha->id,
            'maquina_id' => $maquina->id,
            'cantidad_producida' => 100,
            'cantidad_buena' => 95,
            'cantidad_fallada' => 5,
            'tasa_defectos' => 5.0,
        ]);
        expect($produccion1->cantidad_producida)->toEqual(100.0);
        expect($produccion1->tasa_defectos)->toEqual(5.0);

        // 4. Iniciar Parada
        $parada1 = $incidenciaParadaService->registrarParada($puestaEnMarcha, 'falla_electrica', 'Parada por falla eléctrica');

        expect($parada1->ts_fin_parada)->toBeNull();
        expect($parada1->duracion_segundos)->toBeNull();

        // Verificar que hay parada activa
        $paradasActivas = $jornada->fresh()->puestasEnMarcha->flatMap->incidenciasParada->whereNull('ts_fin_parada');
        expect($paradasActivas)->toHaveCount(1);

        // 5. Finalizar Parada
        $parada1 = $incidenciaParadaService->finalizarParada($parada1, 'Parada finalizada por reparación');

        expect($parada1->ts_fin_parada)->not->toBeNull();
        expect($parada1->duracion_segundos)->toBeGreaterThan(0);

        // Verificar que no hay paradas activas
        $paradasActivas = $jornada->fresh()->puestasEnMarcha->flatMap->incidenciasParada->whereNull('ts_fin_parada');
        expect($paradasActivas)->toHaveCount(0);

        // 6. Registrar Producción después de parada
        $produccion2 = ProduccionDetalle::factory()->create([
            'puesta_en_marcha_id' => $puestaEnMarcha->id,
            'maquina_id' => $maquina->id,
            'cantidad_producida' => 150,
            'cantidad_buena' => 145,
            'cantidad_fallada' => 5,
            'tasa_defectos' => (5 / 150) * 100,
        ]);
        expect($produccion2->cantidad_producida)->toEqual(150.0);

        // 7. Iniciar otra Parada
        $parada2 = $incidenciaParadaService->registrarParada($puestaEnMarcha, 'falta_material', 'Falta de material');

        expect($parada2->ts_fin_parada)->toBeNull();

        // Verificar que hay parada activa
        $paradasActivas = $jornada->fresh()->puestasEnMarcha->flatMap->incidenciasParada->whereNull('ts_fin_parada');
        expect($paradasActivas)->toHaveCount(1);

        // 8. Finalizar segunda Parada
        $parada2 = $incidenciaParadaService->finalizarParada($parada2, 'Parada finalizada, material recibido');

        expect($parada2->ts_fin_parada)->not->toBeNull();
        expect($parada2->duracion_segundos)->toBeGreaterThan(0);

        // Verificar que no hay paradas activas
        $paradasActivas = $jornada->fresh()->puestasEnMarcha->flatMap->incidenciasParada->whereNull('ts_fin_parada');
        expect($paradasActivas)->toHaveCount(0);

        // 9. Registrar Producción final
        $produccion3 = ProduccionDetalle::factory()->create([
            'puesta_en_marcha_id' => $puestaEnMarcha->id,
            'maquina_id' => $maquina->id,
            'cantidad_producida' => 200,
            'cantidad_buena' => 190,
            'cantidad_fallada' => 10,
            'tasa_defectos' => (10 / 200) * 100,
        ]);

        expect($produccion3->cantidad_producida)->toEqual(200.0);

        // 10. Finalizar Jornada
        $jornada->update([
            'estado' => 'finalizada',
            'ts_fin' => now(),
        ]);
        $jornada->refresh();

        expect($jornada->estado)->toBe('finalizada');
        expect($jornada->ts_fin)->not->toBeNull();

        // Verificaciones finales
        expect($jornada->puestasEnMarcha)->toHaveCount(1);
        expect($puestaEnMarcha->detallesProduccion)->toHaveCount(3);
        expect($puestaEnMarcha->incidenciasParada)->toHaveCount(2);
        expect($puestaEnMarcha->incidenciasParada->whereNotNull('ts_fin_parada'))->toHaveCount(2);
    });
});
