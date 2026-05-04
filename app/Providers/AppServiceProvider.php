<?php

namespace App\Providers;

use App\Models\Address;
use App\Observers\AddressObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel escucha a el modelo Address.
        // cada vez que el doctor abra una nueva sede física, sus servicios virtuales (que ya existen) se habilitarán allí al instante sin que tenga que editar nada.
        Address::observe(AddressObserver::class);
    }
}
