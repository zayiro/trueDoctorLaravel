{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<!-- Corrección del espacio de nombres estándar oficial -->
<urlset xmlns="http://sitemaps.org">
    
    <!-- 1. PÁGINAS ESTÁTICAS PRINCIPALES -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('search') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- 2. PÁGINAS LEGALES E INFORMATIVAS -->
    <url>
        <loc>{{ url('/terms') }}</loc>
        <lastmod>2026-05-21T00:00:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/privacy') }}</loc>
        <lastmod>2026-05-21T00:00:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/support') }}</loc>
        <lastmod>2026-05-21T00:00:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- 3. PERFILES DINÁMICOS DE LOS DOCTORES -->
    @foreach($doctors as $doctor)
        <url>
            <loc>{{ route('partner.public.profile', ['partner' => $doctor->slug]) }}</loc>
            <lastmod>{{ $doctor->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    
    @foreach($indexedSymptoms as $symptom)
        <url>
            <loc>{{ route('symptoms.landing', $symptom->slug) }}</loc>
            <lastmod>{{ $symptom->updated_at->tz('UTC')->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

</urlset>
