<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PromoCode;
use App\Models\User;
use App\Mail\WelcomePatientMail;
use App\Mail\WelcomeDoctorMail;
use App\Mail\WelcomeClinicMail;
use App\Mail\WelcomeAdminMail;
use App\Notifications\MailLimitExceededNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Observer responsable del ciclo de vida del usuario.
 * 
 * RESPONSABILIDADES:
 * 1. Enviar correo de bienvenida según el rol (ÚNICO punto de envío).
 * 2. Crear código promocional para doctores y pacientes nuevos.
 * 3. Sincronizar el slug del doctor cuando cambia su nombre.
 * 
 *  ANTI-PATRÓN EVITADO:
 *  Los controladores NO deben enviar correos de bienvenida.
 *  Si un controlador necesita crear un usuario sin enviar correo
 *  (ej: registro masivo de doctores por una clínica), debe setear
 *  el flag $user->skip_welcome_email = true ANTES de guardar.
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     * 
     *  CORRECCIONES:
     * - Eliminado namespace duplicado (bug fatal)
     * - Respeto del flag skip_welcome_email para evitar doble envío
     * - Unificado uso de $user->role en lugar de hasRole()
     * - Agregado logging de auditoría
     */
    public function created(User $user): void
    {
        $userEmail = $user->email;
        $userRole = $user->role;

        try {
            // 1. Enviar correo de bienvenida SOLO si no está marcado para saltar
            // Esto permite que ClinicDoctorController registre doctores sin doble envío
            if (empty($user->skip_welcome_email)) {
                $this->sendWelcomeEmail($user, $userEmail, $userRole);
            } else {
                Log::info('UserObserver: Correo de bienvenida omitido (flag skip_welcome_email activo)', [
                    'user_id' => $user->id,
                    'email' => $userEmail,
                    'role' => $userRole,
                ]);
            }

            // 2. Crear código promocional para doctores y pacientes
            // Solo si no fue creado en contexto de clínica (clínicas no deben recibir promo codes)
            if (in_array($userRole, ['doctor', 'patient'], true) && empty($user->skip_promo_code)) {
                $this->createPromoCode($user);
            }
        } catch (Throwable $e) {
            $this->handleWelcomeEmailFailure($e, $userEmail, $userRole);
        }
    }

    /**
     * Envía el correo de bienvenida según el rol del usuario.
     */
    private function sendWelcomeEmail(User $user, string $email, string $role): void
    {
        $mailClass = match ($role) {
            'doctor'  => WelcomeDoctorMail::class,
            'patient' => WelcomePatientMail::class,
            'clinic'  => WelcomeClinicMail::class,
            'admin'   => WelcomeAdminMail::class,
            default   => null,
        };

        if ($mailClass === null) {
            Log::warning('UserObserver: Rol sin correo de bienvenida configurado', [
                'user_id' => $user->id,
                'role' => $role,
            ]);
            return;
        }

        Mail::to($email)->send(new $mailClass($user));

        Log::info('UserObserver: Correo de bienvenida enviado', [
            'user_id' => $user->id,
            'email' => $email,
            'role' => $role,
            'mail_class' => $mailClass,
        ]);
    }

    /**
     * Crea un código promocional del 100% de descuento para el usuario.
     */
    private function createPromoCode(User $user): void
    {
        // Prevención de duplicados (idempotencia)
        $existingPromo = PromoCode::where('user_id', $user->id)->first();
        if ($existingPromo) {
            Log::info('UserObserver: Código promocional ya existe, omitiendo creación', [
                'user_id' => $user->id,
                'promo_id' => $existingPromo->id,
            ]);
            return;
        }

        PromoCode::create([
            'user_id'    => $user->id,
            'code'       => Str::upper(Str::random(7)),
            'type'       => 'percent',
            'reward'     => 100.00, // 100% de descuento
            'max_uses'   => 1,
            'expires_at' => null,
            'is_active'  => true,
        ]);

        Log::info('UserObserver: Código promocional creado', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Maneja el fallo del envío de correo de bienvenida.
     * Notifica a los administradores para intervención manual.
     */
    private function handleWelcomeEmailFailure(Throwable $e, string $email, string $role): void
    {
        Log::error("UserObserver: Fallo crítico al enviar correo de bienvenida al rol [{$role}] ({$email})", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Notificar a los administradores
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            if ($admin->id !== auth()->id()) {
                try {
                    $admin->notify(new MailLimitExceededNotification($e->getMessage(), $email));
                } catch (Throwable $notifyError) {
                    Log::error('UserObserver: Fallo al notificar admin sobre error de email', [
                        'admin_id' => $admin->id,
                        'error' => $notifyError->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the User "updating" event.
     * 
     * Sincroniza el slug del doctor cuando cambia su nombre.
     * 
     *  CORRECCIONES:
     * - Unificado uso de $user->role en lugar de hasRole() de Spatie
     * - Agregado manejo de errores para no romper la actualización del usuario
     * - Agregado logging de auditoría
     */
    public function updating(User $user): void
    {
        // Solo actuar si el nombre cambió y el usuario es doctor
        if (!$user->isDirty('name') || $user->role !== 'doctor') {
            return;
        }

        try {
            $doctor = $user->doctor;
            
            if (!$doctor) {
                Log::warning('UserObserver: Usuario doctor sin perfil de doctor asociado', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Generar slug único
            $newSlug = $this->generateUniqueSlug($user->name, $doctor->id);
            
            if ($doctor->slug !== $newSlug) {
                $doctor->update(['slug' => $newSlug]);
                
                Log::info('UserObserver: Slug de doctor actualizado', [
                    'user_id' => $user->id,
                    'doctor_id' => $doctor->id,
                    'old_slug' => $doctor->getOriginal('slug'),
                    'new_slug' => $newSlug,
                ]);
            }
        } catch (Throwable $e) {
            // No romper la actualización del usuario si falla el slug
            Log::error('UserObserver: Error al actualizar slug del doctor', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Genera un slug único para el doctor, evitando colisiones.
     */
    private function generateUniqueSlug(string $name, int $excludeDoctorId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (\App\Models\Doctor::where('slug', $slug)->where('id', '!=', $excludeDoctorId)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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