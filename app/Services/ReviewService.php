<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ReviewService
{
    private const SUMMARY_TTL = 600; // 10 min

    /** ¿Puede este usuario dejar una reseña ahora mismo? (para mostrar el formulario) */
    public function canReview(?User $user, Model $reviewable): bool
    {
        return $user !== null
            && $this->hasCompletedAppointment($user, $reviewable)
            && ! $this->alreadyReviewed($user, $reviewable);
    }

    public function hasCompletedAppointment(User $user, Model $reviewable): bool
    {
        if ($user->role !== 'patient') {
            return false;
        }

        return Appointment::query()
            ->where($this->appointmentColumn($reviewable), $reviewable->getKey())
            ->where('status', 'completed')
            // VERIFICAR: si appointments.patient_id guarda users.id, cambia esta línea por
            // ->where('patient_id', $user->id)
            ->whereHas('patient', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function alreadyReviewed(User $user, Model $reviewable): bool
    {
        return $reviewable->reviews()->where('user_id', $user->id)->exists();
    }

    /**
     * @return array{average: float, count: int, distribution: array<int,int>}
     */
    public function summary(Model $reviewable): array
    {
        return Cache::remember($this->summaryKey($reviewable), self::SUMMARY_TTL, function () use ($reviewable) {
            $rows = $reviewable->reviews()
                ->published()
                ->toBase()
                ->selectRaw('rating, COUNT(*) as total')
                ->groupBy('rating')
                ->pluck('total', 'rating');

            $distribution = collect(range(5, 1))
                ->mapWithKeys(fn (int $stars) => [$stars => (int) ($rows[$stars] ?? 0)]);

            $count = $distribution->sum();
            $sum   = $distribution->map(fn (int $total, int $stars) => $total * $stars)->sum();

            return [
                'average'      => $count > 0 ? round($sum / $count, 1) : 0.0,
                'count'        => $count,
                'distribution' => $distribution->all(),
            ];
        });
    }

    /**
     * Recalcula el resumen (solo reseñas publicadas) y actualiza las columnas
     * denormalizadas rating / reviews_count del doctor o la clínica.
     */
    public function refreshAggregates(Model $reviewable): void
    {
        Cache::forget($this->summaryKey($reviewable));

        $summary = $this->summary($reviewable);

        $reviewable->newQueryWithoutScopes()
            ->whereKey($reviewable->getKey())
            ->toBase()
            ->update([
                'rating'        => $summary['average'],
                'reviews_count' => $summary['count'],
            ]);
    }

    private function appointmentColumn(Model $reviewable): string
    {
        return match (true) {
            $reviewable instanceof Doctor => 'doctor_id',   // doctors.id
            $reviewable instanceof Clinic => 'clinic_id',
            default => throw new InvalidArgumentException('Entidad no reseñable: ' . $reviewable::class),
        };
    }

    private function summaryKey(Model $reviewable): string
    {
        return 'reviews.' . class_basename($reviewable) . '.' . $reviewable->getKey() . '.summary';
    }
}