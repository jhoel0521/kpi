<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Jornada;
use Illuminate\Database\Eloquent\Collection;

class JornadaForm extends Component
{
    public string $formAction;
    public string $formMethod;
    public string $submitButtonText;

    /**
     * Create a new component instance.
     *
     * Aquí está la lógica que solicitaste:
     * 1. Recibe el modelo $jornada (puede ser null).
     * 2. Recibe las colecciones para los dropdowns.
     * 3. Detecta si es 'create' o 'edit' basado en $jornada->exists.
     */
    public function __construct(
        public Collection $maquinas,
        public Collection $operadores,
        public ?Jornada $jornada = null
    ) {
        // Si $jornada es null, creamos una instancia vacía
        $this->jornada = $jornada ?? new Jornada();

        // Lógica para determinar si es Creación o Edición
        if ($this->jornada->exists) {
            // EDITANDO (el modelo tiene datos)
            $this->formAction = route('jornadas.update', $this->jornada);
            $this->formMethod = 'PATCH';
            $this->submitButtonText = 'Actualizar Jornada';
        } else {
            // CREANDO (el modelo es nuevo/vacío)
            $this->formAction = route('jornadas.store');
            $this->formMethod = 'POST';
            $this->submitButtonText = 'Crear Jornada';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.jornada-form');
    }
}
