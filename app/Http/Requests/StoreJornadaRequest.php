<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Maquina;

class StoreJornadaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Implementar lógica de permisos si es necesario (ej: solo supervisores)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'maquina_id' => [
                'required',
                'integer',
                Rule::exists('maquinas', 'id'),
                // T2.7 Lógica FSM (Máquina de Estados Finita):
                // Validar que la máquina esté 'operativa'
                Rule::exists('maquinas', 'id')->where(function ($query) {
                    $query->where('estado', 'operativa');
                }),
            ],
            'nombre' => 'required|string|max:255',
            'ts_inicio' => 'required|date',
            'operador_id_inicio' => ['required', 'integer', Rule::exists('users', 'id')],
            'cantidad_producida_esperada' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Mensajes de error personalizados para la lógica FSM.
     */
    public function messages(): array
    {
        return [
            'maquina_id.exists' => 'La máquina seleccionada no es válida o no está en estado "operativa".',
        ];
    }
}
