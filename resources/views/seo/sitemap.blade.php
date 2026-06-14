{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<!-- 🔒 CORREGIDO: Espacio de nombres oficial de sitemaps.org para evitar rechazos en Google -->
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
            <!-- 🔒 CORREGIDO: Cambiado 'partner' por 'slug' en el parámetro de la ruta -->
            <loc>{{ route('partner.public.profile', ['slug' => $doctor->slug]) }}</loc>
            <lastmod>{{ $doctor->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    <!-- 4. PERFILES DINÁMICOS DE LAS CLÍNICAS (NUEVO) -->
    @foreach($clinics as $clinic)
        <url>
            <!-- 🔒 MULTI-TENANT: Indexación unificada usando la firma 'slug' del centro médico -->
            <loc>{{ route('partner.public.profile', ['slug' => $clinic->slug]) }}</loc>
            <lastmod>{{ $clinic->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
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

</urlset>
