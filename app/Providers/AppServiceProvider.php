<?php

namespace App\Providers;

use App\Models\Doctor;
use App\Observers\DoctorObserver;
use App\Models\Address;
use App\Observers\AddressObserver;
use Illuminate\Support\ServiceProvider;
use App\Events\AppointmentCancelled;
use App\Listeners\SendCancellationEmail;
use Illuminate\Support\Facades\Event;

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
        Event::listen(
            AppointmentCancelled::class,
            SendCancellationEmail::class
        );

        // Laravel escucha a el modelo Address.
        // cada vez que el doctor abra una nueva sede física, sus servicios virtuales (que ya existen) se habilitarán allí al instante sin que tenga que editar nada.
        Address::observe(AddressObserver::class);
        // Vinculamos el modelo con su observador
        Doctor::observe(DoctorObserver::class);
    }
}
