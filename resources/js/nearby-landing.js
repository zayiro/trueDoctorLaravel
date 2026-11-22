import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const TILES = window.OD_TILES || '';

const doctorPin = L.divIcon({
    className: 'bg-transparent',
    html: `<svg class="w-8 h-8 text-red-600 drop-shadow-md -mt-4 -ml-2" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
           </svg>`,
    iconSize: new L.Point(32, 32),
    iconAnchor: new L.Point(16, 32),
    popupAnchor: new L.Point(0, -32),
});

const youIcon = L.divIcon({
    className: 'bg-transparent',
    html: `<div class="relative flex items-center justify-center w-6 h-6">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full w-4 h-4 bg-blue-600 border-2 border-white shadow-md"></span>
           </div>`,
    iconSize: new L.Point(24, 24),
    iconAnchor: new L.Point(12, 12),
});

window.nearbyLanding = function ({ endpoint, cities, initial }) {
    // El mapa y los marcadores viven fuera del estado reactivo de Alpine (evita proxies sobre objetos de Leaflet).
    let map = null;
    let layer = null;
    const markers = {};

    return {
        endpoint,
        cities,
        cityKey: initial.cityKey,
        mode: initial.mode, // 'city' | 'exact'
        center: initial.center,
        radius: initial.radius,
        baseRadius: initial.radius,
        doctors: initial.doctors,
        hydrated: false,
        loading: false,
        locating: false,
        errorMessage: '',
        geoMessage: '',

        get cityName() {
            return this.cities[this.cityKey]?.name ?? '';
        },
        get placeLabel() {
            return this.mode === 'exact' ? 'tu ubicación' : this.cityName;
        },
        get heading() {
            return this.mode === 'exact' ? 'Doctores cerca de mí' : `Doctores cerca de mí en ${this.cityName}`;
        },

        init() {
            this.hydrated = true;
            this.$nextTick(() => this.initMap());
        },

        initMap() {
            const el = this.$refs.map;
            if (!el || map) return;

            map = L.map(el, {
                zoomControl: false,
                scrollWheelZoom: false,        // no secuestra el scroll de la página
                dragging: !L.Browser.mobile,   // en celular un dedo desplaza la página, no el mapa
            }).setView([this.center.lat, this.center.lng], 12);

            L.control.zoom({ position: 'bottomright' }).addTo(map);

            L.tileLayer(TILES, {
                attribution: '&copy; OpenStreetMap contributors, &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 19,
            }).addTo(map);

            layer = L.layerGroup().addTo(map);
            this.plot();

            // Medir de nuevo cuando el contenedor ya tiene su tamaño final y cada vez que cambie.
            setTimeout(() => { if (map) { map.invalidateSize(); this.plot(); } }, 250);
            if (typeof ResizeObserver !== 'undefined') {
                new ResizeObserver(() => { if (map) map.invalidateSize(); }).observe(el);
            }
        },

        plot() {
            if (!map || !layer) return;

            layer.clearLayers();
            Object.keys(markers).forEach((k) => delete markers[k]);

            const bounds = L.latLngBounds([]);

            if (this.mode === 'exact') {
                L.marker([this.center.lat, this.center.lng], { icon: youIcon, zIndexOffset: 500 })
                    .addTo(layer)
                    .bindPopup('<div class="font-sans text-xs font-semibold text-gray-800 px-1">Tu ubicación actual</div>');
                bounds.extend([this.center.lat, this.center.lng]);
            }

            this.doctors.forEach((d) => {
                if (d.latitude == null || d.longitude == null) return;

                markers[d.key] = L.marker([d.latitude, d.longitude], { icon: doctorPin })
                    .addTo(layer)
                    .bindPopup(() => this.popupNode(d));

                bounds.extend([d.latitude, d.longitude]);
            });

            if (this.doctors.length > 0 && bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            } else {
                map.setView([this.center.lat, this.center.lng], this.mode === 'exact' ? 13 : 12);
            }
        },

        distanceLabel(d) {
            if (d.distance === null || d.distance === undefined) return '';
            return this.mode === 'exact'
                ? `A ${d.distance} km de ti`
                : `A ${d.distance} km del centro de ${this.cityName}`;
        },

        popupNode(d) {
            const container = document.createElement('div');
            container.className = 'p-1.5 min-w-[230px] font-sans';

            const title = document.createElement('h3');
            title.className = 'font-bold text-gray-900 text-sm leading-snug mb-1';
            title.textContent = d.name;
            container.appendChild(title);

            const dist = document.createElement('p');
            dist.className = 'text-xs text-gray-500 mb-2';
            dist.textContent = `📍 ${this.distanceLabel(d)}`;
            container.appendChild(dist);

            if (d.nextAppointment) {
                const slotBox = document.createElement('div');
                slotBox.className = 'bg-emerald-50 border border-emerald-100 rounded-md px-2.5 py-1.5 mb-3 text-xs';
                slotBox.innerHTML = '<span class="text-gray-600 block text-[11px]">Próximo turno disponible:</span>';
                const badge = document.createElement('span');
                badge.className = 'font-bold text-emerald-700';
                badge.textContent = d.nextAppointment;
                slotBox.appendChild(badge);
                container.appendChild(slotBox);
            }

            const link = document.createElement('a');
            link.href = d.profileUrl;
            link.className = 'block w-full text-center bg-blue-600 hover:bg-blue-700 !text-white text-xs font-semibold py-2 px-3 rounded-lg shadow-xs transition-colors';
            link.textContent = 'Agendar Cita';
            link.style.setProperty('color', '#ffffff', 'important'); // Leaflet pinta de azul los enlaces del mapa
            container.appendChild(link);

            return container;
        },

        focusDoctor(d) {
            const m = markers[d.key];
            if (!m || !map) return;
            this.$refs.mapWrap?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            map.setView(m.getLatLng(), Math.max(map.getZoom(), 15));
            m.openPopup();
        },

        selectCity(key) {
            const c = this.cities[key];
            if (!c) return;

            this.cityKey = key;
            this.mode = 'city';
            this.center = { lat: c.lat, lng: c.lng };
            this.radius = this.baseRadius;
            this.geoMessage = '';
            this.syncUrl(key);

            return this.refresh();
        },

        useMyLocation() {
            this.geoMessage = '';

            if (!('geolocation' in navigator)) {
                this.geoMessage = 'Tu navegador no permite obtener la ubicación. Elige tu ciudad en la lista.';
                return;
            }

            this.locating = true;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.locating = false;
                    this.mode = 'exact';
                    this.cityKey = '';
                    this.center = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    this.radius = this.baseRadius;
                    this.syncUrl(null);
                    this.rememberLocation();
                    this.refresh();
                },
                (err) => {
                    this.locating = false;
                    if (err.code === 1) {
                        try { localStorage.setItem('opendoctor_location_consent', 'denied'); } catch (e) { /* no es crítico */ }
                    }
                    const messages = {
                        1: 'No pudimos acceder a tu ubicación. Puedes darle permiso al navegador o elegir tu ciudad en la lista.',
                        2: 'Tu ubicación no está disponible en este momento. Elige tu ciudad en la lista.',
                        3: 'Obtener tu ubicación tardó demasiado. Intenta de nuevo o elige tu ciudad en la lista.',
                    };
                    this.geoMessage = messages[err.code] ?? 'No pudimos obtener tu ubicación. Elige tu ciudad en la lista.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        },

        /** Guarda la ubicación en la sesión (para otras páginas y próximas visitas) y evita volver a pedir el permiso. */
        rememberLocation() {
            try { localStorage.setItem('opendoctor_location_consent', 'allowed'); } catch (e) { /* no es crítico */ }

            try {
                fetch('/api/session/location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ latitude: this.center.lat, longitude: this.center.lng }),
                }).catch(() => {});
            } catch (e) { /* no es crítico */ }
        },

        widenSearch() {
            this.radius = 100;
            return this.refresh();
        },

        async refresh() {
            this.loading = true;
            this.errorMessage = '';

            try {
                const params = new URLSearchParams({
                    latitude: this.center.lat,
                    longitude: this.center.lng,
                    radius: this.radius,
                });

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const payload = await response.json();
                if (!payload.success) throw new Error('Respuesta inválida');

                this.doctors = payload.doctors;
            } catch (error) {
                console.error(error);
                this.doctors = [];
                this.errorMessage = 'No pudimos cargar los especialistas en este momento. Intenta de nuevo en unos minutos.';
            } finally {
                this.loading = false;
            }

            this.plot();
        },

        syncUrl(key) {
            try {
                const url = new URL(window.location.href);
                if (key) url.searchParams.set('ciudad', key);
                else url.searchParams.delete('ciudad');
                window.history.replaceState(null, '', url);
            } catch (e) { /* no es crítico */ }
        },
    };
};
