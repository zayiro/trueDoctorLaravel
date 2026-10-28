<div 
    x-data="{ showButton: false }" 
    @scroll.window="showButton = (window.pageYOffset > 400)"
    class="fixed bottom-6 right-6 z-50 transition-all duration-300"
    style="position: fixed; bottom: 24px; right: 24px; z-index: 9999;"
>
    <button 
        x-show="showButton"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"                
        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg border-none cursor-pointer transition-all duration-300 ease-out hover:bg-blue-700 hover:-translate-y-1 hover:shadow-sm hover:scale-105 active:scale-95 focus:outline-none"                
        style="display: flex; width: 48px; height: 48px; align-items: center; justify-content: center; border-radius: 9999px; background-color: #2563eb; color: #ffffff; border: none; cursor: pointer;"
        aria-label="Volver arriba"
    >
        <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 24px; height: 24px;" class="transition-transform duration-300 group-hover:scale-110">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
        </svg>
    </button>
</div>
