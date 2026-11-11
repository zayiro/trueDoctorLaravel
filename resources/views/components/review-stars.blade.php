@props(['rating' => 0, 'size' => 'w-4 h-4'])

<div class="flex items-center gap-0.5" role="img" aria-label="{{ $rating }} de 5 estrellas">
    @foreach (range(1, 5) as $i)
        <svg class="{{ $size }} fill-current {{ $i <= round($rating) ? 'text-amber-400' : 'text-slate-200' }}" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
        </svg>
    @endforeach
</div>