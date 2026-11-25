<?php

namespace App\Services\Plans;

use App\Models\Doctor;
use App\Models\DoctorSetting;
use App\Models\Plan;
use App\Models\PlanEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reglas de planes de suscripción (médicos). Un único lugar para decidir:
 *  - qué cambios de plan se permiten (subir sí; bajar solo cuando termina el tiempo pagado),
 *  - cuándo se puede renovar (últimos 7 días, sumando desde el vencimiento),
 *  - cómo se activa un pago aprobado y cómo se baja un plan vencido.
 *
 * Plan vigente = suscripción aprobada con ends_at futuro. Un plan de pago sin ninguna suscripción
 * aprobada en su historial es un plan asignado por OpenDoctor (cortesía) y no vence.
 */
final class PlanRules
{
    public const RENEW_WINDOW_DAYS = 7;
    public const PERIOD_DAYS = 30;
    /** Días que el médico conserva su plan después del vencimiento antes de bajar al gratuito (config plans.grace_days). */
    public const GRACE_DAYS = 5;
    /** Estado del vínculo clínica-especialista cuando el plan de la clínica ya no alcanza (vuelve a approved al renovar). */
    public const CLINIC_SUSPENDED = 'plan_suspended';

    public function graceDays(): int
    {
        return max(0, (int) config('plans.grace_days', self::GRACE_DAYS));
    }

    /** Planes de 6 meses o más se pueden renovar con más anticipación (config plans.renew_window_days); los cortos, 7 días. */
    public function renewWindowDays(?Plan $plan): int
    {
        return ($plan && $plan->durationDays() >= 180)
            ? max(1, (int) config('plans.renew_window_days', 30))
            : self::RENEW_WINDOW_DAYS;
    }

    /** Última suscripción aprobada del usuario (la de vencimiento más lejano). */
    public function lastApproved(int $userId): ?Subscription
    {
        return Subscription::where('doctor_id', $userId)->where('status', 'approved')->orderByDesc('ends_at')->first();
    }

    public function doctorOf(User $user): ?Doctor
    {
        return Doctor::where('user_id', $user->id)->first();
    }

    public function currentPlan(?Doctor $doctor): ?Plan
    {
        return $doctor?->settings?->plan;
    }

    public function activeSubscription(int $userId, ?int $exceptId = null): ?Subscription
    {
        return Subscription::where('doctor_id', $userId)
            ->where('status', 'approved')
            ->where('ends_at', '>', now())
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->orderByDesc('ends_at')
            ->first();
    }

    public function hasPaidHistory(int $userId): bool
    {
        return Subscription::where('doctor_id', $userId)->where('status', 'approved')->exists();
    }

    private function fmt($date): string
    {
        return $date->copy()->timezone('America/Bogota')->format('d/m/Y');
    }

    /** A dónde mandar al usuario cuando un cambio de plan se rechaza. */
    public function homeFor(?User $user): string
    {
        return ($user && $user->role === 'doctor') ? url('/partner/profile') : route('plans.index');
    }

    /**
     * @return array{ok:bool, code:string, kind:?string, message:string}
     */
    public function evaluate(User $user, Plan $target): array
    {
        $ok = fn (string $kind) => ['ok' => true, 'code' => 'ok', 'kind' => $kind, 'message' => ''];
        $no = fn (string $code, string $msg) => ['ok' => false, 'code' => $code, 'kind' => null, 'message' => $msg];

        if (!$target->active) {
            return $no('inactive', 'Este plan ya no está disponible.');
        }
        if ($target->applicable_role && $target->applicable_role !== $user->role) {
            return $no('role', 'Este plan no corresponde a tu tipo de cuenta.');
        }

        $doctor = $user->role === 'doctor' ? $this->doctorOf($user) : null;
        if (!$doctor) {
            // Otros roles (clínicas): solo se valida estado y rol; no hay lógica de bajada de plan.
            if ((float) $target->price <= 0) {
                return $no('free_not_payable', 'Este plan no se contrata desde aquí.');
            }
            $clinicSub = $this->activeSubscription($user->id);
            if ($clinicSub && (int) $clinicSub->plan_id === (int) $target->id) {
                $opens = $clinicSub->ends_at->copy()->subDays($this->renewWindowDays($target));
                if (now()->lt($opens)) {
                    return $no('same_active', "Tu {$target->name} está vigente hasta el {$this->fmt($clinicSub->ends_at)}. Podrás renovarlo a partir del {$this->fmt($opens)}.");
                }

                return $ok('renew');
            }

            return $ok('purchase');
        }

        $current = $this->currentPlan($doctor);
        $currentPrice = (float) ($current->price ?? 0);
        $targetPrice = (float) $target->price;
        $sub = $this->activeSubscription($user->id);

        if ($current && $current->id === $target->id) {
            if ($targetPrice <= 0) {
                return $no('same_free', "Ya estás en el {$target->name}.");
            }
            if (!$sub) {
                if (!$this->hasPaidHistory($user->id)) {
                    return $no('manual_same', "Tu {$target->name} fue asignado por OpenDoctor y no requiere pago.");
                }

                return $ok('renew'); // venció y todavía no se ha bajado
            }
            $opens = $sub->ends_at->copy()->subDays($this->renewWindowDays($target));
            if (now()->lt($opens)) {
                return $no('same_active', "Tu {$target->name} está vigente hasta el {$this->fmt($sub->ends_at)}. Podrás renovarlo a partir del {$this->fmt($opens)}.");
            }

            return $ok('renew');
        }

        if ($targetPrice > $currentPrice) {
            return $ok('upgrade');
        }

        // Bajar de plan: solo cuando termina el tiempo pagado.
        if ($sub && $currentPrice > 0) {
            $name = $current->name ?? 'plan actual';

            return $no('downgrade_blocked', "Tu {$name} sigue vigente hasta el {$this->fmt($sub->ends_at)}. Cuando termine podrás pasar al {$target->name}.");
        }

        return $ok($targetPrice <= 0 ? 'downgrade_free' : 'downgrade');
    }

    /** Texto para mostrar en el perfil. */
    public function statusFor(?User $user): ?array
    {
        if (!$user || $user->role !== 'doctor') {
            return null;
        }
        $doctor = $this->doctorOf($user);
        $plan = $this->currentPlan($doctor);
        if (!$plan || (float) $plan->price <= 0) {
            return null;
        }
        $sub = $this->activeSubscription($user->id);
        if ($sub) {
            $days = max(0, (int) ceil(($sub->ends_at->timestamp - now()->timestamp) / 86400));

            return ['state' => 'paid', 'days' => $days, 'text' => "{$plan->name}: vigente hasta el {$this->fmt($sub->ends_at)} (" . ($days === 1 ? 'queda 1 día' : "quedan {$days} días") . ').'];
        }
        if (!$this->hasPaidHistory($user->id)) {
            return ['state' => 'manual', 'days' => null, 'text' => "{$plan->name}: asignado por OpenDoctor, sin fecha de vencimiento."];
        }

        $last = $this->lastApproved($user->id);
        if ($last) {
            $graceEnd = $last->ends_at->copy()->addDays($this->graceDays());
            if (now()->lt($graceEnd)) {
                $left = max(0, (int) ceil(($graceEnd->timestamp - now()->timestamp) / 86400));

                return ['state' => 'grace', 'days' => $left, 'text' => "Tu {$plan->name} venció el {$this->fmt($last->ends_at)}. Tienes hasta el {$this->fmt($graceEnd)} para renovarlo sin perder tus sedes; después pasarás al plan gratuito."];
            }
        }

        return ['state' => 'expired', 'days' => 0, 'text' => "Tu {$plan->name} ya venció; en las próximas horas pasarás al plan gratuito. Renueva para conservarlo."];
    }

    /** Periodo que cubre un pago aprobado: si renueva el mismo plan vigente, suma desde su vencimiento. */
    public function periodFor(Subscription $s): array
    {
        $active = Subscription::where('doctor_id', $s->doctor_id)
            ->where('status', 'approved')
            ->where('plan_id', $s->plan_id)
            ->where('ends_at', '>', now())
            ->where('id', '!=', $s->id)
            ->orderByDesc('ends_at')
            ->first();
        $start = $active ? $active->ends_at->copy() : now();

        $days = max(1, (int) (Plan::where('id', $s->plan_id)->value('duration_days') ?: self::PERIOD_DAYS));

        return [$start, $start->copy()->addDays($days)];
    }

    public function clinicIdOf(int $userId): ?int
    {
        $id = DB::table('clinics')->where('user_id', $userId)->value('id');

        return $id ? (int) $id : null;
    }

    public function currentClinicPlan(int $clinicId): ?Plan
    {
        $planId = DB::table('clinic_settings')->where('clinic_id', $clinicId)->value('plan_id');

        return $planId ? Plan::find($planId) : null;
    }

    /**
     * Pasa la clínica al plan gratuito. No se borra nada: se desactivan las sedes físicas y se pausan los
     * especialistas que superan el límite del plan (los más recientes). La sede virtual no cuenta.
     *
     * @return array{addresses_off:int, doctors_off:int}
     */
    public function downgradeClinicToFree(int $clinicId, int $userId, Plan $free, string $reason, string $type = 'downgraded'): array
    {
        $fromPlanId = DB::table('clinic_settings')->where('clinic_id', $clinicId)->value('plan_id');

        $summary = DB::transaction(function () use ($clinicId, $free) {
            DB::table('clinic_settings')->where('clinic_id', $clinicId)->update(['plan_id' => $free->id, 'updated_at' => now()]);

            $addressIds = DB::table('addresses')->where('clinic_id', $clinicId)->whereNull('doctor_id')->where('type', 'physical')
                ->whereNull('deleted_at')->where('status', 1)->orderBy('created_at')->orderBy('id')->pluck('id');
            $addressesOff = $addressIds->slice(max(0, (int) $free->max_addresses))->values();
            if ($addressesOff->isNotEmpty()) {
                DB::table('addresses')->whereIn('id', $addressesOff->all())->update(['status' => 0, 'updated_at' => now()]);
            }

            $linkIds = DB::table('clinic_doctor')->where('clinic_id', $clinicId)->where('status', 'approved')->orderBy('id')->pluck('id');
            $doctorsOff = $linkIds->slice(max(0, (int) $free->max_doctors))->values();
            if ($doctorsOff->isNotEmpty()) {
                DB::table('clinic_doctor')->whereIn('id', $doctorsOff->all())->update(['status' => self::CLINIC_SUSPENDED, 'updated_at' => now()]);
            }

            return ['addresses_off' => $addressesOff->count(), 'doctors_off' => $doctorsOff->count(), 'address_ids' => $addressesOff->all()];
        });

        Log::info('plans.clinic_downgrade', ['clinic_id' => $clinicId, 'from_plan_id' => $fromPlanId, 'to_plan_id' => $free->id, 'reason' => $reason, 'addresses_off' => $summary['addresses_off'], 'doctors_off' => $summary['doctors_off']]);

        PlanEvent::record($type, $userId, [
            'from_plan_id' => $fromPlanId ? (int) $fromPlanId : null,
            'to_plan_id'   => (int) $free->id,
            'reason'       => $reason,
            'meta'         => ['addresses_off' => $summary['addresses_off'], 'doctors_off' => $summary['doctors_off'], 'addresses_off_ids' => $summary['address_ids']],
        ]);

        return ['addresses_off' => $summary['addresses_off'], 'doctors_off' => $summary['doctors_off']];
    }

    /**
     * Al activar un plan con más cupo, vuelven lo que la bajada había desactivado: los especialistas pausados y las
     * sedes de la última bajada, hasta el límite del plan.
     *
     * @return array{doctors:int, addresses:int}
     */
    public function restoreClinicOverflow(int $clinicId, int $userId, Plan $plan): array
    {
        $doctors = 0;
        $addresses = 0;

        $approved = DB::table('clinic_doctor')->where('clinic_id', $clinicId)->where('status', 'approved')->count();
        $room = max(0, (int) $plan->max_doctors - $approved);
        if ($room > 0) {
            $ids = DB::table('clinic_doctor')->where('clinic_id', $clinicId)->where('status', self::CLINIC_SUSPENDED)->orderBy('id')->limit($room)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('clinic_doctor')->whereIn('id', $ids->all())->update(['status' => 'approved', 'updated_at' => now()]);
                $doctors = $ids->count();
            }
        }

        $event = PlanEvent::where('user_id', $userId)->whereIn('type', ['expired', 'downgraded'])->orderByDesc('id')->first();
        $offIds = (array) (($event?->meta ?? [])['addresses_off_ids'] ?? []);
        if ($offIds) {
            $active = DB::table('addresses')->where('clinic_id', $clinicId)->whereNull('doctor_id')->where('type', 'physical')->whereNull('deleted_at')->where('status', 1)->count();
            $room = max(0, (int) $plan->max_addresses - $active);
            if ($room > 0) {
                $ids = DB::table('addresses')->whereIn('id', $offIds)->where('clinic_id', $clinicId)->whereNull('deleted_at')->where('status', 0)
                    ->orderBy('created_at')->orderBy('id')->limit($room)->pluck('id');
                if ($ids->isNotEmpty()) {
                    DB::table('addresses')->whereIn('id', $ids->all())->update(['status' => 1, 'updated_at' => now()]);
                    $addresses = $ids->count();
                }
            }
        }

        if ($doctors || $addresses) {
            Log::info('plans.clinic_restore', ['clinic_id' => $clinicId, 'plan_id' => $plan->id, 'doctors' => $doctors, 'addresses' => $addresses]);
        }

        return ['doctors' => $doctors, 'addresses' => $addresses];
    }

    /** Deja el plan del médico igual al de la suscripción aprobada. */
    public function activatePlan(Subscription $s): bool
    {
        $doctorId = Doctor::where('user_id', $s->doctor_id)->value('id');
        if (!$doctorId) {
            // Clínica: su plan vive en clinic_settings.
            $clinicId = DB::table('clinics')->where('user_id', $s->doctor_id)->value('id');
            if ($clinicId && DB::table('clinic_settings')->where('clinic_id', $clinicId)->exists()) {
                DB::table('clinic_settings')->where('clinic_id', $clinicId)->update(['plan_id' => $s->plan_id, 'updated_at' => now()]);
                try {
                    $clinicPlan = Plan::find($s->plan_id);
                    if ($clinicPlan) {
                        $this->restoreClinicOverflow((int) $clinicId, (int) $s->doctor_id, $clinicPlan);
                    }
                } catch (\Throwable $e) {
                    Log::warning('plans: no se pudo restaurar lo que la bajada había desactivado', ['clinic_id' => $clinicId, 'error' => $e->getMessage()]);
                }

                return true;
            }
        }

        if (!$doctorId) {
            Log::warning('plans: suscripción aprobada sin perfil de médico (¿clínica?)', ['subscription_id' => $s->id, 'user_id' => $s->doctor_id]);

            return false;
        }
        DoctorSetting::where('doctor_id', $doctorId)->update(['plan_id' => $s->plan_id]);

        return true;
    }

    /** Qué pasará al pasar a un plan menor (para la pantalla de confirmación). */
    public function downgradePreview(Doctor $doctor, Plan $plan): array
    {
        $own = DB::table('addresses')->where('doctor_id', $doctor->id)->whereNull('clinic_id')->whereNull('deleted_at')->where('status', 1)->count();
        $services = (int) DB::table('service_specialty')->where('user_id', $doctor->user_id)->distinct()->count('service_id');

        return [
            'addresses_total' => $own,
            'addresses_keep' => min($own, (int) $plan->max_addresses),
            'addresses_off' => max(0, $own - (int) $plan->max_addresses),
            'services_total' => $services,
            'services_limit' => (int) $plan->max_services,
        ];
    }

    /**
     * Pasa al médico al plan gratuito. Las sedes propias más antiguas siguen activas hasta el límite
     * del plan; el resto se desactiva (no se borra nada).
     */
    public function downgradeToFree(Doctor $doctor, Plan $free, string $reason, string $type = 'downgraded'): array
    {
        $fromPlanId = DoctorSetting::where('doctor_id', $doctor->id)->value('plan_id');

        $summary = DB::transaction(function () use ($doctor, $free, $reason) {
            $before = DoctorSetting::where('doctor_id', $doctor->id)->value('plan_id');
            DoctorSetting::where('doctor_id', $doctor->id)->update(['plan_id' => $free->id]);

            $ids = DB::table('addresses')->where('doctor_id', $doctor->id)->whereNull('clinic_id')->whereNull('deleted_at')->where('status', 1)
                ->orderBy('created_at')->orderBy('id')->pluck('id');
            $off = $ids->slice((int) $free->max_addresses)->values();
            if ($off->isNotEmpty()) {
                DB::table('addresses')->whereIn('id', $off->all())->update(['status' => 0, 'updated_at' => now()]);
            }

            $summary = $this->downgradePreview($doctor, $free);
            $summary['addresses_off'] = $off->count();
            Log::info('plans.downgrade', ['doctor_id' => $doctor->id, 'from_plan_id' => $before, 'to_plan_id' => $free->id, 'reason' => $reason, 'addresses_off' => $off->count(), 'services_total' => $summary['services_total'], 'services_limit' => $summary['services_limit']]);

            return $summary;
        });

        // Historial del plan (nunca debe romper la bajada si falla).
        PlanEvent::record($type, (int) $doctor->user_id, [
            'from_plan_id' => $fromPlanId ? (int) $fromPlanId : null,
            'to_plan_id'   => (int) $free->id,
            'reason'       => $reason,
            'meta'         => ['addresses_off' => (int) $summary['addresses_off']],
        ]);

        return $summary;
    }
}
