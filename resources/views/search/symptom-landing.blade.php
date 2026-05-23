<x-guest-layout>
    <!-- Inyección exclusiva para el HEAD -->
    <x-slot:seo>
        <meta name="title" content="{{ $seoTitle }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        @php
            // 1. Construimos el arreglo base con datos limpios
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => "MedicalWebPage",
                "name" => $seoTitle,
                "description" => $seoDescription,
                "url" => request()->url(),
                "aspect" => "Análisis de síntomas y derivación médica"                
            ];

            $schemaData["mainContentOfPage"] = [
                "@type" => "WebPageElement",
                "cssSelector" => ".symptom-content"
            ];

            /*$schemaData["reviewedBy"] = [
                "@type" => "Person",
                "name" => "Dr. Nombre del Revisor",
                "jobTitle" => "Médico Especialista en [Especialidad]",
                "sameAs" => "https://openDotor.online"
            ];*/

            $schemaData["medicalAudience"] = [
                "@type" => "MedicalAudience",
                "audienceType" => "Patients"
            ];
        @endphp

        {{-- 3. Renderizado seguro: Evita comillas rotas o saltos de línea destructivos --}}
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    </x-slot:seo>
    
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5 symptom-content">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                
                <!-- Migas de pan (Breadcrumbs) óptimas para el SEO de Google -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb small">
                        <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-muted">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('search.symptom.view') }}" class="text-decoration-none text-muted">Síntomas</a></li>
                        <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 250px;">{{ $symptom->search_query }}</li>
                    </ol>
                </nav>

                <!-- Encabezado Clínico de la Consulta -->
                <div class="mb-4 border-bottom pb-4">
                    <h1 class="fw-black text-dark tracking-tight mb-2 fs-2">
                        Orientación para: <span class="text-primary">"{{ $symptom->search_query }}"</span>
                    </h1>
                    <p class="text-muted small">Evaluación automatizada de sintomatología clínica en lenguaje natural.</p>
                </div>

                <!-- Banner de Triage Fijo -->
                <div class="alert {{ $symptom->urgency_level === 'Alta' ? 'alert-danger' : ($symptom->urgency_level === 'Media' ? 'alert-warning' : 'alert-success') }} border-start border-4 shadow-sm py-4 mb-5" role="alert">
                    <div class="d-flex align-items-start">
                        <span class="fs-2 me-3">💡</span>
                        <div>
                            <h3 class="fs-5 font-weight-bold mb-1">
                                Prioridad de Atención: <span class="badge bg-dark">{{ $symptom->urgency_level }}</span>
                            </h3>
                            <p class="mb-3 fs-6 text-dark leading-relaxed">
                                {{ $symptom->ai_advice }}
                            </p>
                            @if ($symptom->specialty)
                            <p class="mb-0 small">
                                Contamos con una red activa de profesionales verificados.
                                <a href="{{ route('search') }}?specialty={{ $symptom->specialty->slug }}" class="alert-link text-decoration-underline fw-bold ms-1">
                                    Ver todo el catálogo de {{ $symptom->specialty->name }} →
                                </a>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Listado de Médicos Sugeridos Disponibles -->
                <div class="mt-4">
                    <h4 class="text-dark mb-4 fs-5 fw-bold text-uppercase tracking-wide text-secondary">
                        👨‍⚕️ Especialistas recomendados en {{ $symptom->specialty ? $symptom->specialty->name : 'General' }} disponibles:
                    </h4>

                    @forelse($doctors as $doctor)
                        <div class="card h-100 shadow-sm border-0 border-bottom border-2 border-primary mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h5 class="card-title mb-1 text-dark fw-bold">Dr(a). {{ $doctor->user->name }} {{ $doctor->user->last_name }}</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-primary border">{{ $symptom->specialty ? $symptom->specialty->name : 'General' }}</span>
                                        @if($doctor->addresses->isNotEmpty())
                                            <small class="text-muted">📍 {{ $doctor->addresses->first()->city->name }}</small>
                                        @endif
                                    </div>
                                </div>
                                <a href="/doctor/{{ $doctor->slug }}" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm fw-bold">
                                    Agendar Cita
                                </a>
                            </div>
                        </div>
                    @empty
                        <!-- Estado de respaldo si no hay médicos en esa área todavía -->
                        <div class="card text-center p-5 border-0 shadow-sm bg-light">
                            <div class="fs-1 mb-2">🔍</div>
                            <h5 class="fw-bold text-dark">No hay médicos directos asignados en este momento</h5>
                            <p class="text-muted small mb-3">Contamos con médicos generales listos para evaluar tu caso de forma inicial.</p>
                            <a href="{{ route('search') }}" class="btn btn-outline-primary btn-sm px-4 rounded-pill">
                                Explorar directorio completo
                            </a>
                        </div>
                    @endforelse

                    <!-- Paginación nativa de Bootstrap para los bots de Google -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $doctors->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
