<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    
    public ?string $title;

    public function __construct(?string $title = null)
    {
        // Asignamos el título, o un default si es nulo.
        $this->title = $title ? $title . ' - KPI Dashboard' : 'KPI Dashboard';
    }

    
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.app-layout');
    }
}
