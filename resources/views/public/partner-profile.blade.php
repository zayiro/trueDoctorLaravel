{{--
Perfil público completo del médico.
Variables: $doctor (App\Models\Doctor) y, opcional, $bookingUrl (enlace del botón "Agendar cita").
Carga recomendada en el controlador:
    $doctor->load(['user', 'specialties', 'addresses' => fn ($q) => $q->bookable()->with(['city', 'services'])]);
    Las secciones se muestran SOLO si hay datos; los campos opcionales se leen con data_get() (ajusta los nombres a tus columnas).
--}}
@php
    $user        = $doctor->user;
    $name        = $user?->name ?? 'Especialista';
    $photo       = $user?->profile_photo_path;
    $specialties = $doctor->specialties ?? collect();
    $primary     = $specialties->first()?->name ?? 'Médico';

    $addresses   = ($doctor->addresses ?? collect())->where('status', true)->values();
    $physical    = $addresses->where('type', 'physical')->values();
    $hasVirtual  = $addresses->where('type', 'virtual')->isNotEmpty();
    $offerings   = $addresses->filter(fn ($a) => $a->services->isNotEmpty())->values();   // sedes con servicios (cada uno es independiente por sede)

    $bio       = data_get($doctor, 'bio') ?? data_get($doctor, 'description') ?? data_get($doctor, 'about');
    $years     = data_get($doctor, 'years_experience') ?? data_get($doctor, 'experience_years');
    $license   = data_get($doctor, 'license_number') ?? data_get($doctor, 'medical_license');
    $languages = data_get($doctor, 'languages');
    $languages = is_array($languages) ? implode(', ', $languages) : $languages;

    $modalities = collect([$physical->isNotEmpty() ? 'Presencial' : null, $hasVirtual ? 'Telemedicina' : null])->filter()->implode(' · ');
    $isVerified = (string) data_get($doctor, 'validation_status') === 'approved';
    $bookingUrl = $bookingUrl ?? '#servicios';

    $mapsUrl = function ($address) {
        return $address->hasCoordinates()
            ? 'https://www.google.com/maps?q=' . $address->latitude . ',' . $address->longitude
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address->address . ' ' . ($address->city->name ?? ''));
    };

    $jsonLd = array_filter([
        '@context'         => 'https://schema.org',
        '@type'            => 'Physician',
        'name'             => $name,
        'image'            => $photo,
        'url'              => url()->current(),
        'medicalSpecialty' => $specialties->pluck('name')->values()->all() ?: null,
        'address'          => $physical->map(fn ($a) => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $a->address,
            'addressLocality' => $a->city->name ?? null,
            'addressCountry'  => $a->country_code ?? 'CO',
        ])->values()->all() ?: null,
    ]);
@endphp

<x-guest-layout
    meta_title="Perfil de Doctor o Centro Médico | OpenDoctor"
    meta_description="Perfil Médico especializado"
>

<div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 font-sans my-6">
    <div class="grid grid-cols-1 md:grid-cols-12 w-full">
        
        {{-- COLUMNA IZQUIERDA: Imagen y Biografía --}}
        <div class="md:col-span-5 flex flex-col bg-gray-50/50 w-full border-r border-gray-100">
            
            @php
                $resolvedPhoto = $doctor->avatar_url ?? $doctor->photo ?? $doctor->user->profile_photo_url ?? null;
            @endphp

            {{-- 📷 RENDERIZADO DIRECTO: Flujo natural sin absolute/relative --}}
            @if ($resolvedPhoto)
                <img 
                    src="{{ \Storage::url($photo) }}" 
                    alt="{{ $doctor->user->name }}" 
                    class="w-full h-auto max-h-[32rem] object-cover"
                    decoding="async"
                >
            @else
                <div class="w-full py-16 text-center text-7xl font-black text-slate-300 bg-slate-100 uppercase select-none">
                    {{ mb_substr($doctor->user->name, 0, 1) }}
                </div>
            @endif

            {{-- Sección: Biografía --}}
            <div class="p-6 flex-1 bg-white">
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-2">Biografía</h3>
                <div class="text-md text-gray-600 leading-relaxed space-y-3">
                    <p>{{ $doctor->bio ?? 'Sin descripción profesional disponible en este momento.' }}</p>
                    @if($doctor->experience_years)
                        <p class="text-md text-gray-500 italic">Formado con los más altos estándares científicos y éticos en salud.</p>
                    @endif
                </div>
            </div>
        </div>
        {{-- COLUMNA DERECHA: Detalles Profesionales y Contacto --}}
        <div class="md:col-span-7 p-6 md:p-8 flex flex-col justify-between bg-white space-y-6">
            
            {{-- Bloque de Perfil / Hospital --}}
            <div>
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-1">Perfil Profesional</h3>
                <h2 class="text-base font-bold text-gray-800">Dr. {{ $doctor->user->name }}</h2>
                <p class="text-md text-gray-500 mt-0.5 font-medium">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
                
                {{-- Credenciales Académicas --}}
                <div class="mt-3 text-md text-gray-600 space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100/80">
                    <p class="font-medium text-gray-700">Licencia Médica: <span class="font-normal text-gray-600">{{ $doctor->medical_license ?? 'En proceso de verificación' }}</span></p>
                    <p class="font-medium text-gray-700">Institución: <span class="font-normal text-gray-600">{{ $doctor->clinic?->user->name ?? 'Consultorio Independiente' }}</span></p>
                </div>
            </div>

            {{-- Sección: Especialidades / Enfoques (Badges) --}}
            <div>
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-2.5">Especialidades</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($doctor->specialties as $specialty)
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-[11px] font-medium rounded-full shadow-xs">
                            {{ $specialty->name }}
                        </span>
                    @empty
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-500 text-[11px] font-medium rounded-full shadow-xs">
                            Medicina General
                        </span>
                    @endforelse
                </div>
            </div>

            {{-- Fila de Datos Rápidos: Experiencia, Idiomas, Tipo --}}
            <div class="grid grid-cols-3 gap-4 border-t border-b border-gray-100 py-4 text-md">
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Experiencia</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->experience_years ?? '0' }} Años+</p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Idiomas</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">
                        {{ is_array($doctor->languages) ? implode(', ', $doctor->languages) : 'Español' }}
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Vinculación</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->clinic_id ? 'Staff de Clínica' : 'Médico Autónomo' }}</p>
                </div>
            </div>

            {{-- Sección Inferior: Datos de Contacto --}}
            <div class="pt-2">
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-3">Contacto</h3>
                <div class="space-y-2.5 text-md text-gray-600">
                    {{-- Teléfonos --}}
                    @if($doctor->phone)
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.83a1 1 0 01.9.553l2 4a1 1 0 01-.145 1.055L17 14l-2 2a11.042 11.042 0 01-5.5-5.5l2-2 2.943-2.943a1 1 0 011.055-.145l4 2a1 1 0 01.553.9v.583a2 2 0 01-2 2H3z" />
                            </svg>
                            <span class="font-medium tracking-wide">{{ $doctor->phone }}</span>
                        </div>
                    @endif

                    {{-- Correo --}}
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-slate-600">{{ $doctor->user->email }}</span>
                    </div>

                    {{-- Extracción segura de la dirección --}}
                    @php
                        $activeAddress = $doctor->addresses->first();
                    @endphp

                    @if($activeAddress)
                        <div class="mt-4 pl-4 border-l-2 border-sky-500 py-0.5 space-y-0.5">
                            <p class="font-bold text-gray-800 text-[11px] uppercase tracking-wide">Ubicación del Consultorio</p>
                            <p class="text-gray-600 leading-tight">{{ $activeAddress->address }}</p>
                            <p class="text-md text-gray-400 font-medium">{{ $activeAddress->name }}</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

</x-guest-layout>

