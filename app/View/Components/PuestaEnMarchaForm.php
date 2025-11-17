<?php

namespace App\View\Components;

use App\Models\Jornada;
use App\Models\PuestaEnMarcha;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PuestaEnMarchaForm extends Component
{
    public string $formAction;

    public string $formMethod;

    public string $submitButtonText;

    /**
     * Create a new component instance.
     * Detecta si es 'create' (iniciar) o 'edit' (finalizar).
     */
    public function __construct(
        public ?Jornada $jornada = null, // Requerido para 'create'
        public ?PuestaEnMarcha $puestaEnMarcha = null // Requerido para 'edit'
    ) {
        if ($this->puestaEnMarcha && $this->puestaEnMarcha->exists) {
            // EDITANDO (Finalizando una Puesta en Marcha)
            $this->jornada = $this->puestaEnMarcha->jornada; // Cargar la jornada desde la PEM
            $this->formAction = route('puestas-en-marcha.update', $this->puestaEnMarcha);
            $this->formMethod = 'PATCH';
            $this->submitButtonText = 'Finalizar Puesta en Marcha';
        } else {
            // CREANDO (Iniciando una Puesta en Marcha)
            $this->puestaEnMarcha = new PuestaEnMarcha;
            // $this->jornada se pasa desde el constructor
            $this->formAction = route('jornadas.puestas-en-marcha.store', $this->jornada);
            $this->formMethod = 'POST';
            $this->submitButtonText = 'Iniciar Puesta en Marcha';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.puesta-en-marcha-form');
    }
}
