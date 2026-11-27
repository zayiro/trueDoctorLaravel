@php
    $reviews = $reviews ?? \App\Services\Home\HomeReviews::pick();
    $minToShow = (int) config('testimonials.min_to_show', 3);
    $accents = ['#2563eb', '#16a34a', '#9333ea', '#ea580c'];
@endphp

{{-- Opiniones de pacientes: carrusel con orden al azar en cada carga. Se oculta solo si hay muy pocas opiniones reales. --}}
@if (count($reviews) >= $minToShow)
    <section class="py-10 px-4 bg-gray-50" aria-labelledby="hr-title">
        <style>
            .hr-wrap { max-width: 72rem; margin: 0 auto; padding-bottom: 1.25rem; }
            .hr-track { display: flex; gap: 1.5rem; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; scrollbar-width: none; padding: .5rem .25rem 1.25rem; -webkit-overflow-scrolling: touch; }
            .hr-track::-webkit-scrollbar { display: none; }
            .hr-card { flex: 0 0 100%; scroll-snap-align: start; background: #fff; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1); border-left: 4px solid #2563eb; padding: 2rem; display: flex; flex-direction: column; min-width: 0; }
            @media (min-width: 768px) { .hr-card { flex-basis: calc((100% - 1.5rem) / 2); } }
            @media (min-width: 1024px) { .hr-card { flex-basis: calc((100% - 3rem) / 3); } }
            .hr-stars { display: flex; gap: .25rem; margin-bottom: 1rem; }
            .hr-stars svg { width: 1.25rem; height: 1.25rem; }
            .hr-text { color: #374151; font-style: italic; margin: 0 0 1rem; display: -webkit-box; -webkit-line-clamp: 7; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
            .hr-name { font-weight: 900; color: #111827; margin: auto 0 0; }
            .hr-label { font-size: .875rem; color: #6b7280; margin: 0; }
            .hr-nav { display: flex; justify-content: center; gap: .75rem; }
            .hr-btn { width: 2.75rem; height: 2.75rem; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff; color: #334155; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,.06); transition: background .15s, color .15s; }
            .hr-btn:hover { background: #2563eb; color: #fff; }
            .hr-btn:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
        </style>

        <div class="hr-wrap"
             x-data="{
                 paused: false,
                 step(dir) {
                     const t = $refs.track;
                     if (dir > 0 && t.scrollLeft + t.clientWidth >= t.scrollWidth - 4) { t.scrollTo({ left: 0 }); return; }
                     if (dir < 0 && t.scrollLeft <= 4) { t.scrollTo({ left: t.scrollWidth }); return; }
                     t.scrollBy({ left: dir * (t.querySelector('.hr-card').offsetWidth + 24) });
                 }
             }"
             x-init="if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) { setInterval(() => { if (!paused && !document.hidden) { step(1); } }, 7000); }"
             x-on:mouseenter="paused = true" x-on:mouseleave="paused = false"
             x-on:focusin="paused = true" x-on:focusout="paused = false"
             x-on:touchstart.passive="paused = true">
            <h2 id="hr-title" class="text-5xl lg:text-7xl text-center font-black text-slate-900 tracking-tight mb-10">
                Lo que dicen nuestros
                <span class="text-blue-600">pacientes</span>
            </h2>

            <div class="hr-track" x-ref="track" role="region" aria-roledescription="carrusel" aria-label="Opiniones de pacientes" tabindex="0">
                @foreach ($reviews as $r)
                    <figure class="hr-card" style="border-left-color: {{ $accents[$loop->index % count($accents)] }}; margin: 0">
                        <div class="hr-stars" role="img" aria-label="{{ $r['rating'] }} de 5 estrellas">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg viewBox="0 0 20 20" fill="{{ $i <= $r['rating'] ? '#facc15' : '#e2e8f0' }}" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <blockquote class="hr-text" title="{{ $r['text'] }}">“{{ $r['text'] }}”</blockquote>
                        <figcaption>
                            <p class="hr-name">{{ $r['name'] }}</p>
                            <p class="hr-label">{{ $r['label'] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            <div class="hr-nav">
                <button type="button" class="hr-btn" aria-label="Opinión anterior" x-on:click="step(-1)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" class="hr-btn" aria-label="Opinión siguiente" x-on:click="step(1)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </section>
@endif
