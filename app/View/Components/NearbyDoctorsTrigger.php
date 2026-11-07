<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NearbyDoctorsTrigger extends Component
{
    public string $endpoint;

    public function __construct(
        public int $radius = 50
    ) {
        $this->endpoint = route('api.doctors.nearby');
    }

    public function render(): View
    {
        return view('components.nearby-doctors-trigger');
    }
}
