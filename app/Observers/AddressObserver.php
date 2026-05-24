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
        // 🌟 REGLA 1: Solo actuamos si la sede que se está creando es de tipo VIRTUAL
        if ($address->type !== 'virtual') {
            return;
        }

        // 2. Obtenemos todos los servicios virtuales definidos en el catálogo global del SaaS
        $globalVirtualServices = Service::where('type', 'virtual')
            ->where('active', true)
            ->get();

        if ($globalVirtualServices->isEmpty()) {
            return;
        }

        // 3. Estructurar el array para la tabla pivote inyectando los campos obligatorios
        $attachData = [];
        foreach ($globalVirtualServices as $service) {
            $attachData[$service->id] = [
                'price'    => 0.00,  // Precio base por defecto
                'duration' => 20,    // Duración estimada base en minutos
            ];
        }

        // 4. Vinculación atómica en la tabla pivote address_service usando el address_id nativo
        $address->services()->attach($attachData);
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
}
