<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProduccionDetalleRequest;
use App\Models\PuestaEnMarcha;

class ProduccionController extends Controller
{
    /**
     * Registra un dato granular de producción.
     * Tarea T2.5: storeDetalle (registrar dato granular)
     */
    public function storeDetalle(StoreProduccionDetalleRequest $request, PuestaEnMarcha $puestaEnMarcha)
    {
        $datosValidados = $request->validated();

        // Calcular cantidad fallada si no se proporciona
        $cantidadFallada = $datosValidados['cantidad_fallada'] ?? ($datosValidados['cantidad_producida'] - ($datosValidados['cantidad_buena'] ?? $datosValidados['cantidad_producida']));

        // Calcular tasa de defectos si no se proporciona
        $tasaDefectos = $datosValidados['tasa_defectos'] ?? ($datosValidados['cantidad_producida'] > 0 ? ($cantidadFallada / $datosValidados['cantidad_producida']) * 100 : 0);

        // Crear el detalle de producción
        $detalle = $puestaEnMarcha->detallesProduccion()->create([
            'maquina_id' => $puestaEnMarcha->maquina_id,
            'ts' => $datosValidados['ts'],
            'cantidad_producida' => $datosValidados['cantidad_producida'],
            'cantidad_buena' => $datosValidados['cantidad_buena'] ?? $datosValidados['cantidad_producida'],
            'cantidad_fallada' => $cantidadFallada,
            'tasa_defectos' => $tasaDefectos,
            'payload_raw' => $datosValidados['payload_raw'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dato de producción registrado exitosamente.',
            'data' => $detalle,
        ]);
    }
}
