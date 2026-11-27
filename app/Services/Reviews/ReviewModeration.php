<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Reportar una reseña y moderarla. Nada se oculta solo: un reporte solo avisa; decide el administrador.
 */
final class ReviewModeration
{
    /** Máximo de reportes que una persona puede enviar en 24 horas (evita inundar la cola). */
    public const DAILY_LIMIT = 20;

    public function __construct(private readonly ReviewService $reviews)
    {
    }

    /** @return array{0: bool, 1: string} [ok, mensaje para mostrar] */
    public function report(User $user, Review $review, string $reason, ?string $details): array
    {
        if ($user->role === 'admin') {
            return [false, 'Como administrador puedes revisar y ocultar las reseñas desde el panel de administración.'];
        }

        if (!array_key_exists($reason, ReviewReport::REASONS)) {
            return [false, 'Elige el motivo del reporte.'];
        }

        if ($review->status !== Review::STATUS_PUBLISHED) {
            return [false, 'Esta reseña ya no está publicada.'];
        }

        if ((int) $review->user_id === (int) $user->id) {
            return [false, 'No puedes reportar tu propia reseña.'];
        }

        if (ReviewReport::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDay())->count() >= self::DAILY_LIMIT) {
            return [false, 'Has enviado muchos reportes hoy. Intenta de nuevo mañana.'];
        }

        $details = $details !== null ? trim(mb_substr($details, 0, 500)) : null;

        try {
            ReviewReport::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'details' => $details !== '' ? $details : null,
                'status' => ReviewReport::STATUS_PENDING,
            ]);
        } catch (UniqueConstraintViolationException) {
            return [false, 'Ya reportaste esta reseña. El equipo la revisará pronto.'];
        }

        return [true, 'Gracias. Recibimos tu reporte y el equipo de OpenDoctor revisará la reseña.'];
    }

    /**
     * El administrador decide sobre una reseña: «keep» la mantiene, «hide» la oculta del sitio.
     * Cierra todos los reportes pendientes de esa reseña. Devuelve cuántos cerró.
     */
    public function resolve(User $admin, Review $review, string $decision, ?string $note): int
    {
        if (!in_array($decision, ['keep', 'hide'], true)) {
            throw new \InvalidArgumentException('Decisión no válida.');
        }

        $note = $note !== null ? trim(mb_substr($note, 0, 500)) : null;

        $closed = DB::transaction(function () use ($admin, $review, $decision, $note) {
            $pending = ReviewReport::query()->pending()->where('review_id', $review->id)->lockForUpdate()->get();

            if ($pending->isEmpty()) {
                return 0;
            }

            if ($decision === 'hide') {
                $review->forceFill(['status' => Review::STATUS_HIDDEN])->save();
            }

            ReviewReport::query()->whereIn('id', $pending->pluck('id'))->update([
                'status' => $decision === 'hide' ? ReviewReport::STATUS_UPHELD : ReviewReport::STATUS_DISMISSED,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'resolution_note' => $note !== '' ? $note : null,
                'updated_at' => now(),
            ]);

            return $pending->count();
        });

        if ($closed > 0 && $decision === 'hide') {
            $this->refresh($review);
        }

        return $closed;
    }

    /** Vuelve a publicar una reseña que se había ocultado por reportes. */
    public function restore(User $admin, Review $review): bool
    {
        if ($review->status !== Review::STATUS_HIDDEN) {
            return false;
        }

        DB::transaction(function () use ($admin, $review) {
            $review->forceFill(['status' => Review::STATUS_PUBLISHED])->save();

            ReviewReport::query()->where('review_id', $review->id)->where('status', ReviewReport::STATUS_UPHELD)->update([
                'status' => ReviewReport::STATUS_REVERSED,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->refresh($review);

        return true;
    }

    /** Recalcula promedio y total del doctor o la clínica (solo cuentan las reseñas publicadas). */
    private function refresh(Review $review): void
    {
        $target = $review->reviewable;

        if ($target) {
            $this->reviews->refreshAggregates($target);
        }
    }
}
