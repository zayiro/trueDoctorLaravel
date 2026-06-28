<?php

namespace App\Providers;

use App\Models\Doctor;
use App\Observers\DoctorObserver;

use App\Models\Clinic;
use App\Observers\ClinicObserver;

use App\Models\User;
use App\Observers\UserObserver;

use App\Models\Address;
use App\Observers\AddressObserver;

use App\Models\Appointment;
use App\Observers\AppointmentObserver;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Events\AppointmentCancelled;
use App\Listeners\SendCancellationEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use App\Services\Twilio\WhatsAppTemplateService;
use Laravel\Fortify\Fortify;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppTemplateService::class, function () {
            return new WhatsAppTemplateService();
        });
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

        //setSafeMode(true) escapa cualquier HTML crudo que venga dentro del Markdown
        Blade::directive('markdown', function ($expression) {
            return "<?php 
                \$parsedown = new \Parsedown();
                \$parsedown->setSafeMode(true);
                echo \$parsedown->text({$expression}); 
            ?>";
        });

        User::observe(UserObserver::class);
        // Laravel escucha a el modelo Address.
        // cada vez que el doctor abra una nueva sede física, sus servicios virtuales (que ya existen) 
        // se habilitarán allí al instante sin que tenga que editar nada.
        Address::observe(AddressObserver::class);
        // Vinculamos el modelo con su observador
        Clinic::observe(ClinicObserver::class);
        Doctor::observe(DoctorObserver::class);
        // Observador de citas con transacciones de base de datos para garantizar integridad
        Appointment::observe(AppointmentObserver::class);

        // ✅ Rate limiter para búsqueda de usuarios
        RateLimiter::for('App\Models\User::search', function () {
            return Limit::perMinute(60);
        });

        Fortify::authenticateUsing(function ($request) {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return null;
            }

            $isActive = match ($user->role) {
                'patient' => $user->patient?->active ?? true,
                'doctor'  => $user->doctor?->active ?? true,
                'clinic'  => $user->clinic?->active ?? true,
                default   => true,
            };

            if (!$isActive) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => ['Tu cuenta ha sido desactivada. Contacta al administrador para más información.'],
                ]);
            }

            return $user;
        });
    }
}
