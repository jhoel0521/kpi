<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePuestaEnMarchaRequest extends FormRequest
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

        return [
            'ts_fin' => [
                'required',
                'date',
                // FSM: La hora de fin debe ser posterior a la hora de inicio
                'after_or_equal:'.$puestaEnMarcha->ts_inicio,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ts_fin.after_or_equal' => 'La hora de fin debe ser posterior o igual a la hora de inicio.',
        ];
    }
}
