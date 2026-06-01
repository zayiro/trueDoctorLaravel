@if (count($breadcrumbs))

    <nav class="flex flex-col gap-2 mb-6" aria-label="Breadcrumb">

        <!-- Lista de enlaces del Breadcrumb con alineación vertical perfecta -->
        <ol class="inline-flex items-center flex-wrap space-x-1 md:space-x-2 rtl:space-x-reverse">
            @foreach ($breadcrumbs as $item)
                <li class="inline-flex items-center">
                    
                    @if (!$loop->first)
                        <!-- Separador: ChevronRight centrado verticalmente -->
                        <svg class="w-5 h-5 text-gray-400 mx-1 md:mx-2 rtl:rotate-180 block align-middle" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    @endif

                    @isset($item['href'])
                        @if ($loop->first)
                            <!-- Flex e items-center para alinear el icono Home y su texto -->
                            <a href="{{ $item['href'] }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white transition-colors duration-200">
                                <!-- Icono Home con márgenes corregidos -->
                                <svg class="w-4 h-4 me-1.5 block align-middle" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                                </svg>
                                <span class="leading-none mt-1">{{ $item['name'] }}</span>
                            </a>
                        @else
                            <!-- Enlaces intermedios sin icono -->
                            <a href="{{ $item['href'] }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white transition-colors duration-200">
                                <span class="leading-none">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    @else
                        <!-- Último elemento activo -->
                        <span class="inline-flex items-center text-sm font-semibold text-gray-800 dark:text-gray-200" aria-current="page">
                            <span class="leading-none">{{ $item['name'] }}</span>
                        </span>
                    @endisset

                </li>
            @endforeach
        </ol>

        <!-- Encabezado de página (H1) -->
        @if (count($breadcrumbs) > 1)
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl mt-1">
                {{ end($breadcrumbs)['name'] }}
            </h1>
        @endif
    </nav>

@endif
