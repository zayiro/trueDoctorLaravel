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
            <title>{{ $meta_title ?? 'OpenDoctor | Orientación Médica' }}</title>
            <meta name="description" content="{{ $meta_description ?? 'Encuentra especialistas médicos y agenda tu cita en línea.' }}">
            <meta name="robots" content="{{ $meta_robots ?? 'index, follow' }}">
        @endif

        <!-- Canonical corregida para SEO Programático -->
        <link rel="canonical" href="{{ $meta_canonical ?? url()->current() }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- wireui --}}
        <wireui:scripts />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles        
    </head>
    <body class="font-sans antialiased bg-gray-100">
        @include('layouts.includes.app.navigation')

        <div class="pt-5">
            {{ $slot }}
        </div>        

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
    </body>
</html>
