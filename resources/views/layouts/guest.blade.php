@php
    $title = $meta_title ?? 'Plataforma de agendamiento médico en línea';
    $description = $meta_description;
    $schemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'MedicalBusiness',
        '@id' => config('app.url'),
        'name' => 'OpenDoctorOnline',
        'description' => 'Plataforma de agendamiento médico en línea. Consulta especialistas, realiza análisis con IA y agenda citas médicas en tiempo real. Telemedicina solicita y recibe tu cita',
        'url' => config('app.url'),
        'logo' => asset('images/logoOpenDoctor.jpg'),
        'image' => asset('images/logoOpenDoctor.jpg'),
        'telephone' => '+573026433874',
        'email' => 'contacto@opendoctor.online',
        'areaServed' => [
            [
                '@type' => 'Country',
                'name' => 'Colombia'
            ],
            [
                '@type' => 'Country',
                'name' => 'México'
            ],
            [
                '@type' => 'Country',
                'name' => 'Perú'
            ],
            [
                '@type' => 'Country',
                'name' => 'Argentina'
            ]
        ],
        'offers' => [
            [
                '@type' => 'Service',
                'name' => 'Agendamiento de Citas Médicas',
                'description' => 'Agenda consultas con especialistas en línea o presenciales',
                'areaServed' => 'CO,MX,PE,AR'
            ],
            [
                '@type' => 'Service',
                'name' => 'Analisis Médico con IA',
                'description' => 'Análisis instantaáneo de síntomas con inteligencia artificial',
                'areaServed' => 'CO,MX,PE,AR'
            ]
        ]
    ], JSON_UNESCAPED_SLASHES);
    $keywords = 'Agenda cita médica online, consulta especialista, telemedicina Colombia, plataforma agendamiento, cita médica virtual, Exámenes de laboratorio online';

    if (isset($metaTitleMedicalAnalysis)) {        
        $title = $metaTitleMedicalAnalysis;
        $description = $metaDescriptionMedicalAnalysis;
        $schemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'MedicalBusiness',
            'name' => 'OpenDoctorOnline',
            'url' => config('app.url'),
            'logo' => asset('images/logoOpenDoctor.jpg'),
            'description' => $meta_description,
            'telephone' => '+573026433874',
            'areaServed' => ['CO', 'MX', 'PE', 'AR']            
        ]);
        $keywords ='Análisis médico IA, Interpreta tus exámenes médicos con Inteligencia Artificial, interpretar análisis de sangre online gratis, evaluación médica rápida,  Lee e interpreta tus resultados online médicos con IA, Interpretación de pruebas de laboratorio en línea, análisis de síntomas gratis, Interpreta tus análisis de sangre, orina y heces online con IA en minutos, Como leer resultados de laboratorio de sangre, Interpretación de exámenes de laboratorio PDF gratis, valores normales de exámenes de laboratorio pdf, Lector de exámenes médicos gratis, ia para leer resultados de laboratorio gratis';
    }
        
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        
        @if (isset($seo) && $seo->isNotEmpty())
            {{ $seo }}            
        @else        
            <title>{{ $title ?? 'OpenDoctorOnline | Orientacion Medica' }}</title>            
            <meta name="description" content="{{ $description ?? 'Análisis médico online con IA. Consulta especialistas en Colombia, diagnostico instantaneo, cita médica virtual y presencial disponible.' }}">
            <meta name="robots" content="{{ $meta_robots ?? 'index, follow' }}">
        @endif

        <meta name="keywords" content="{{ $keywords }}">
    
        <link rel="canonical" href="{{ $meta_canonical ?? url()->current() }}">
        
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="OpenDoctorOnline">
        <meta property="og:title" content="{{ $title ?? 'OpenDoctorOnline - Encuentra tu especialista y agenda tu cita' }}">
        <meta property="og:description" content="{{ $description ?? 'Busca especialistas medicos, agenda tu cita y recibe recordatorios automaticos.' }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/og-logo.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle ?? 'OpenDoctorOnline' }}">
        <meta name="twitter:description" content="{{ $description ?? 'Busca especialistas medicos y agenda tu cita.' }}">
        <meta name="twitter:image" content="{{ asset('images/og-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- wireui --}}
        <wireui:scripts />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles        

        <!-- CSS de Flatpickr -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- JS de Flatpickr -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <!-- JS de Flatpickr para localización en español -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

        @stack('meta')

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(registrations => {
                    registrations.forEach(reg => reg.unregister());
                });
            }
        </script>

        @if(env('GOOGLE_ANALYTICS_ID'))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_ID') }}"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ env('GOOGLE_ANALYTICS_ID') }}');
        </script>
        @endif

        <script type="application/ld+json">
            {!! $schemaJson ?? '{}' !!}
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-100">
        @include('layouts.includes.app.navigation')

        <main>
            {{ $slot }}
        </main>        

        @include('components.footer')

        @livewireScripts

        @wireUiScripts       

        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

        <!-- ======================================================== -->
        <!-- CAPTURA DE GEOLOCALIZACIÓN -->
        <!-- ======================================================== -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // 1. Validar si el usuario ya otorgó o rechazó el permiso en sesiones anteriores
                const locationDecision = localStorage.getItem('opendoctor_location_consent');
                
                // Si no hay decisión previa y el dispositivo soporta geolocalización, solicitamos coordenadas
                if (!locationDecision && navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            // Guardar consentimiento afirmativo en el navegador del cliente
                            localStorage.setItem('opendoctor_location_consent', 'allowed');
                            
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;
                            let detectedCityName = 'Unknown';

                            try {
                                // 2. Consulta inversa rápida a OpenStreetMap para extraer el nombre de la ciudad
                                const reverseResponse = await fetch(`https://openstreetmap.org{latitude}&lon=${longitude}&zoom=10&addressdetails=1`);
                                if (reverseResponse.ok) {
                                    const data = await reverseResponse.json();
                                    detectedCityName = data.address.city || data.address.town || data.address.municipality || 'Unknown';
                                }

                                // 3. 🔐 PERSISTENCIA EN EL BACKEND: Despachar la data hacia la sesión de Laravel
                                await fetch("/api/session/location", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        // Capturar el token CSRF global de la página
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        latitude: latitude,
                                        longitude: longitude,
                                        city: detectedCityName
                                    })
                                });

                            } catch (error) {
                                console.error("Error silencioso procesando la geolocalización en el servidor:", error);
                            }
                        },
                        (error) => {
                            // El usuario rechazó el permiso nativo o el dispositivo falló; no lo volvemos a molestar
                            console.warn("Acceso a la geolocalización denegado por el usuario o dispositivo.");
                            localStorage.setItem('opendoctor_location_consent', 'denied');
                        },
                        { enableHighAccuracy: true, timeout: 6000, maximumAge: 0 }
                    );
                }
            });
        </script>

        <script>
            // 🛡️ CONTROLADOR DEFENSIVO DE BFCACHE PARA PRODUCCIÓN
            window.addEventListener('pageshow', function (event) {
                // Si event.persisted es true, significa que el usuario regresó usando las flechas del navegador
                if (event.persisted) {
                    // 1. Quitar el overlay de carga global si se quedó encendido
                    const overlay = document.getElementById('loading-overlay');
                    if (overlay) {
                        overlay.style.display = 'none';
                    }

                    // 2. Si estás usando Alpine en las tarjetas, forzar la restauración de sus estados locales
                    window.dispatchEvent(new CustomEvent('restore-booking-buttons'));
                    
                    // 3. Fallback nativo: Si tus botones cambian mediante clases HTML puras o deshabilitación
                    document.querySelectorAll('button[disabled]').forEach(button => {
                        button.removeAttribute('disabled');
                    });
                }
            });
        </script>
    </body>
</html>
