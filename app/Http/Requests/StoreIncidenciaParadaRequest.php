<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidenciaParadaRequest extends FormRequest
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
            'motivo' => 'required|string|max:255',
            'notas' => 'nullable|string|max:1000',

            // LÓGICA FSM (TAREA T2.7)
            'puesta_en_marcha_id' => [
                // FSM: La puesta en marcha debe estar 'en_marcha'
                Rule::exists('puesta_en_marchas', 'id')->where(function ($query) use ($puestaEnMarcha) {
                    $query->where('id', $puestaEnMarcha->id)->where('estado', 'en_marcha');
                }),
            ],
        ];
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

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de la parada es obligatorio.',
            'motivo.max' => 'El motivo no puede exceder los 255 caracteres.',
            'notas.max' => 'Las notas no pueden exceder los 1000 caracteres.',
            'puesta_en_marcha_id.exists' => 'No se pueden registrar paradas en una puesta en marcha que no está activa.',
        ];
    }
}
