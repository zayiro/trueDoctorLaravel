<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, ReviewService $reviews): RedirectResponse
    {
        $user       = $request->user();
        $reviewable = $request->reviewable();

        if (! $reviews->hasCompletedAppointment($user, $reviewable)) {
            return back()
                ->with('error', 'Solo puedes calificar después de asistir a tu cita.')
                ->withFragment('resenas');
        }

        if ($reviews->alreadyReviewed($user, $reviewable)) {
            return back()
                ->with('error', 'Ya has dejado una reseña para este perfil.')
                ->withFragment('resenas');
        }

        try {
            $reviewable->reviews()->create([
                'user_id' => $user->id,
                'rating'  => $request->integer('rating'),
                'comment' => $request->validated('comment'),
                'status'  => Review::STATUS_PUBLISHED,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Doble clic / doble pestaña: el índice único ya lo bloqueó
            return back()
                ->with('error', 'Ya has dejado una reseña para este perfil.')
                ->withFragment('resenas');
        }

        $reviews->refreshAggregates($reviewable);

        return back()
            ->with('success', 'Reseña publicada con éxito.')
            ->withFragment('resenas');
    }
}