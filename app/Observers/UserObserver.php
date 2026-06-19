<?php

namespace App\Models; // Asegúrate de importar el modelo User para las notificaciones
namespace App\Observers;

use App\Models\User;
use App\Mail\WelcomePatientMail;
use App\Mail\WelcomeDoctorMail;
use App\Mail\WelcomeClinicMail;
use App\Mail\WelcomeAdminMail;
use App\Notifications\MailLimitExceededNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * 
     * ⚠️ RESPONSABILIDAD EXCLUSIVA: Enviar correo de bienvenida
     * 
     * Se ejecuta automáticamente inmediatamente después de insertar un usuario en la BD.
     * Este es el ÚNICO lugar donde se envía el correo de bienvenida para evitar duplicidad.
     * 
     * Los controladores (RegisterDoctorController, RegisterClinicController, etc.)
     * NO deben enviar correos de bienvenida. Solo el Observer es responsable.
     * 
     * Flujo:
     * 1. Usuario se crea en la BD
     * 2. UserObserver::created() se dispara automáticamente
     * 3. Se envía el correo de bienvenida según el rol
     * 4. Si falla, se notifica a los administradores
     * 
     * GARANTÍA: No hay duplicidad de correos porque este es el ÚNICO punto de envío.
     */
    public function created(User $user): void
    {
        $userEmail = $user->email;

        try {
            // Evaluamos el rol del enum de la tabla 'users'
            // Enviamos el correo de bienvenida al usuario según el rol
            switch ($user->role) {
                case 'doctor':
                    Mail::to($userEmail)->send(new WelcomeDoctorMail($user));
                    break;

                case 'patient':
                    Mail::to($userEmail)->send(new WelcomePatientMail($user));
                    break;

                case 'clinic':
                    Mail::to($userEmail)->send(new WelcomeClinicMail($user));
                    break;
                
                case 'admin':
                    Mail::to($userEmail)->send(new WelcomeAdminMail($user));
                    break;
            }
        } catch (Throwable $e) {
            // 1. Registramos el fallo técnico detallado en el log (storage/logs/laravel.log)
            Log::error("Fallo crítico al enviar correo de bienvenida al rol [{$user->role}] ({$userEmail}): " . $e->getMessage());

            // 2. Buscamos de forma segura a todos los administradores globales del sistema
            $admins = User::where('role', 'admin')->get();

            // 3. Despachamos de manera interna la notificación en la base de datos para el staff
            foreach ($admins as $admin) {
                // Evitamos que un admin se notifique a sí mismo si fue él quien disparó el evento
                if ($admin->id !== $user->id) {
                    $admin->notify(new MailLimitExceededNotification($e->getMessage(), $userEmail));
                }
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
