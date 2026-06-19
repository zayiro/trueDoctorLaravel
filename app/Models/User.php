<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Alias para acceder al perfil del doctor de forma más clara.
     * Utilizado en contextos donde se necesita referencia explícita.
     * 
     * Ejemplo:
     * $doctor = $user->doctorProfile;
     */
    public function doctorProfile()
    {
        return $this->doctor();
    }

    public function clinic() 
    {
        return $this->hasOne(Clinic::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class, 'user_id');
    }    

    /**
     * Resuelve el plan operativo real del médico basado en el contexto de la consulta.
     * Si se proporciona un ID de clínica, el sistema evalúa si está vinculado y hereda su plan.
     */
    public function getActivePlanForContext(?int $clinicId = null): Plan
    {
        // Si el contexto indica una clínica, verificamos la vinculación en la tabla pivote
        if ($clinicId) {
            $clinic = Clinic::where('id', $clinicId)
                ->whereHas('doctors', function ($query) {
                    $query->where('doctor_id', $this->doctorProfile->id)
                        ->where('status', 'approved'); // Solo si está aprobado en la nómina
                })->first();

            if ($clinic) {
                return $clinic->plan; // Hereda el HasOneThrough de la clínica (Ej: clinic_gold)
            }
        }

        // Si no hay contexto de clínica o trabaja particular, rige su plan individual comprado
        return $this->plan ?? Plan::where('slug', 'free')->first();
    }

    /**
     * Relación directa para acceder a los ajustes del médico desde el usuario.
     */
    public function doctorSettings(): HasOneThrough
    {
        return $this->hasOneThrough(
            DoctorSetting::class, // Modelo de destino
            Doctor::class,        // Modelo intermedio
            'user_id',            // Clave foránea en la tabla 'doctors'
            'doctor_id',          // Clave foránea en la tabla 'doctor_settings'
            'id',                 // Clave primaria en la tabla 'users'
            'id'                  // Clave primaria en la tabla 'doctors'
        );
    }
}
