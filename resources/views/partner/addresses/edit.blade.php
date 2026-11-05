@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Consultorios', 'href' => route('partner.addresses.index')],
    ['name' => 'Editar Sede virtual y fisicas (Consultorios)']
];
@endphp
<x-admin-layout :breadcrumbs="$breadcrumbs">

<div class="py-10 px-4" x-data="{ 
    loading: false, 
    departmentId: '{{ old('department_id', $address->city?->department_id) }}', 
    selectedCityId: '{{ old('city_id', $address->city_id) }}',
    latitude: '{{ old('latitude', $address->latitude) }}',
    longitude: '{{ old('longitude', $address->longitude) }}',
    geoLoading: false,
    geoError: '',
    cities: [],
    citiesLoading: false,
    citiesError: null,
    init() {
        if (this.departmentId) {
            this.fetchCities();
        }
    },
    useMyLocation() {
        if (!navigator.geolocation) {
            this.geoError = 'Tu navegador no permite obtener la ubicación.';
            return;
        }
        this.geoLoading = true;
        this.geoError = '';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.latitude = pos.coords.latitude.toFixed(7);
                this.longitude = pos.coords.longitude.toFixed(7);
                this.geoLoading = false;
            },
            () => {
                this.geoError = 'No pudimos obtener tu ubicación. Revisa el permiso del navegador o ingresa las coordenadas a mano.';
                this.geoLoading = false;
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    },
    async fetchCities() {
        if (!this.departmentId) { 
            this.cities = []; 
            return; 
        }
        this.citiesLoading = true;
        this.citiesError = null;
        try {
            const res = await fetch(`/departments/${this.departmentId}/cities`);
            if (!res.ok) throw new Error('Error al cargar ciudades');
            const data = await res.json();
            this.cities = data;
        } catch (error) {
            console.error('Error fetching cities:', error);
            this.citiesError = 'No se pudieron cargar las ciudades. Intenta de nuevo.';
            this.cities = [];
        } finally {
            this.citiesLoading = false;
        }
    }
}">

    <!-- ENCABEZADO ESTILIZADO DE NIVEL SAAS -->
    <div class="mb-8 border-b border-slate-100 pb-5">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full mb-3 tracking-wide uppercase">
            <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
            Infraestructura Corporativa
        </span>
        <h2 class="text-2xl font-black text-slate-800">Editar Consultorio</h2>
        <p class="text-sm text-slate-500 mt-1">
            Modifica los datos de localización o contacto de esta sede física dentro de la red de OpenDoctorOnline.
        </p>
    </div>

    <!-- TARJETA DEL FORMULARIO PRINCIPAL CON UX PREMIUM -->
    <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 md:p-8 shadow-sm">
        <form action="{{ route('partner.addresses.update', $address) }}" method="POST" @submit.prevent="loading = true; $el.closest('form').submit()" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Mensajes de Error Globales -->
            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="flex flex-col">
                    <x-label value="Nombre de la sede / Sucursal" class="mb-1 text-slate-500 font-bold text-xs" />
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $address->name) }}" 
                           required 
                           placeholder="Ej: Consultorios Norte"
                           autocomplete="organization"
                           class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('name') border-red-400 @enderror">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label value="Dirección" class="mb-1 text-slate-500 font-bold text-xs" />
                    <input type="text" 
                           name="address" 
                           value="{{ old('address', $address->address) }}" 
                           required 
                           placeholder="Ej: Av. Calle 100 # 15-22, Torre B"
                           autocomplete="street-address"
                           class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner @error('address') border-red-400 @enderror">
                    @error('address')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label value="Teléfono de contacto de la sede (Opcional)" class="mb-1 text-slate-500 font-bold text-xs" />
                    <input type="tel" 
                           name="phone" 
                           value="{{ old('phone', $address->phone) }}" 
                           placeholder="Ej: 3026433874"
                           autocomplete="tel"
                           pattern="[0-9+\s\-()]+"
                           title="Solo números, espacios, guiones y paréntesis"
                           class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner @error('phone') border-red-400 @enderror">
                    <p class="text-[10px] text-slate-400 mt-1">Opcional. Solo números, máximo 20 caracteres.</p>
                    @error('phone')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Fila 2: Selector de Departamento y Municipio con marcaje dinámico -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="flex flex-col">
                    <x-label value="Departamento" class="mb-1 text-slate-500 font-bold text-xs" />
                    <select x-model="departmentId" 
                            @change="fetchCities(); selectedCityId = ''" 
                            required 
                            class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner @error('city_id') border-red-400 @enderror">
                        <option value="" selected disabled>Selecciona el departamento</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col">
                    <x-label value="Municipio / Ciudad" class="mb-1 text-slate-500 font-bold text-xs" />
                    <select name="city_id" 
                            x-model="selectedCityId" 
                            required 
                            :disabled="cities.length === 0 || citiesLoading" 
                            class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner disabled:bg-slate-50 disabled:cursor-not-allowed @error('city_id') border-red-400 @enderror">
                        <option value="" selected disabled x-text="citiesLoading ? 'Cargando municipios...' : (cities.length === 0 ? 'Selecciona un departamento primero' : 'Selecciona el municipio')"></option>
                        <template x-for="city in cities" :key="city.id">
                            <option :value="city.id" :selected="city.id == selectedCityId" x-text="city.name"></option>
                        </template>
                    </select>
                    <!-- 🔒 FEEDBACK VISUAL DE ERROR EN CARGA DE CIUDADES -->
                    <template x-if="citiesError">
                        <p class="text-xs text-red-600 mt-1 font-semibold" x-text="citiesError"></p>
                    </template>
                    @error('city_id')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Fila 3: Georreferencia (opcional) -->
            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 md:p-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-black text-slate-700">Ubicación en el mapa <span class="font-semibold text-slate-400">(opcional, recomendado)</span></h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-xl">
                            Con las coordenadas, los pacientes podrán encontrarte en las búsquedas de &quot;doctores cerca de mí&quot;.
                            Para copiarlas: en Google Maps haz clic derecho sobre tu consultorio y toca el primer número que aparece.
                        </p>
                    </div>
                    <button type="button"
                            @click="useMyLocation()"
                            :disabled="geoLoading"
                            :class="geoLoading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-50 text-xs font-bold uppercase tracking-wider transition">
                        <x-heroicon-o-map-pin class="w-4 h-4" />
                        <span x-text="geoLoading ? 'Buscando...' : 'Usar mi ubicación actual'"></span>
                    </button>
                </div>

                <p x-show="geoError" x-cloak x-text="geoError" class="text-xs text-red-600 font-semibold mb-2"></p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="flex flex-col">
                        <x-label for="latitude" value="Latitud" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="number"
                               name="latitude"
                               id="latitude"
                               x-model="latitude"
                               step="any"
                               min="-90"
                               max="90"
                               inputmode="decimal"
                               placeholder="Ej: 4.7109886"
                               class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('latitude') border-red-400 @enderror">
                        @error('latitude')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="longitude" value="Longitud" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="number"
                               name="longitude"
                               id="longitude"
                               x-model="longitude"
                               step="any"
                               min="-180"
                               max="180"
                               inputmode="decimal"
                               placeholder="Ej: -74.0721012"
                               class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('longitude') border-red-400 @enderror">
                        @error('longitude')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[10px] text-slate-400">En Colombia la latitud es positiva o cercana a 0 (Ej: 4.71) y la longitud es negativa (Ej: -74.07). Si las inviertes, la sede quedaría mal ubicada.</p>
                    <a x-show="latitude !== '' && longitude !== ''" x-cloak
                       :href="'https://www.google.com/maps?q=' + latitude + ',' + longitude"
                       target="_blank" rel="noopener"
                       class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                        Verificar en Google Maps
                    </a>
                </div>
            </div>

            <!-- BOTONERA DE ACCIÓN MANTENIENDO TU ESTRUCTURA ORIGINAL -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-4 border-t border-slate-100 pt-5 mt-6">
                <a href="{{ route('partner.addresses.index') }}" 
                   onclick="return confirm('¿Seguro que deseas cancelar? Se perderán los cambios no guardados.')"
                   class="w-full sm:w-auto text-center px-6 py-3.5 border border-slate-200 font-bold rounded-2xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition duration-150 text-xs uppercase tracking-wider">
                    Cancelar
                </a>
                <button type="submit" 
                        :disabled="loading"
                        :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-8 rounded-2xl shadow-md transition duration-150 tracking-wider uppercase text-xs flex items-center justify-center gap-2">
                    <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Guardando...' : 'Guardar Cambios'"></span>
                </button>
            </div>
        </form>
    </div>

</div>
</x-admin-layout>