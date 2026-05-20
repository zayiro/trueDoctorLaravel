@php
    $links = [
        [
            'header' => 'Administrar página',        
        ],
        [
            'name' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('admin.dashboard'),
            'active' => false,
        ],
        [
            'name' => 'Aprovación de documentos',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('administrator.validation.index'),
            'active' => false,
        ],
    ];
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-white border-e border-default">
        <a href="https://flowbite.com/" class="flex items-center ps-2.5 mb-5">
            <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 me-3" alt="Flowbite Logo" />
            <span class="self-center text-lg text-heading font-semibold whitespace-nowrap">Flowbite</span>
        </a>
        <ul class="pt-5 space-y-2 font-medium">
            @foreach ($links as $link)
                <li>
                    @isset($link['header'])
                        <div class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase">
                            {{  $link['header'] }}
                        </div>
                    @else
                        @isset($link['submenu'])
                            <button type="button" class="flex items-center w-full justify-between px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group" aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">
                                <span class="w-6 h-6 inline-flex justify-center items-center text-gray-500">
                                    <i class="{{  $link['icon'] }}"></i>
                                </span>
                                <span class="ms-3">{{ $link['name'] }}</span>
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                            </button>
                            <ul id="dropdown-example" class="hidden py-2 space-y-2">
                                @foreach ($link['submenu'] as $item)
                                    <li>
                                        <a href="{{  $item['href'] }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">{{  $item['name'] }}</a>
                                    </li>                            
                                @endforeach
                            </ul>
                        @else
                            <a href="{{  $link['href'] }}" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ $link['active'] ? 'bg-gray-100' : '' }}">
                                <span class="w-6 h-6 inline-flex justify-center items-center text-gray-500">
                                    <i class="{{  $link['icon'] }}"></i>
                                </span>
                                <span class="ms-3">{{  $link['name'] }}</span>
                            </a>
                        @endisset
                    @endisset
                </li>            
            @endforeach
        </ul>
    </div>
</aside>