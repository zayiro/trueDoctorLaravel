<?php

namespace App\Livewire;

use App\Models\Doctor; // Cambiado de User a Doctor
use App\Models\Campaign;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PublicLanding extends Component
{
    public $doctor; // Cambiado de $user a $doctor
    public $campaign;

    // Los nombres de los parámetros deben coincidir con los de la ruta en web.php
    public function mount($partner_slug, $campaign_slug)
    {
        // 1. Buscamos en la tabla doctors usando la variable mapeada de la ruta
        $this->doctor = Doctor::where('slug', $partner_slug)->firstOrFail();
        
        // 2. Buscamos la campaña que pertenezca a ese doctor
        $this->campaign = Campaign::where('doctor_id', $this->doctor->id)
            ->where('slug', $campaign_slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.public-landing');
    }
}
