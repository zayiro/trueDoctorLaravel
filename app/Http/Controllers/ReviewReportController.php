<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Services\Reviews\ReviewModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewReportController extends Controller
{
    public function store(Request $request, Review $review, ReviewModeration $moderation): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', Rule::in(array_keys(ReviewReport::REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
        ], [
            'reason.required' => 'Elige el motivo del reporte.',
            'reason.in' => 'Elige el motivo del reporte.',
            'details.max' => 'La explicación no puede superar los 500 caracteres.',
        ]);

        [$ok, $message] = $moderation->report($request->user(), $review, $data['reason'], $data['details'] ?? null);

        return back()
            ->with($ok ? 'success' : 'error', $message)
            ->withFragment('resenas');
    }
}
