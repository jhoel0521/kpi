<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePuestaEnMarchaRequest extends FormRequest
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
        $jornada = $this->route('jornada');
        $maquinaId = $jornada->maquina_id;

        return [
            'ts_inicio' => [
                'required',
                'date',
                // FSM: El inicio de la puesta en marcha debe ser DESPUÉS del inicio de la jornada
                'after_or_equal:'.$jornada->ts_inicio,
            ],
            'cantidad_producida_esperada' => 'nullable|integer|min:0',

            // LÓGICA FSM (TAREA T2.7)
            'jornada_id' => [
                // FSM: La jornada debe estar 'activa'
                Rule::exists('jornadas', 'id')->where(function ($query) use ($jornada) {
                    $query->where('id', $jornada->id)->where('estado', 'activa');
                }),
                // FSM: No debe haber otra puesta en marcha 'en_marcha' para esta máquina
                Rule::unique('puesta_en_marchas', 'jornada_id')->where(function ($query) use ($maquinaId) {
                    return $query->where('maquina_id', $maquinaId)
                        ->where('estado', 'en_marcha');
                }),
            ],
        ];
    }

    /**
     * Pre-poblar el validador con el jornada_id de la ruta.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'jornada_id' => $this->route('jornada')->id,
        ]);
    }

    public function messages(): array
    {
        return [
            'jornada_id.exists' => 'La jornada seleccionada no está activa.',
            'jornada_id.unique' => 'Ya existe una Puesta en Marcha activa para esta máquina en esta jornada.',
            'ts_inicio.after_or_equal' => 'La hora de inicio debe ser posterior al inicio de la jornada.',
        ];
    }
}
