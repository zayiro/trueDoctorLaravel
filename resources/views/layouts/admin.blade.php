@props([
    'title' => 'Dashboard',
    'breadcrumbs' => []
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>
        <meta name="robots" content="@yield('meta_robots', 'index, follow')">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        {{-- wireui --}}
        <wireui:scripts />

        {{-- FontAwesome --}}
        <script src="https://kit.fontawesome.com/5b159020fe.js" crossorigin="anonymous"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased" style="background-color: #FCFCFC;">       
        @if(auth()->user()->role === 'doctor')
            @include('layouts.includes.admin.navigation')
            @include('layouts.includes.admin.sidebar')
        @elseif(auth()->user()->role === 'admin')
            @include('layouts.includes.administrator.navigation')
            @include('layouts.includes.administrator.sidebar')
        @elseif(auth()->user()->role === 'clinic')
            @include('layouts.includes.clinic.navigation')
            @include('layouts.includes.clinic.sidebar')
        @else
            @include('layouts.includes.patient.navigation')
            @include('layouts.includes.patient.sidebar')
        @endif

        <div class="p-4 sm:ml-64">
            <div class="mt-14 flex items-center">

                @include('layouts.includes.admin.breadcrumb')

                @isset($action)
                    <div class="ml-auto">
                        {{ $action }}
                    </div>
                @endisset
            </div>

            {{ $slot }}
        </div>

        @stack('modals')

        @livewireScripts

        <script src="{{ asset('js/flowbite.min.js') }}"></script>            
    </body>
</html>
