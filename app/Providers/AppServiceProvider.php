<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use App\Observers\AddressObserver;
use App\Observers\AppointmentObserver;
use App\Observers\ClinicObserver;
use App\Observers\DoctorObserver;
use App\Observers\UserObserver;
use App\Services\Twilio\WhatsAppTemplateService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;

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
     *
     * NOTA SOBRE EVENTOS: los listeners de app/Listeners (con handle() tipado) se descubren solos.
     * NO los registres aquí con Event::listen(): quedarían registrados dos veces y se ejecutarían dos veces
     * (así se enviaban duplicados los correos de cancelación). Verifica con: php artisan event:list
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }
        
        $this->registerObservers();
        $this->registerBladeDirectives();
        $this->registerRateLimiters();
        $this->registerFortifyAuthentication();
    }

    /**
     * Vincula cada modelo con su observador.
     */
    private function registerObservers(): void
    {
        User::observe(UserObserver::class);

        // Cada vez que el doctor abra una nueva sede física, sus servicios virtuales (que ya existen)
        // se habilitan allí al instante sin que tenga que editar nada.
        Address::observe(AddressObserver::class);

        Clinic::observe(ClinicObserver::class);
        Doctor::observe(DoctorObserver::class);

        // Citas: sincroniza Zoom, invalida la caché de disponibilidad y notifica reagendamientos
        Appointment::observe(AppointmentObserver::class);
    }

    private function registerBladeDirectives(): void
    {
        // setSafeMode(true) escapa cualquier HTML crudo que venga dentro del Markdown
        Blade::directive('markdown', function ($expression) {
            return "<?php 
                \$parsedown = new \Parsedown();
                \$parsedown->setSafeMode(true);
                echo \$parsedown->text({$expression}); 
            ?>";
        });
    }

    private function registerRateLimiters(): void
    {
        // Búsqueda de usuarios
        RateLimiter::for('App\Models\User::search', function () {
            return Limit::perMinute(60);
        });
    }

    /**
     * Login de Fortify: valida credenciales y rechaza las cuentas desactivadas.
     * El aviso de cuenta desactivada solo se muestra DESPUÉS de validar la contraseña,
     * para no revelar a un tercero qué correos existen.
     */
    private function registerFortifyAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return null;
            }

            if (!$this->isAccountActive($user)) {
                throw ValidationException::withMessages([
                    'email' => ['Tu cuenta ha sido desactivada. Contacta al administrador para más información.'],
                ]);
            }

            return $user;
        });
    }

    /**
     * Una cuenta está activa salvo que su perfil (paciente, doctor o clínica) diga lo contrario.
     */
    private function isAccountActive(User $user): bool
    {
        return (bool) match ($user->role) {
            'patient' => $user->patient?->active ?? true,
            'doctor'  => $user->doctor?->active ?? true,
            'clinic'  => $user->clinic?->active ?? true,
            default   => true,
        };
    }
}