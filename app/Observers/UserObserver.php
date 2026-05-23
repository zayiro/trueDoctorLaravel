<?php

namespace App\Observers;

use App\Models\User;
use App\Mail\WelcomePatientMail;
use App\Mail\WelcomeDoctorMail;
use App\Mail\WelcomeClinicMail;
use App\Mail\WelcomeAdminMail;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Se ejecuta automáticamente inmediatamente después de insertar un usuario en la BD.
     */
    public function created(User $user): void
    {
        // Evaluamos el rol del enum de tu tabla 'users'
        //enviamos el correo de bienvenida al usuario segun el role
        switch ($user->role) {
            case 'doctor':
                Mail::to($user->email)->send(new WelcomeDoctorMail($user));
                break;

            case 'patient':
                Mail::to($user->email)->send(new WelcomePatientMail($user));
                break;

            case 'clinic':
                Mail::to($user->email)->send(new WelcomeClinicMail($user));
                break;
            
            case 'admin':
                Mail::to($user->email)->send(new WelcomeAdminMail($user));
                break;
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
