<x-guest-layout>
    <x-slot:seo>
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">
        <style>
            .btn-pill-primary { background: #0d6efd; color: #fff; border: none; transition: all 0.25s ease; }
            .btn-pill-primary:hover { background: #0b5ed7; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25); }
            .doctor-card { transition: all 0.3s ease; border: 1px solid #f1f3f5 !important; }
            .doctor-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important; }
            .search-box-premium { background: #ffffff; border: 1px solid #e9ecef; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02) !important; }
            .gold-badge { background: linear-gradient(135deg, #ffdf00, #bda100); color: #212529; font-weight: 700; }
            .premium-badge { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); color: #fff; font-weight: 700; }
        </style>
    </x-slot:seo>

    <div class="container max-w-7xl mx-auto px-4 py-5 mt-5">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-semibold mb-2 small text-uppercase tracking-wider">Red de Salud</span>
            <h1 class="fw-black text-dark display-6 mb-2 tracking-tight">Directorio Médico Oficial</h1>
            <p class="text-muted leading-relaxed">Agenda tu cita en minutos con profesionales de la salud verificados en OpenDoctor.</p>
        </div>
        <div class="card search-box-premium border-0 p-3 mb-5 rounded-4">
            <form action="{{ route('medical.directory') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group bg-light rounded-pill px-3 border-0">
                        <span class="input-group-text bg-transparent border-0 opacity-60">🩺</span>
                        <select name="specialty" class="form-select bg-transparent border-0 small py-2.5" style="box-shadow: none;">
                            <option value="">Buscar especialidad médica...</option>
                            @foreach($specialties as $especialidad)
                                <option value="{{ $especialidad->slug }}" {{ request('specialty') === $especialidad->slug ? 'selected' : '' }}>{{ $especialidad->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="input-group bg-light rounded-pill px-3 border-0">
                        <span class="input-group-text bg-transparent border-0 opacity-60">📍</span>
                        <select name="city" class="form-select bg-transparent border-0 small py-2.5" style="box-shadow: none;">
                            <option value="">¿En qué ciudad te encuentras?</option>
                            @foreach($cities as $ciudad)
                                <option value="{{ $ciudad->name }}" {{ request('city') === $ciudad->name ? 'selected' : '' }}>{{ $ciudad->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-pill-primary w-100 rounded-pill fw-bold btn-sm py-2.5 shadow-sm">Filtrar</button>
                    @if(request()->filled('specialty') || request()->filled('city'))
                        <a href="{{ route('medical.directory') }}" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 42px; height: 42px;">✕</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="row">
            @forelse($doctors as $doctor)
                @php $planSlug = $doctor->settings?->plan?->slug ?? 'free'; @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 doctor-card border-0 shadow-sm rounded-4 bg-white @if($planSlug === 'gold') border-top border-4 border-warning @elseif($planSlug === 'premium') border-top border-4 border-info @endif">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    @if($planSlug === 'gold')
                                        <span class="badge gold-badge rounded-pill px-3 py-1.5 small shadow-sm">⭐ Socio Gold</span>
                                    @elseif($planSlug === 'premium')
                                        <span class="badge premium-badge rounded-pill px-3 py-1.5 small shadow-sm">Socio Premium</span>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1.5 small">Verificado</span>
                                    @endif
                                    @if($doctor->rating > 0)
                                        <div class="d-flex align-items-center gap-1 bg-light px-2.5 py-1 rounded-pill"><span class="text-warning fw-bold">★</span><span class="text-dark fw-bold small">{{ number_format($doctor->rating, 1) }}</span></div>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-3 mb-3">
                                    @if($doctor->user?->profile_photo_path)
                                        <img src="/storage/{{ $doctor->user->profile_photo_path }}" class="size-10 rounded-full object-cover">
                                    @else
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center border" style="min-width: 56px; min-height: 56px; max-width: 56px; max-height: 56px; font-size: 1.1rem;">{{ Str::upper(Str::substr($doctor->user?->name, 0, 2)) }}</div>
                                    @endif
                                    <div>
                                        <h5 class="card-title fw-black text-dark mb-0 fs-5">Dr(a). {{ $doctor->user?->name }}</h5>
                                        <span class="text-muted small d-block mt-0.5">📍 {{ $doctor->addresses->first()->city?->name ?? 'Consulta Virtual' }}</span>
                                    </div>
                                </div>

                                <p class="text-muted small mb-4 leading-relaxed" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 40px;">{{ $doctor->bio ?? 'Profesional de la salud enfocado en brindar una atención integral.' }}</p>

                                <div class="mb-4" style="min-height: 38px;">
                                    @forelse($doctor->specialties as $especialidad)
                                        <span class="badge bg-light text-secondary border border-light rounded-pill px-2.5 py-1.5 me-1 my-1 small fw-medium">{{ $especialidad->name }}</span>
                                    @empty
                                        <span class="badge bg-light text-muted rounded-pill px-2.5 py-1.5 small">Medicina General</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="pt-3 border-top border-light">
                                <a href="{{ route('partner.public.profile', ['partner' => $doctor->slug]) }}" class="btn btn-pill-primary w-100 rounded-pill fw-bold btn-sm py-2.5 text-uppercase tracking-wider small">Reservar Cita</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <h5 class="fw-bold text-dark fs-5">No encontramos especialistas</h5>
                    <p class="small text-muted">Prueba restableciendo los buscadores superiores.</p>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-center mt-5">
            {{ $doctors->links() }}
        </div>
    </div>
</x-guest-layout>
