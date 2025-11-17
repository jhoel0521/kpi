<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCambioOperadorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Asumimos que si puede ver el formulario, puede hacerlo
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // La $jornada se obtiene de la ruta
        $jornada = $this->route('jornada');

        return [
            'operador_nuevo_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                // Lógica FSM: No se puede cambiar al mismo operador
                Rule::notIn([$jornada->operador_id_actual]),
            ],
            'razon' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'operador_nuevo_id.not_in' => 'El nuevo operador debe ser diferente al operador actual.',
            'operador_nuevo_id.exists' => 'El operador seleccionado no es válido.',
        ];
    }
}
