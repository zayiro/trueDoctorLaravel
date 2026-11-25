<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Página principal --}}
    <url>
        <loc>https://opendoctor.online/</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Páginas estáticas públicas --}}
    <url>
        <loc>https://opendoctor.online/search</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Landing de campañas "Doctores cerca de mí" (la variante ?ciudad= NO va aquí: su canonical es esta URL) --}}
    <url>
        <loc>https://opendoctor.online/doctores-cerca-de-mi</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Chat de Salud con IA (página pública de ventas) --}}
    <url>
        <loc>https://opendoctor.online/chat-salud-ia</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/search-symptom</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/medical-analysis</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/skin-analysis</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/contact</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/about</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/terms</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/privacy</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    <url>
        <loc>https://opendoctor.online/plans/show</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    {{-- Página pública de elección de registro (Doctor, Clínica o Paciente) --}}
    <url>
        <loc>https://opendoctor.online/register-options</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>

    {{-- Directorio de síntomas indexados --}}
    <url>
        <loc>https://opendoctor.online/sintomas</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    @foreach($indexedSymptoms as $symptom)
    <url>
        <loc>https://opendoctor.online/sintomas/{{ $symptom->slug }}</loc>
        <lastmod>{{ $symptom->updated_at->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Perfiles públicos de médicos --}}
    @foreach($doctors as $doctor)
    <url>
        <loc>https://opendoctor.online/medical-partner/{{ $doctor->slug }}</loc>
        <lastmod>{{ $doctor->updated_at->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    {{-- Perfiles públicos de clínicas --}}
    @foreach($clinics as $clinic)
    <url>
        <loc>https://opendoctor.online/medical-partner/{{ $clinic->slug }}</loc>
        <lastmod>{{ $clinic->updated_at->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    {{-- Especialidades médicas --}}
    @foreach($specialties as $specialty)
    <url>
        <loc>https://opendoctor.online/search?specialty={{ $specialty->slug }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>
