<?php

// Ruta: app/Http/Services/Implementations/IncidenciaParadaService.php

namespace App\Http\Services\Implementations;

use App\Http\Services\Interfaces\IncidenciaParadaServiceInterface;
use App\Models\IncidenciaParada;
use App\Models\PuestaEnMarcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncidenciaParadaService implements IncidenciaParadaServiceInterface
{
    /**
     * Registra una nueva parada no planificada.
     */
    public function registrarParada(PuestaEnMarcha $puestaEnMarcha, string $motivo, string $notas): IncidenciaParada
    {
        return DB::transaction(function () use ($puestaEnMarcha, $motivo, $notas) {
            Log::info("Registrando parada para PEM ID: {$puestaEnMarcha->id}");

            // 1. Crear la incidencia
            $incidencia = $puestaEnMarcha->incidenciasParada()->create([
                'maquina_id' => $puestaEnMarcha->maquina_id,
                'ts_inicio_parada' => now(),
                'motivo' => $motivo,
                'notas' => $notas,
                'creado_por' => Auth::id(),
            ]);

            // 2. CAMBIO DE ESTADO (Switch): Marcar la PEM como 'parada'
            // Esto bloquea el registro de producción normal y cambia la UI
            if ($puestaEnMarcha->estado === 'en_marcha') {
                $puestaEnMarcha->update(['estado' => 'parada']);
                Log::info("Estado de PEM {$puestaEnMarcha->id} cambiado a 'parada'");
            }

            return $incidencia;
        });
    }

    /**
     * Finaliza una parada no planificada.
     */
    public function finalizarParada(IncidenciaParada $incidenciaParada, string $notasFinalizacion): IncidenciaParada
    {
        return DB::transaction(function () use ($incidenciaParada, $notasFinalizacion) {
            Log::info("Finalizando parada ID: {$incidenciaParada->id}");

            // Validación defensiva (por si el binding fallara de nuevo, que no debería)
            if (! $incidenciaParada->exists) {
                throw new \Exception('Error crítico: Intentando finalizar una parada no persistida.');
            }

            $tsFin = now();
            // Asegurar que ts_inicio existe
            $tsInicio = $incidenciaParada->ts_inicio_parada ?? $incidenciaParada->created_at;
            $duracionSegundos = $tsInicio->diffInSeconds($tsFin);

            // 1. Actualizar la incidencia
            $incidenciaParada->update([
                'ts_fin_parada' => $tsFin,
                'duracion_segundos' => $duracionSegundos,
                'notas' => trim(($incidenciaParada->notas ?? '')."\n".$notasFinalizacion),
            ]);

            // 2. CAMBIO DE ESTADO (Switch): Reactivar la PEM a 'en_marcha'
            $puestaEnMarcha = $incidenciaParada->puestaEnMarcha;
            if ($puestaEnMarcha->estado === 'parada') {
                $puestaEnMarcha->update(['estado' => 'en_marcha']);
                Log::info("Estado de PEM {$puestaEnMarcha->id} restaurado a 'en_marcha'");
            }

            return $incidenciaParada;
        });
    }
}
