<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900">

    <div class="flex min-h-screen">        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
          
            <div class="p-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
