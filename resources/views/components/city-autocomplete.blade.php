@props(['name' => 'city', 'placeholder' => 'Escribe tu ciudad para viajar...'])

<div x-data="cityAutocomplete()" class="w-full">
    <div class="relative">
        <input
            type="text"
            name="{{ $name }}"
            x-model="searchQuery"
            @input="search()"
            @focus="showSuggestions = true"
            @blur="setTimeout(() => showSuggestions = false, 200)"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 placeholder-gray-500"
        />
        
        <!-- Ícono de geolocalización -->
        <button
            type="button"
            @click="getCurrentLocation()"
            :disabled="loading"
            class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
            title="Detectar tu ubicación"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>
    </div>
    
    <!-- Sugerencias -->
    <div
        x-show="showSuggestions && suggestions.length > 0"
        @click.outside="showSuggestions = false"
        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-72 overflow-y-auto"
    >
        <template x-for="city in suggestions" :key="city.id">
            <button
                type="button"
                @click="selectCity(city)"
                class="w-full text-left px-4 py-3 hover:bg-blue-50 transition cursor-pointer border-b border-gray-100 last:border-b-0"
            >
                <div class="font-medium text-gray-900" x-text="city.name"></div>
                <div class="text-sm text-gray-500">
                    <span x-text="city.adminName1"></span>
                    <span x-show="city.adminName1">,</span>
                    <span x-text="city.countryName"></span>
                </div>
            </button>
        </template>
    </div>
    
    <!-- Mensaje de carga -->
    <div x-show="loading" class="text-sm text-gray-500 mt-2">
        Buscando ciudades...
    </div>
    
    <!-- Campos ocultos para guardar datos -->
    <input type="hidden" :value="selectedCity.id || ''" name="{{ $name }}_id" />
    <input type="hidden" :value="selectedCity.countryCode || ''" name="{{ $name }}_countryCode" />
    <input type="hidden" :value="selectedCity.lat || ''" name="{{ $name }}_lat" />
    <input type="hidden" :value="selectedCity.lng || ''" name="{{ $name }}_lng" />
    <input type="hidden" :value="selectedCity.name || ''" name="{{ $name }}_name" />
    <input type="hidden" :value="selectedCity.countryName || ''" name="{{ $name }}_countryName" />
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cityAutocomplete', () => ({
            searchQuery: '',
            suggestions: [],
            showSuggestions: false,
            loading: false,
            selectedCity: {},
            searchTimeout: null,
            
            search() {
                clearTimeout(this.searchTimeout);
                
                // Si es muy corta, muestra ciudades principales
                if (this.searchQuery.length < 2) {
                    this.suggestions = this.getDefaultCities();
                    this.showSuggestions = this.suggestions.length > 0;
                    return;
                }
                
                this.loading = true;
                this.searchTimeout = setTimeout(() => {
                    fetch(`{{ route('cities.search') }}?q=${encodeURIComponent(this.searchQuery)}`)
                        .then(res => res.json())
                        .then(data => {
                            // Muestra datos incluso si hay fallback
                            if (data.data && data.data.length > 0) {
                                this.suggestions = data.data;
                                this.showSuggestions = true;
                            } else {
                                this.suggestions = [];
                                this.showSuggestions = false;
                                if (data.message) console.log(data.message);
                            }
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            this.suggestions = [];
                            this.showSuggestions = false;
                            this.loading = false;
                        });
                }, 300);
            },
            
            selectCity(city) {
                this.selectedCity = city;
                this.searchQuery = `${city.name}, ${city.countryName}`;
                this.suggestions = [];
                this.showSuggestions = false;
                this.$dispatch('city-selected', city);
            },
            
            getCurrentLocation() {
                if (!navigator.geolocation) {
                    alert('La geolocalización no está disponible en tu navegador');
                    return;
                }
                
                this.loading = true;
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        
                        // Usa nuevo endpoint
                        fetch(`{{ route('cities.nearby') }}?lat=${latitude}&lng=${longitude}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.data && data.data.length > 0) {
                                    this.selectCity(data.data[0]);
                                } else {
                                    alert('No se encontraron ciudades cercanas');
                                }
                                this.loading = false;
                            })
                            .catch(err => {
                                console.error('Error:', err);
                                alert('Error al buscar tu ubicación');
                                this.loading = false;
                            });
                    },
                    (error) => {
                        console.error('Error de geolocalización:', error);
                        alert('No pudimos detectar tu ubicación');
                        this.loading = false;
                    }
                );
            },
            
            getDefaultCities() {
                return [
                    { id: 3686110, name: 'Bogotá', countryName: 'Colombia', countryCode: 'CO', adminName1: 'Bogotá' },
                    { id: 3674730, name: 'Medellín', countryName: 'Colombia', countryCode: 'CO', adminName1: 'Antioquia' },
                    { id: 3687238, name: 'Cali', countryName: 'Colombia', countryCode: 'CO', adminName1: 'Valle del Cauca' },
                    { id: 3668740, name: 'Barranquilla', countryName: 'Colombia', countryCode: 'CO', adminName1: 'Atlántico' },
                    { id: 3657050, name: 'Cartagena', countryName: 'Colombia', countryCode: 'CO', adminName1: 'Bolívar' },
                ];
            }
        }));
    });
</script>