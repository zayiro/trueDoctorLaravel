<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - @yield('title', 'Inicio')</title>

    <!-- Google Fonts -->
    <link href="https://googleapis.com" rel="stylesheet">
    
    <!-- Scripts y Estilos (Tailwind vía Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- FullCalendar CDN -->
    <!--<script src='https://jsdelivr.net'></script>-->
    <!--<script src="https://cloudflare.com" integrity="sha512-YI6rY3jIrfu82K1W2I8a67U4XpYI7rZtKq5R3u5B5K5L1A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>-->


    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-item-active { @apply bg-blue-50 text-blue-600 border-r-4 border-blue-600; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 hidden lg:flex flex-col sticky top-0 h-screen">
            <div class="p-8">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 p-2 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <span class="text-2xl font-extrabold tracking-tight text-slate-800">TrueDoctor</span>
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-4">
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50 {{ Request::is('dashboard*') ? 'sidebar-item-active' : 'text-slate-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    Agenda
                </a>
                
                <a href="{{ route('doctor.addresses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50 {{ Request::is('doctor/addresses*') ? 'sidebar-item-active' : 'text-slate-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    Mis Sedes
                </a>

                <a href="{{ route('doctor.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-slate-50 {{ Request::is('perfil*') ? 'sidebar-item-active' : 'text-slate-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Mi Perfil
                </a>
            </nav>

            <div class="p-4 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-4 py-3 w-full text-left text-slate-500 font-semibold hover:text-red-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-20 bg-white border-b border-slate-200 px-8 flex items-center justify-between sticky top-0 z-20">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">@yield('header')</h2>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-bold text-slate-900">{{ Auth::user()->name }}</p>
                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-black uppercase tracking-wider">
                            Plan {{ Auth::user()->doctor->plan }}
                        </span>
                    </div>
                    <img src="https://ui-avatars.com{{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff" class="w-10 h-10 rounded-xl shadow-sm">
                </div>
            </header>

            <!-- Área de Contenido -->
            <div class="p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span class="font-bold">{{ session('success') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

</body>
</html>
