<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    // 1. Declarar las propiedades públicas
    public ?string $metaTitleMedicalAnalysis;
    public ?string $metaDescriptionMedicalAnalysis;

    // 2. Recibirlas en el constructor (Laravel convierte camelCase a snake-case automáticamente)
    public function __construct(
        $metaTitleMedicalAnalysis = null, 
        $metaDescriptionMedicalAnalysis = null
    ) {
        $this->metaTitleMedicalAnalysis = $metaTitleMedicalAnalysis;
        $this->metaDescriptionMedicalAnalysis = $metaDescriptionMedicalAnalysis;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
