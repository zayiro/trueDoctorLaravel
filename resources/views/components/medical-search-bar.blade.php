@props(['specialties', 'cities'])

<!-- BARRA DE BÚSQUEDA HÍBRIDA UNIFICADA (INMUNE A LIVEWIRE) -->
<form x-data="{ loading: false }" 
      x-on:submit="loading = handleSearchSubmit($event)"
      action="{{ route('search') }}" 
      method="GET" 
      class="bg-white mt-8 p-4 rounded-[1.5rem] shadow-md flex flex-col md:flex-row items-stretch md:items-center gap-4 mb-8 border border-slate-100 w-full">
    
    <!-- 🩺 AUTOCOMPLETADO INTERACTIVO: ESPECIALIDADES (DOCTORALIA STYLE) -->
    <div class="flex-1 min-w-[200px] relative" 
         x-data="specialtyAutocomplete([
             @foreach($specialties as $s)
                 { id: '{{ $s->slug }}', name: '{{ addslashes($s->name) }}' },
             @endforeach
         ])"
         x-on:click.away="closeDropdown()"
         x-on:keydown.escape="closeDropdown()"
    >
        
        <!-- Input oculto para persistencia del parámetro GET -->
        <input type="hidden" name="specialty" :value="selectedSlug">
        
        <label for="specialty" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Especialidad Médica</label>
        
        <div class="relative">
            <input 
                type="text"
                id="specialty" 
                x-model="searchQuery"
                x-on:focus="openDropdown()"
                x-on:input="openDropdown()"
                x-on:keydown.arrow-down.prevent="highlightNext()"
                x-on:keydown.arrow-up.prevent="highlightPrevious()"
                x-on:keydown.enter.prevent="selectHighlighted()"
                placeholder="¿Qué especialidad buscas?" 
                autocomplete="off" 
                class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 pl-11 pr-10 pl-10 text-sm shadow-inner transition-all">
            
            <!-- Heroicon: MagnifyingGlass -->
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
            </div>

            <!-- Botón de Limpieza Dinámica -->
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button type="button" x-show="searchQuery.length > 0" x-on:click="clearSelection()" class="text-slate-400 hover:text-slate-600 focus:outline-none p-1 rounded-full hover:bg-slate-200/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <button type="button" x-show="searchQuery.length === 0" x-on:click="toggleDropdown()" class="text-slate-400 hover:text-slate-600 focus:outline-none p-1">
                    <svg class="w-4 h-4 transition-transform duration-250" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
        </div>

        <!-- Menú Desplegable Flotante Moderno (Limitado a 10 Opciones) -->
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" class="absolute z-50 w-full min-w-[280px] md:min-w-[340px] mt-2 bg-white border border-slate-150/80 rounded-2xl shadow-xl max-h-72 overflow-y-auto p-1.5 space-y-0.5" style="display: none;">
            <template x-for="(specialty, index) in filteredItems().slice(0, 10)" :key="specialty.id">
                <button type="button" x-on:click="selectItem(specialty)" x-on:mouseenter="highlightedIndex = index" :class="highlightedIndex === index ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition flex items-center justify-between group">
                    <span x-text="specialty.name"></span>
                    <svg x-show="selectedSlug === specialty.id" class="w-4 h-4 flex-shrink-0" :class="highlightedIndex === index ? 'text-white' : 'text-indigo-600'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </button>
            </template>
            <div x-show="filteredItems().length === 0" class="px-4 py-3 text-center text-xs font-bold text-slate-400 italic">No encontramos esa especialidad...</div>
        </div>
    </div>
    <!-- 📍 AUTOCOMPLETADO INTERACTIVO: CIUDADES (ZOCDOC STYLE) -->
    <div class="flex-1 min-w-[200px] relative" 
         x-data="cityAutocomplete([
             @foreach($cities as $c)
                 { id: '{{ $c->slug }}', name: '{{ addslashes($c->name) }}' },
             @endforeach
         ])"
         x-on:click.away="closeDropdown()"
         x-on:keydown.escape="closeDropdown()"
    >
        
        <!-- Input oculto para persistencia de la consulta por ubicación -->
        <input type="hidden" name="city" :value="selectedSlug">
        
        <label for="city" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">¿Dónde?</label>
        
        <div class="relative">
            <input 
                type="text"
                id="city"
                x-model="searchQuery"
                x-on:focus="openDropdown()"
                x-on:input="openDropdown()"
                x-on:keydown.arrow-down.prevent="highlightNext()"
                x-on:keydown.arrow-up.prevent="highlightPrevious()"
                x-on:keydown.enter.prevent="selectHighlighted()"
                placeholder="Todas las ciudades" 
                autocomplete="off" 
                class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 pl-11 pr-10 pl-10 text-sm shadow-inner transition-all">
            
            <!-- Heroicon: MapPin -->
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            </div>

            <!-- Botón de Limpieza Dinámica -->
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button type="button" x-show="searchQuery.length > 0" x-on:click="clearSelection()" class="text-slate-400 hover:text-slate-600 focus:outline-none p-1 rounded-full hover:bg-slate-200/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <button type="button" x-show="searchQuery.length === 0" x-on:click="toggleDropdown()" class="text-slate-400 hover:text-slate-600 focus:outline-none p-1">
                    <svg class="w-4 h-4 transition-transform duration-250" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
        </div>

        <!-- Menú Desplegable Flotante de Ciudades (Limitado a 10 Opciones) -->
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" class="absolute z-50 w-full min-w-[280px] md:min-w-[340px] mt-2 bg-white border border-slate-150/80 rounded-2xl shadow-xl max-h-72 overflow-y-auto p-1.5 space-y-0.5" style="display: none;">
            <template x-for="(city, index) in filteredItems().slice(0, 10)" :key="city.id">
                <button type="button" x-on:click="selectItem(city)" x-on:mouseenter="highlightedIndex = index" :class="highlightedIndex === index ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-50'" class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wide transition flex items-center justify-between group">
                    <span x-text="city.name"></span>
                    <svg x-show="selectedSlug === city.id" class="w-4 h-4 flex-shrink-0" :class="highlightedIndex === index ? 'text-white' : 'text-indigo-600'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </button>
            </template>
            <div x-show="filteredItems().length === 0" class="px-4 py-3 text-center text-xs font-bold text-slate-400 italic">No encontramos esa ciudad...</div>
        </div>
    </div>

    <!-- 🚀 BOTÓN DE ACCIÓN CON ICONO Y SPINNER DINÁMICO -->
    <div class="pt-5 w-full md:w-auto">
        <button type="submit" 
                :disabled="loading" 
                :class="loading ? 'opacity-75 cursor-not-allowed bg-indigo-500 shadow-none' : 'bg-indigo-600 hover:bg-indigo-700'" 
                class="w-full text-white px-8 py-3 rounded-2xl font-bold text-sm transition shadow-md uppercase tracking-wider flex items-center justify-center gap-2 min-w-[140px]">
            
            <!-- 🔍 Icono de Lupa Nativo (Visible por defecto, oculto al cargar) -->
            <svg x-show="!loading" class="w-5 h-5 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
            </svg>

            <!-- ⏳ Icono Spinner SVG Animado (Oculto por defecto, visible al cargar) -->
            <svg x-show="loading" class="animate-spin h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" style="display: none;">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <!-- Texto Dinámico Reaccionario -->
            <span x-text="loading ? 'Buscando...' : 'Buscar'">Buscar</span>
        </button>
    </div>

</form>
<script>
    // Orquestador global de envíos: maneja validación y estado de carga de forma segura
    window.handleSearchSubmit = function(event) {
        const specialtyInput = document.querySelector('input[name="specialty"]');
        const cityInput = document.querySelector('input[name="city"]');
        
        const specialtyValue = specialtyInput ? specialtyInput.value : '';
        const cityValue = cityInput ? cityInput.value : '';

        // Si ambos campos están vacíos, bloqueamos el formulario y dejamos loading en false
        if (!specialtyValue.trim() && !cityValue.trim()) {
            event.preventDefault();
            alert('Por favor, selecciona una especialidad o una ciudad de la lista para iniciar la búsqueda.');
            return false;
        }

        // Si la validación es exitosa, permitimos el envío y activamos el spinner de carga (true)
        return true;
    };

    document.addEventListener('alpine:init', () => {
        // Fábrica de lógica de autocompletado (Mantenla exactamente igual aquí abajo...)
        const createAutocompleteEngine = (itemsList, urlParameterName) => ({
            items: itemsList,
            searchQuery: '',
            selectedSlug: new URLSearchParams(window.location.search).get(urlParameterName) || '',
            isOpen: false,
            highlightedIndex: -1,
            init() {
                const activeItem = this.items.find(i => i.id === this.selectedSlug);
                if (activeItem) this.searchQuery = activeItem.name;
            },
            filteredItems() {
                if (this.searchQuery.trim() === '') return this.items;
                const normalizedQuery = this.searchQuery.toLowerCase().trim();
                return this.items.filter(i => i.name.toLowerCase().includes(normalizedQuery));
            },
            openDropdown() { this.isOpen = true; this.highlightedIndex = -1; },
            closeDropdown() { this.isOpen = false; this.highlightedIndex = -1; },
            toggleDropdown() { this.isOpen ? this.closeDropdown() : this.openDropdown(); },
            selectItem(item) {
                this.selectedSlug = item.id;
                this.searchQuery = item.name;
                this.closeDropdown();
            },
            clearSelection() { this.selectedSlug = ''; this.searchQuery = ''; this.closeDropdown(); },
            highlightNext() {
                const maxIndex = Math.min(this.filteredItems().length, 10) - 1;
                this.highlightedIndex = this.highlightedIndex < maxIndex ? this.highlightedIndex + 1 : 0;
            },
            highlightPrevious() {
                const maxIndex = Math.min(this.filteredItems().length, 10) - 1;
                this.highlightedIndex = this.highlightedIndex > 0 ? this.highlightedIndex - 1 : maxIndex;
            },
            selectHighlighted() {
                const activeList = this.filteredItems().slice(0, 10);
                if (this.highlightedIndex >= 0 && activeList[this.highlightedIndex]) {
                    this.selectItem(activeList[this.highlightedIndex]);
                }
            }
        });

        Alpine.data('specialtyAutocomplete', (list) => createAutocompleteEngine(list, 'specialty'));
        Alpine.data('cityAutocomplete', (list) => createAutocompleteEngine(list, 'city'));
    });
</script>
