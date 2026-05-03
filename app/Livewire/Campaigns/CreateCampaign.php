<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Str;
use Livewire\Component;

class CreateCampaign extends Component
{
    public $title = '';
    public $slug = '';
    public $content = '';

    // Genera el slug automáticamente al escribir el título
    public function updatedTitle($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|min:5',
            'slug'  => 'required|unique:campaigns,slug,NULL,id,user_id,' . auth()->id(),
            'content' => 'required',
        ]);

        auth()->user()->campaigns()->create($validated);

        return redirect()->route('dashboard')
            ->with('status', '¡Landing creada con éxito!');
    }

    public function render()
    {
        return view('livewire.campaigns.create-campaign');
    }
}
