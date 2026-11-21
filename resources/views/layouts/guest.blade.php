@props([
    'meta_title' => null, 
    'meta_description' => null, 
    'keywords' => null, 
    'meta_robots' => null,
    'meta_canonical' => null,
    'meta_image' => null
])

@php
    // 1. Corregimos a camelCase (como Laravel procesa los atributos con guion de Blade)
    $name = !empty($meta_title) ? $meta_title : "OpenDoctor | Agendar una Cita Médica en Línea";

    // 2. Validación de Descripción con rotación aleatoria
    if (empty($meta_description)) {
        $descriptions = [
            "Plataforma de agendamiento médico en línea. Consulta especialistas, realiza análisis con IA y agenda citas médicas en tiempo real. Telemedicina solicita y recibe tu cita.",
            "Encuentra médicos especialistas en línea. Triage inteligente con IA, citas médicas por telemedicina en tiempo real y análisis digital avanzado.",
            "Agenda citas médicas en tiempo real y realiza consultas virtuales. Telemedicina avanzada con análisis de exámenes e Inteligencia Artificial.",
            "Agenda tu Cita Médica con los mejores especialistas de tu ciudad. Rápido, seguro y sin filas."
        ];
        $description = $descriptions[array_rand($descriptions)];
    } else {
        // Ahora sí capturará "Crea tu cuenta profesional en OpenDoctor de forma gratuita..."
        $description = $meta_description;
    }

    $finalKeywords = !empty($keywords) ? $keywords : 'citas médicas en línea, citas médicas, agendar cita médica, consulta médica virtual, telemedicina, médicos especialistas, directorio médico, atención médica online, salud digital';
    $finalRobots = !empty($meta_robots) ? $meta_robots : 'index, follow';
    
    // Forzar canonical sin www y sin query strings innecesarios
    $currentUrl = url()->current();
    $parsedUrl = parse_url($currentUrl);
    $cleanPath = $parsedUrl['path'] ?? '/';
    $finalCanonical = !empty($meta_canonical) ? $meta_canonical : 'https://opendoctor.online' . $cleanPath;
    
    $finalImage = !empty($meta_image) ? $meta_image : asset('/images/logoAuth.jpg');
    
    $schemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => $name,
        'url' => 'https://opendoctor.online',
        "applicationCategory" => "HealthApplication",
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => 4.6,
            "ratingCount" => 1739
        ],
        "operatingSystem" => "All",
        "browserRequirements" => "Requires HTML5 / JavaScript",
        "inLanguage" => "es",        
        "image" => "https://opendoctor.online/images/logoAuth.jpg",        
        "description" => $description,
        "featureList" => [
            "Telemedicina y citas médicas virtuales por videoconferencia (Zoom) y presenciales en consultorio",
            "Análisis de piel mediante Inteligencia Artificial para la detección y orientación de afecciones cutáneas",
            "Segunda opinión médica por Inteligencia Artificial para la interpretación y análisis digital de exámenes (Laboratorio, Ecografía, Radiografía, Tomografía, Resonancia, DICOM)"
        ],
        "about" => [
            [
                "@type" => "MedicalSpecialty",
                "name" => "Telemedicine"
            ],
            [
                "@type" => "MedicalSpecialty",
                "name" => "GeneralMedicine"
            ],
            [
                "@type" => "MedicalSpecialty",
                "name" => "Dermatology"
            ],
            [
                "@type" => "MedicalProcedure",
                "name" => "DiagnosticProcedure"
            ]
        ],        
        "author" => [
            "@type" => "Organization",
            "name" => "OpenDoctorOnline",
            "url" => "https://opendoctor.online",
            'image' => "https://opendoctor.online/images/logoAuth.jpg",
            'logo' => "https://opendoctor.online/images/logoAuth.jpg",
            "telephone" => "+573026433874",
            "areaServed" => [
                "@type" => "AdministrativeArea",
                "name" => "World"
            ],
        ],
        "hasPart" => [
            [
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "¿Cómo funciona la segunda opinión por Inteligencia Artificial para exámenes médicos?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Nuestra IA analiza digitalmente reportes y archivos de exámenes médicos como laboratorios, ecografías, radiografías, tomografías y resonancias magnéticas, ofreciendo una interpretación clínica preliminar de alta precisión que sirve como respaldo o segunda opinión orientativa."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "¿En qué consiste el análisis de piel con Inteligencia Artificial de OpenDoctor?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Es un componente digital avanzado que permite procesar imágenes de la piel para identificar, evaluar y ofrecer una orientación médica inmediata sobre posibles afecciones cutáneas, facilitando el pre-diagnóstico antes de tu consulta dermatológica."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "¿Las consultas virtuales por telemedicina incluyen recetas médicas?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Sí, a través de las videollamadas integradas por Zoom, los especialistas verificados de OpenDoctorOnline pueden emitir órdenes, guías de tratamiento y recetas médicas válidas de acuerdo con la evaluación clínica realizada en tiempo real."
                        ]
                    ]
                ]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $keywords = 'Agenda cita médica online, consulta especialista, telemedicina Colombia, plataforma agendamiento, cita médica virtual, Exámenes de laboratorio online, Analisis de Piel por Inteligencia Artificial, Laboratorio, Ecografía, Radiografía, Tomografía, Resonancia, DICOM';        
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <!-- Icono para dispositivos Apple (iPhone, iPad) -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
        <!-- Iconos PNG de tamaño estándar para navegadores modernos -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
        
        <title>{{ $name }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="keywords" content="{{ $finalKeywords }}">
        <meta name="robots" content="{{ $finalRobots }}">
        <link rel="canonical" href="{{ $finalCanonical }}">
        
        <meta name="geo.region" content="CO" />
        <meta name="geo.placename" content="Colombia" />
        <meta name="author" content="OpenDoctorOnline">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="OpenDoctorOnline">
        <meta property="og:title" content="{{ $name }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/og-logo.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $name }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ asset('images/og-logo.png') }}">

        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

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
                if (!locationDecision && !window.OD_SKIP_AUTO_GEO && navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            // Guardar consentimiento afirmativo en el navegador del cliente
                            localStorage.setItem('opendoctor_location_consent', 'allowed');
                            
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;

                            try {
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
                                        longitude: longitude
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

        <!-- ======================================================== -->
        <!-- SINCRONIZAR UBICACIÓN YA AUTORIZADA                        -->
        <!-- Si el visitante ya concedió el permiso de ubicación (p. ej. antes de que el guardado funcionara), -->
        <!-- se envía en silencio al servidor, como máximo cada 30 min por pestaña. Nunca muestra un aviso: -->
        <!-- solo actúa si el navegador ya tiene el permiso en "granted". -->
        <!-- ======================================================== -->
        <script data-location-sync>
            document.addEventListener('DOMContentLoaded', () => {
                try {
                    if (localStorage.getItem('opendoctor_location_consent') !== 'allowed') return;
                    if (!navigator.geolocation || !navigator.permissions) return;

                    const last = Number(sessionStorage.getItem('opendoctor_location_synced') || 0);
                    if (Date.now() - last < 30 * 60 * 1000) return;

                    navigator.permissions.query({ name: 'geolocation' }).then((status) => {
                        if (status.state !== 'granted') return; // "prompt" o "denied": no se pide nada

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                try { sessionStorage.setItem('opendoctor_location_synced', String(Date.now())); } catch (e) {}
                                fetch('/api/session/location', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    },
                                    body: JSON.stringify({
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude,
                                    }),
                                }).catch(() => {});
                            },
                            () => { /* sin ubicación: no pasa nada */ },
                            { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 }
                        );
                    }).catch(() => {});
                } catch (e) { /* no es crítico */ }
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
