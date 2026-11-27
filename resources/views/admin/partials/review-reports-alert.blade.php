@php
    $rrPending = rescue(fn () => \App\Models\ReviewReport::pendingReviewsCount(), 0);
@endphp

@if ($rrPending > 0)
    <style>
        .rra-box { margin: .75rem; padding: 1rem 1.25rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .rra-box p { margin: 0; color: #92400e; font-size: .9rem; }
        .rra-box b { font-weight: 800; }
        .rra-btn { background: #d97706; color: #fff; font-weight: 700; font-size: .85rem; padding: .5rem 1rem; border-radius: .6rem; text-decoration: none; white-space: nowrap; }
        .rra-btn:hover { background: #b45309; }
    </style>
    <div class="rra-box" role="alert">
        <p>🚩 <b>{{ $rrPending }} {{ $rrPending === 1 ? 'reseña reportada espera' : 'reseñas reportadas esperan' }} tu revisión.</b> Decide si se mantienen o se ocultan del sitio.</p>
        <a class="rra-btn" href="{{ route('administrator.review-reports.index') }}">Revisar reseñas</a>
    </div>
@endif
