<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Activar / desactivar cuentas desde el admin.
 *
 * - El perfil (doctor, clínica o paciente) cambia su campo `active`.
 * - Al desactivar una CLÍNICA se desactivan también sus médicos propios (clinic_doctor.relationship = 'own'),
 *   dejando en cada uno la marca `suspended_by_clinic_id`. Al reactivar la clínica vuelven SOLO los que ella apagó:
 *   un médico que el admin había suspendido por otro motivo no se revive. Los médicos vinculados ('invited') no se tocan.
 * - Las citas futuras NO se cancelan: se informa cuántas hay para que el admin decida (con su reembolso).
 */
final class AccountStatusService
{
    /**
     * @return array{name:string,active:bool,cascaded:int,upcoming:int,upcoming_paid:int}|null  null si la cuenta no tiene perfil
     */
    public function toggle(User $user): ?array
    {
        $profile = match ($user->role) {
            'doctor'  => $user->doctor,
            'clinic'  => $user->clinic,
            'patient' => $user->patient,
            default   => null,
        };

        if (!$profile) {
            return null;
        }

        $activate       = !$profile->active;
        $affectedUsers  = [(int) $user->id];
        $cascaded       = 0;
        $ownDoctorIds   = [];

        DB::transaction(function () use ($user, $profile, $activate, &$affectedUsers, &$cascaded, &$ownDoctorIds) {
            $profile->update(['active' => $activate]);

            if ($user->role === 'clinic') {
                $ownDoctorIds = DB::table('clinic_doctor')
                    ->where('clinic_id', $profile->id)
                    ->where('relationship', 'own')
                    ->pluck('doctor_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (!$activate) {
                    $targets = Doctor::whereIn('id', $ownDoctorIds)->where('active', true)->get(['id', 'user_id']);
                    if ($targets->isNotEmpty()) {
                        Doctor::whereIn('id', $targets->pluck('id'))
                            ->update(['active' => false, 'suspended_by_clinic_id' => $profile->id]);
                    }
                } else {
                    $targets = Doctor::where('suspended_by_clinic_id', $profile->id)->get(['id', 'user_id']);
                    if ($targets->isNotEmpty()) {
                        Doctor::whereIn('id', $targets->pluck('id'))
                            ->update(['active' => true, 'suspended_by_clinic_id' => null]);
                    }
                }

                $cascaded      = $targets->count();
                $affectedUsers = array_merge($affectedUsers, $targets->pluck('user_id')->map(fn ($i) => (int) $i)->all());
            } elseif ($user->role === 'doctor' && $activate) {
                // Reactivación manual del médico: ya no depende de la clínica.
                Doctor::whereKey($profile->id)->update(['suspended_by_clinic_id' => null]);
            }
        });

        foreach ($affectedUsers as $id) {
            Cache::forget('account_active:' . $id); // el middleware se entera de inmediato
        }

        [$upcoming, $upcomingPaid] = (!$activate && in_array($user->role, ['doctor', 'clinic'], true))
            ? $this->upcomingCounts($user->role, (int) $profile->id, $ownDoctorIds)
            : [0, 0];

        Log::info('admin.account_status', [
            'admin_id' => auth()->id(), 'user_id' => $user->id, 'role' => $user->role,
            'active' => $activate, 'cascaded' => $cascaded, 'upcoming' => $upcoming,
        ]);

        return [
            'name'          => (string) $user->name,
            'active'        => $activate,
            'cascaded'      => $cascaded,
            'upcoming'      => $upcoming,
            'upcoming_paid' => $upcomingPaid,
        ];
    }

    /** @return array{0:int,1:int} citas futuras activas y cuántas están pagadas en línea */
    private function upcomingCounts(string $role, int $profileId, array $ownDoctorIds): array
    {
        $today = now(config('app.timezone', 'America/Bogota'))->toDateString();

        $base = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('date', '>=', $today)
            ->where(function ($w) use ($role, $profileId, $ownDoctorIds) {
                if ($role === 'clinic') {
                    $w->where('clinic_id', $profileId);
                    if ($ownDoctorIds) {
                        $w->orWhereIn('doctor_id', $ownDoctorIds);
                    }
                } else {
                    $w->where('doctor_id', $profileId);
                }
            });

        return [
            (clone $base)->count(),
            (clone $base)->where('payment_status', PaymentStatus::PAID->value)->count(),
        ];
    }
}
