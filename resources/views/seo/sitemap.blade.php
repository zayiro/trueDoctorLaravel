{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    
    <!-- 1. PÁGINAS ESTÁTICAS PRINCIPALES -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>    
    <url>
        <loc>{{ url('/register-options') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/login') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ url('/medical-analysis') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/medical-analysis/upload') }}</loc>
        <lastmod>2026-05-21T00:00:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ url('/plans/show') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/contact') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>    
    <url>
        <loc>{{ url('/about') }}</loc>
        <lastmod>{{ now()->startOfDay()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
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
        <priority>0.4</priority>
    </url>

    <url>
        <loc>{{ url('/sintomas') }}</loc>
        <lastmod>2026-05-21T00:00:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- 3. PERFILES DINÁMICOS DE LOS DOCTORES -->
    @foreach($doctors as $doctor)
        <url>            
            <loc>{{ route('partner.public.profile', ['slug' => $doctor->slug]) }}</loc>
            <lastmod>{{ $doctor->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    @endforeach

    <!-- 4. PERFILES DINÁMICOS DE LAS CLÍNICAS (NUEVO) -->
    @foreach($clinics as $clinic)
        <url>            
            <loc>{{ route('partner.public.profile', ['slug' => $clinic->slug]) }}</loc>
            <lastmod>{{ $clinic->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    @endforeach
    
    <!-- 5. LANDINGS DINÁMICAS DE SÍNTOMAS INDEXADOS -->
    @foreach($indexedSymptoms as $symptom)
        <url>
            <loc>{{ route('symptom.landing', $symptom->slug) }}</loc>
            <lastmod>{{ $symptom->updated_at->tz('UTC')->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

    <!-- 6. LANDINGS ESPECIALIDADES -->
    @foreach($specialties as $specialty)
        <url>            
            <loc>{{ url('/search') }}?specialty={{ $specialty->slug }}</loc>
            <lastmod>{{ $specialty->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    @endforeach
</urlset>
