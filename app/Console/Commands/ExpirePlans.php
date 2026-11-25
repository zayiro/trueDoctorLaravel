<?php

namespace App\Console\Commands;

use App\Mail\PlanExpiredMail;
use App\Mail\PlanExpiringMail;
use App\Mail\PlanGraceMail;
use App\Models\Doctor;
use App\Models\Plan;
use App\Models\PlanEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Plans\PlanRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExpirePlans extends Command
{
    protected $signature = 'plans:expire {--dry-run : Muestra qué haría sin cambiar nada ni enviar correos}';

    protected $description = 'Avisa antes del vencimiento (30, 7 y 1 día en planes anuales; 3 en los cortos), da días de gracia al vencer y baja al plan gratuito cuando la gracia termina';

    public function handle(PlanRules $rules): int
    {
        $dry = (bool) $this->option('dry-run');
        $free = Plan::where('plan', 'free')->where('applicable_role', 'doctor')->first();
        if (!$free) {
            $this->error('No existe el plan gratuito de médicos (plan = free).');

            return self::FAILURE;
        }

        $graceDays = $rules->graceDays();

        // 1) Vencidos: el último periodo aprobado ya terminó y el médico sigue en un plan de pago.
        //    Durante los días de gracia conserva el plan (se le avisa una sola vez); al terminar la gracia baja al gratuito.
        $expired = 0;
        $inGrace = 0;
        foreach (Subscription::where('status', 'approved')->distinct()->pluck('doctor_id') as $uid) {
            try {
                $last = $rules->lastApproved((int) $uid);
                if (!$last || $last->ends_at->gt(now())) {
                    continue;
                }
                $doctor = Doctor::where('user_id', $uid)->first();
                $plan = $rules->currentPlan($doctor);
                if (!$doctor || !$plan || (float) $plan->price <= 0) {
                    continue; // ya está en plan gratuito
                }

                $graceEnd = $last->ends_at->copy()->addDays($graceDays);
                if (now()->lt($graceEnd)) {
                    $inGrace++;
                    $this->line("  [en gracia] usuario {$uid} · {$plan->name} terminó el " . $last->ends_at->format('d/m/Y') . ' · baja el ' . $graceEnd->format('d/m/Y'));
                    if ($dry || PlanEvent::happened('grace', (int) $uid, (int) $last->id)) {
                        continue;
                    }
                    PlanEvent::record('grace', (int) $uid, ['to_plan_id' => (int) $plan->id, 'subscription_id' => (int) $last->id, 'meta' => ['grace_until' => $graceEnd->toDateTimeString()]]);
                    $user = User::find($uid);
                    if ($user && $user->email) {
                        Mail::to($user->email)->send(new PlanGraceMail(
                            (string) $user->name,
                            $plan->name,
                            $last->ends_at->copy()->timezone('America/Bogota')->format('d/m/Y'),
                            $graceEnd->copy()->timezone('America/Bogota')->format('d/m/Y'),
                            url('/partner/profile')
                        ));
                    }
                    continue;
                }

                $expired++;
                $this->line("  [vencido] usuario {$uid} · {$plan->name} terminó el " . $last->ends_at->format('d/m/Y') . " (gracia de {$graceDays} días terminada)");
                if ($dry) {
                    continue;
                }
                $summary = $rules->downgradeToFree($doctor, $free, 'plan vencido', 'expired');
                $user = User::find($uid);
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new PlanExpiredMail((string) $user->name, $plan->name, $free->name, (int) $summary['addresses_off'], url('/partner/profile')));
                }
            } catch (\Throwable $e) {
                Log::error('plans:expire falló para el usuario ' . $uid . ': ' . $e->getMessage());
                $this->error("  Error con el usuario {$uid}: " . $e->getMessage());
            }
        }

        // 1b) Clínicas: mismas reglas que los médicos (gracia y luego plan gratuito de clínica).
        //     Una clínica con plan de pago sin ninguna suscripción aprobada es cortesía y no vence.
        $freeClinic = Plan::where('slug', 'clinic_free')->first();
        if ($freeClinic) {
            foreach (Subscription::where('status', 'approved')->distinct()->pluck('doctor_id') as $uid) {
                try {
                    $clinicId = $rules->clinicIdOf((int) $uid);
                    if (!$clinicId) {
                        continue;
                    }
                    $last = $rules->lastApproved((int) $uid);
                    if (!$last || $last->ends_at->gt(now())) {
                        continue;
                    }
                    $plan = $rules->currentClinicPlan($clinicId);
                    if (!$plan || (float) $plan->price <= 0) {
                        continue; // ya está en plan gratuito
                    }

                    $graceEnd = $last->ends_at->copy()->addDays($graceDays);
                    if (now()->lt($graceEnd)) {
                        $inGrace++;
                        $this->line("  [clínica en gracia] usuario {$uid} · {$plan->name} terminó el " . $last->ends_at->format('d/m/Y') . ' · baja el ' . $graceEnd->format('d/m/Y'));
                        if ($dry || PlanEvent::happened('grace', (int) $uid, (int) $last->id)) {
                            continue;
                        }
                        PlanEvent::record('grace', (int) $uid, ['to_plan_id' => (int) $plan->id, 'subscription_id' => (int) $last->id, 'meta' => ['grace_until' => $graceEnd->toDateTimeString()]]);
                        $user = User::find($uid);
                        if ($user && $user->email) {
                            Mail::to($user->email)->send(new PlanGraceMail(
                                (string) $user->name,
                                $plan->name,
                                $last->ends_at->copy()->timezone('America/Bogota')->format('d/m/Y'),
                                $graceEnd->copy()->timezone('America/Bogota')->format('d/m/Y'),
                                $rules->homeFor($user)
                            ));
                        }
                        continue;
                    }

                    $expired++;
                    $this->line("  [clínica vencida] usuario {$uid} · {$plan->name} terminó el " . $last->ends_at->format('d/m/Y') . " (gracia de {$graceDays} días terminada)");
                    if ($dry) {
                        continue;
                    }
                    $summary = $rules->downgradeClinicToFree($clinicId, (int) $uid, $freeClinic, 'plan vencido', 'expired');
                    $user = User::find($uid);
                    if ($user && $user->email) {
                        Mail::to($user->email)->send(new PlanExpiredMail((string) $user->name, $plan->name, $freeClinic->name, (int) $summary['addresses_off'], $rules->homeFor($user), (int) $summary['doctors_off']));
                    }
                } catch (\Throwable $e) {
                    Log::error('plans:expire (clínica) falló para el usuario ' . $uid . ': ' . $e->getMessage());
                    $this->error("  Error con la clínica del usuario {$uid}: " . $e->getMessage());
                }
            }
        }
        // 2) Por vencer: los planes anuales avisan 30, 7 y 1 día antes; los cortos, 3 días. Una sola vez por suscripción y por aviso.
        $warned = 0;
        $annualDays = array_map('intval', (array) config('plans.warn_days_annual', [30, 7, 1]));
        $shortDays = array_map('intval', (array) config('plans.warn_days_short', [3]));
        foreach (array_unique(array_merge($annualDays, $shortDays)) as $daysLeft) {
            $from = now()->addDays($daysLeft)->startOfDay();
            $to = now()->addDays($daysLeft)->endOfDay();
            foreach (Subscription::where('status', 'approved')->whereBetween('ends_at', [$from, $to])->get() as $sub) {
                try {
                    $subPlan = $sub->plan;
                    $annual = $subPlan && $subPlan->durationDays() >= 180;
                    if (!in_array($daysLeft, $annual ? $annualDays : $shortDays, true)) {
                        continue;
                    }
                    if (Subscription::where('doctor_id', $sub->doctor_id)->where('status', 'approved')->max('ends_at') > $sub->ends_at->toDateTimeString()) {
                        continue; // ya renovó: hay un periodo posterior
                    }
                    $user = User::find($sub->doctor_id);
                    if (!$user || !$user->email || $user->role !== 'doctor') {
                        continue; // el vencimiento automático solo aplica a médicos
                    }
                    $warned++;
                    $planName = optional($subPlan)->name ?? 'plan';
                    $this->line("  [aviso {$daysLeft} días] usuario {$user->id} · {$planName} vence el " . $sub->ends_at->format('d/m/Y'));
                    if ($dry || !Cache::add("plan-expiry-warn:{$sub->id}:{$daysLeft}", 1, now()->addDays(3))) {
                        continue;
                    }
                    Mail::to($user->email)->send(new PlanExpiringMail(
                        (string) $user->name,
                        $planName,
                        $sub->ends_at->copy()->timezone('America/Bogota')->format('d/m/Y'),
                        $rules->homeFor($user),
                        $daysLeft,
                        $subPlan ? $subPlan->durationLabel() : '30 días',
                    ));
                } catch (\Throwable $e) {
                    Log::error('plans:expire (aviso) falló para la suscripción ' . $sub->id . ': ' . $e->getMessage());
                }
            }
        }

        $this->info(($dry ? 'DRY-RUN: ' : '') . "{$expired} plan(es) bajado(s), {$inGrace} en gracia, {$warned} aviso(s) de vencimiento.");

        return self::SUCCESS;
    }
}
