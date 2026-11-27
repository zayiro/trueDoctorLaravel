<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Services\Reviews\ReviewModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewReportAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $tab = $request->query('tab') === 'resolved' ? 'resolved' : 'pending';
        $with = ['reporter:id,name,role', 'review.user:id,name,email', 'review.reviewable'];

        $pending = ReviewReport::query()->pending()->with($with)->orderBy('created_at')->get()->groupBy('review_id');

        $resolved = $tab === 'resolved'
            ? ReviewReport::query()->where('status', '!=', ReviewReport::STATUS_PENDING)
                ->with(array_merge($with, ['resolver:id,name']))
                ->orderByDesc('resolved_at')->limit(200)->get()->groupBy('review_id')->take(50)
            : collect();

        return view('admin.review-reports.index', [
            'tab' => $tab,
            'pending' => $pending,
            'resolved' => $resolved,
            'reasons' => ReviewReport::REASONS,
        ]);
    }

    public function resolve(Request $request, Review $review, ReviewModeration $moderation): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['keep', 'hide'])],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['decision.required' => 'Elige qué hacer con la reseña.']);

        $closed = $moderation->resolve($request->user(), $review, $data['decision'], $data['note'] ?? null);

        if ($closed === 0) {
            return redirect()->route('administrator.review-reports.index')->with('error', 'Esos reportes ya estaban resueltos.');
        }

        return redirect()->route('administrator.review-reports.index')->with('success', $data['decision'] === 'hide'
            ? 'La reseña se ocultó del sitio y dejó de contar en la calificación.'
            : 'La reseña se mantiene publicada. Se cerraron los reportes.');
    }

    public function restore(Request $request, Review $review, ReviewModeration $moderation): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $ok = $moderation->restore($request->user(), $review);

        return redirect()->route('administrator.review-reports.index', ['tab' => 'resolved'])
            ->with($ok ? 'success' : 'error', $ok ? 'La reseña volvió a publicarse.' : 'Esa reseña no estaba oculta.');
    }
}
