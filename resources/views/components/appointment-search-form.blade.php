<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <form action="{{ route('appointments.search') }}" method="GET" class="relative w-full">
        <!-- Etiqueta superior minimalista -->
        <label for="reference" class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-2 block">
            Buscador de Citas Global
        </label>
        
        <!-- Contenedor del Input de diseño integrado flexible -->
        <div class="relative flex items-center bg-white border border-slate-200 rounded-2xl shadow-sm w-full focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-100/50 transition-all duration-300">
            
            <!-- Icono Izquierdo de Lupa decorativo -->
            <div class="pl-4 text-slate-400 pointer-events-none transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <!-- Input Principal Flexible -->
            <input 
                type="text" 
                name="reference" 
                id="reference"
                value="{{ old('reference') }}"
                placeholder="Ingresa la referencia (Ej: 20260525-A8F2)" 
                class="w-full flex-1 bg-transparent border-0 py-5 pl-3 pr-28 text-sm font-semibold text-slate-800 uppercase placeholder-slate-400 focus:ring-0 focus:outline-none tracking-wider"
                required
            >
            
            <!-- Botón de acción con posición fija e independiente -->
            <div class="absolute right-2 top-3 bottom-2 flex items-center">
                <button type="submit" class="h-full bg-indigo-600 p-3 text-white hover:text-black rounded-xl text-xs font-black uppercase tracking-wider hover:bg-indigo-300 active:scale-95 shadow-md hover:shadow-indigo-200/50 flex items-center gap-1.5 transition-all duration-200 whitespace-nowrap shrink-0">
                    <span>Buscar</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>

    {{-- Renderizado elegante de errores de búsqueda --}}
    @if (session('error'))
        <div class="mt-3 flex items-center gap-2 text-xs font-bold text-red-600 bg-red-50 border border-red-100/80 px-4 py-3 rounded-xl">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
</div>
