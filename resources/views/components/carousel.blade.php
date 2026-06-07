@props([
    'slides',
    'autoplay' => true,
    'interval' => 4000
])

<div x-data="{ 
        activeSlide: 0, 
        slidesCount: {{ count($slides) }},
        timer: null,
        autoplay: {{ $autoplay ? 'true' : 'false' }},
        interval: {{ $interval }},
        next() { this.activeSlide = (this.activeSlide + 1) % this.slidesCount },
        prev() { this.activeSlide = (this.activeSlide - 1 + this.slidesCount) % this.slidesCount },
        startAutoplay() {
            if (this.autoplay) {
                this.timer = setInterval(() => { this.next() }, this.interval);
            }
        },
        stopAutoplay() {
            if (this.timer) clearInterval(this.timer);
        }
     }" 
     x-init="startAutoplay()"
     @mouseenter="stopAutoplay()"
     @mouseleave="startAutoplay()"
     class="relative w-full overflow-hidden rounded-lg shadow-lg"
     data-carousel="static">
    
    <!-- Contenedor de Slides -->
    <div class="relative h-56 md:h-96 bg-gray-900">
        @foreach($slides as $index => $slide)
            <div x-show="activeSlide === {{ $index }}"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-500"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform -translate-x-full"
                 class="absolute inset-0 duration-700 ease-in-out"
                 style="display: none;"
                 :class="{ 'block': activeSlide === {{ $index }}, 'hidden': activeSlide !== {{ $index }} }">
                
                @if(isset($slide['image']))
                    <img src="{{ $slide['image'] }}" class="absolute block w-full h-full object-cover -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="{{ $slide['title'] ?? 'Slide' }}">
                @endif
                
                @if(isset($slide['title']) || isset($slide['description']))
                    <div class="absolute bottom-12 left-1/2 z-10 -translate-x-1/2 text-center bg-black/40 backdrop-blur-sm p-4 rounded-xl text-white max-w-md w-11/12">
                        @if(isset($slide['title'])) 
                            <h3 class="text-lg font-bold">{{ $slide['title'] }}</h3> 
                        @endif
                        @if(isset($slide['description'])) 
                            <p class="text-sm text-gray-200 mt-1">{{ $slide['description'] }}</p> 
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Indicadores (Puntos) -->
    <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse">
        @foreach($slides as $index => $slide)
            <button type="button" 
                    @click="activeSlide = {{ $index }}"
                    :class="activeSlide === {{ $index }} ? 'bg-white w-8' : 'bg-white/50 w-3'"
                    class="h-3 rounded-full transition-all duration-300" 
                    aria-label="Slide {{ $index + 1 }}">
            </button>
        @endforeach
    </div>

    <!-- Botón Anterior -->
    <button @click="prev()" type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">
            <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 6 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
            </svg>
            <span class="sr-only">Anterior</span>
        </span>
    </button>

    <!-- Botón Siguiente -->
    <button @click="next()" type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">
            <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 6 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
            </svg>
            <span class="sr-only">Siguiente</span>
        </span>
    </button>
</div>
