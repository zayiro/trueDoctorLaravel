<?php

namespace App\Observers;

use App\Models\Address;
use App\Models\Service;

class AddressObserver
{
    /**
     * Handle the Address "created" event.
     */
    public function created(Address $address): void
    {
        // 1. Buscamos todos los servicios virtuales que pertenecen a este doctor
        // Usamos whereHas para filtrar servicios que ya estén vinculados a otras sedes del mismo doctor
        $virtualServices = Service::where('type', 'virtual')
            ->whereHas('addresses', function ($query) use ($address) {
                $query->where('doctor_id', $address->doctor_id);
            })
            ->get();

        // 2. Asociamos estos servicios a la nueva sede
        if ($virtualServices->isNotEmpty()) {
            $address->services()->attach($virtualServices->pluck('id'));
        }
    }

    /**
     * Handle the Address "updated" event.
     */
    public function updated(Address $address): void
    {
        //
    }

    /**
     * Handle the Address "deleted" event.
     */
    public function deleted(Address $address): void
    {
        //
    }

    /**
     * Handle the Address "restored" event.
     */
    public function restored(Address $address): void
    {
        //
    }

    /**
     * Handle the Address "force deleted" event.
     */
    public function forceDeleted(Address $address): void
    {
        //
    }
}
