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

window.nearbyDoctorsMap = function ({ radius, endpoint }) {
    return {
        radius,
        endpoint,
        state: 'idle',
        statusMessage: 'Haz clic para ver especialistas cercanos',
        loadingMessage: '',
        errorMessage: '',
        doctors: [],
        userLocation: null,
        map: null,
        markersLayer: null,

        openModal() {
            if (!('geolocation' in navigator)) {
                alert('Tu navegador no soporta geolocalización. No podemos mostrar especialistas cercanos.');
                return;
            }

            this.$refs.dialog.showModal();
            this.state = 'loading';
            this.loadingMessage = 'Obteniendo tu ubicación actual...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.userLocation = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                    };
                    this.fetchDoctors();
                },
                (err) => this.handleGeolocationError(err),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        handleGeolocationError(err) {
            this.$refs.dialog.close();
            this.resetState();

            const messages = {
                [err.PERMISSION_DENIED]: 'Es requerido habilitar el permiso de ubicación en tu navegador para mostrarte especialistas cercanos.',
                [err.POSITION_UNAVAILABLE]: 'La información de ubicación no está disponible en este momento.',
                [err.TIMEOUT]: 'La solicitud de ubicación tardó demasiado. Intenta nuevamente.',
            };

            alert(messages[err.code] ?? 'No podemos mostrar especialistas cercanos porque no tenemos acceso a tu ubicación.');
        },

        async fetchDoctors() {
            this.loadingMessage = 'Buscando especialistas cercanos...';

            try {
                const params = new URLSearchParams({
                    latitude: this.userLocation.lat,
                    longitude: this.userLocation.lng,
                    radius: this.radius,
                });

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Error de red al consultar especialistas.');

                const payload = await response.json();
                if (!payload.success) throw new Error('Formato de respuesta inválido.');

                this.doctors = payload.doctors;

                if (this.doctors.length === 0) {
                    this.state = 'empty';
                    this.statusMessage = 'No se encontraron especialistas en tu zona';
                    return;
                }

                this.state = 'ready';
                this.statusMessage = `${this.doctors.length} especialista(s) a menos de ${this.radius} km`;

                await this.$nextTick();
                this.renderMap();
            } catch (error) {
                console.error(error);
                this.state = 'error';
                this.errorMessage = 'Ocurrió un error al buscar especialistas. Cierra e intenta de nuevo.';
            }
        },

        renderMap() {
            if (this.map) {
                this.map.invalidateSize();
                this.plotMarkers();
                return;
            }

            this.map = L.map(this.$refs.mapContainer, {
                zoomControl: false,
            }).setView([this.userLocation.lat, this.userLocation.lng], 13);

            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            L.tileLayer('https://{s}://{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 19,
            }).addTo(this.map);

            this.markersLayer = L.layerGroup().addTo(this.map);

            const userIcon = L.divIcon({
                className: 'bg-transparent',
                html: `<div class="relative flex items-center justify-center w-6 h-6">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full w-4 h-4 bg-blue-600 border-2 border-white shadow-md"></span>
                       </div>`,
                iconSize: new L.Point(24, 24),
                iconAnchor: new L.Point(12, 12),
            });

            L.marker([this.userLocation.lat, this.userLocation.lng], { icon: userIcon })
                .addTo(this.map)
                .bindPopup('<div class="font-sans text-xs font-semibold text-gray-800 px-1">Tu ubicación actual</div>');

            this.plotMarkers();
        },

        plotMarkers() {
            if (!this.markersLayer) return;
            this.markersLayer.clearLayers();

            const bounds = L.latLngBounds([[this.userLocation.lat, this.userLocation.lng]]);

            const doctorPin = L.divIcon({
                className: 'bg-transparent',
                html: `<svg class="w-8 h-8 text-red-600 drop-shadow-md -mt-4 -ml-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                       </svg>`,
                iconSize: new L.Point(32, 32),
                iconAnchor: new L.Point(16, 32),
                popupAnchor: new L.Point(0, -32),
            });

            this.doctors.forEach((doctor) => {
                if (!doctor.latitude || !doctor.longitude) return;

                L.marker([doctor.latitude, doctor.longitude], { icon: doctorPin })
                    .addTo(this.markersLayer)
                    .bindPopup(() => this.createPopupNode(doctor));

                bounds.extend([doctor.latitude, doctor.longitude]);
            });

            if (this.doctors.length > 0) {
                this.map.fitBounds(bounds, {
                    padding: new L.Point(60, 60),
                    maxZoom: 15,
                });
            }
        },

        createPopupNode(doctor) {
            const container = document.createElement('div');
            container.className = 'p-1.5 min-w-[230px] font-sans';

            const title = document.createElement('h3');
            title.className = 'font-bold text-gray-900 text-sm leading-snug mb-1';
            title.textContent = doctor.name;
            container.appendChild(title);

            if (doctor.distance !== null) {
                const dist = document.createElement('p');
                dist.className = 'text-xs text-gray-500 mb-2 flex items-center gap-1';
                dist.textContent = `📍 A ${doctor.distance} km de ti`;
                container.appendChild(dist);
            }

            const slotBox = document.createElement('div');
            slotBox.className = 'bg-emerald-50 border border-emerald-100 rounded-md px-2.5 py-1.5 mb-3 text-xs';
            slotBox.innerHTML = '<span class="text-gray-600 block text-[11px]">Próximo turno disponible:</span>';
            
            const slotBadge = document.createElement('span');
            slotBadge.className = 'font-bold text-emerald-700';
            slotBadge.textContent = doctor.nextAppointment;
            slotBox.appendChild(slotBadge);
            container.appendChild(slotBox);

            const link = document.createElement('a');
            link.href = doctor.profileUrl;
            link.className = 'block w-full text-center bg-blue-600 hover:bg-blue-700 !text-white text-xs font-semibold py-2 px-3 rounded-lg shadow-xs transition-colors';
            link.textContent = 'Agendar Cita';
            container.appendChild(link);

            return container;
        },

        closeAndCleanup() {
            this.resetState();
            if (this.map) {
                this.map.remove();
                this.map = null;
                this.markersLayer = null;
            }
        },

        resetState() {
            this.state = 'idle';
            this.doctors = [];
            this.userLocation = null;
            this.errorMessage = '';
            this.statusMessage = 'Haz clic para ver especialistas cercanos';
        },
    };
};
