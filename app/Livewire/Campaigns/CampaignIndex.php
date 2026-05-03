<?php

namespace App\Livewire\Campaigns;

use Livewire\Component;
use App\Models\Campaign;

class CampaignIndex extends Component
{
    public function toggleStatus($campaignId)
    {
        $campaign = Campaign::where('doctor_id', auth()->user()->doctor->id)
                            ->find($campaignId);
        $campaign->update(['is_active' => !$campaign->is_active]);
    }

    public function delete($campaignId)
    {
        Campaign::where('doctor_id', auth()->user()->doctor->id)
                ->find($campaignId)
                ->delete();
    }

    public function render()
    {
        // Obtenemos las campañas del doctor actual
        $campaigns = auth()->user()->doctor->campaigns()->latest()->get();

        return view('livewire.campaigns.campaign-index', compact('campaigns'));
    }
}
