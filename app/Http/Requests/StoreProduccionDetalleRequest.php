<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProduccionDetalleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $puestaEnMarcha = $this->route('puestaEnMarcha');

        $rules = [
            'ts' => [
                'required',
                'date',
                // FSM: El timestamp debe estar dentro del rango de la puesta en marcha
                'after_or_equal:'.$puestaEnMarcha->ts_inicio,
            ],
            'cantidad_producida' => 'required|integer|min:0',
            'cantidad_buena' => 'nullable|integer|min:0|lte:cantidad_producida',
            'cantidad_fallada' => 'nullable|integer|min:0',
            'tasa_defectos' => 'nullable|numeric|min:0|max:100',
            'payload_raw' => 'nullable|array',
        ];

        // Agregar validación de ts_fin solo si existe
        if ($puestaEnMarcha->ts_fin) {
            $rules['ts'][] = 'before_or_equal:'.$puestaEnMarcha->ts_fin;
        }

        // LÓGICA FSM (TAREA T2.7)
        $rules['puesta_en_marcha_id'] = [
            // FSM: La puesta en marcha debe estar 'en_marcha'
            \Illuminate\Validation\Rule::exists('puesta_en_marchas', 'id')->where(function ($query) use ($puestaEnMarcha) {
                $query->where('id', $puestaEnMarcha->id)->where('estado', 'en_marcha');
            }),
        ];

        return $rules;
    }

    /**
     * Pre-poblar el validador con el puesta_en_marcha_id de la ruta.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'puesta_en_marcha_id' => $this->route('puestaEnMarcha')->id,
        ]);
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'ts.after_or_equal' => 'El timestamp debe ser posterior al inicio de la puesta en marcha.',
            'ts.before_or_equal' => 'El timestamp debe ser anterior al fin de la puesta en marcha.',
            'cantidad_buena.lte' => 'La cantidad buena no puede ser mayor que la cantidad producida.',
            'puesta_en_marcha_id.exists' => 'La puesta en marcha no está activa.',
        ];
    }
}
