<x-guest-layout>
    <div class="relative bg-white py-10 overflow-hidden">      
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div x-data="{
                lastSearch: '',
                query: '',
                results: [],
                triageInfo: {
                    active: false,
                    advice: '',
                    urgency: 'Baja',
                    specialtyName: '',
                    slug: ''
                },
                loading: false,
                searchPerformed: false,

                init() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const symptomUrl = urlParams.get('symptom');

                    if (symptomUrl && symptomUrl.trim().length >= 3) {
                        this.query = symptomUrl;
                        this.search();
                    }
                },
                search() {
                    if (this.query.trim() === this.lastSearch) return;
                    if (this.query.trim().length < 3) return;

                    this.loading = true;
                    this.searchPerformed = true;
                    this.lastSearch = this.query.trim();

                    fetch(`{{ route('partner.search.symptom') }}?symptom=${encodeURIComponent(this.query)}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(res => { 
                        if (res.success) {
                            this.results = res.results;
                            this.triageInfo.active        = true;
                            this.triageInfo.advice        = res.triage.advice;
                            this.triageInfo.urgency       = res.triage.urgency;
                            this.triageInfo.specialtyName = res.triage.specialty_name;
                            this.triageInfo.slug          = res.triage.specialty_slug;
                        }
                        this.loading = false;
                    })
                    .catch(() => this.loading = false);
                }
            }">

                <div class="flex flex-col items-center justify-center">
                    <!-- Encabezado de la sección -->
                    <div class="text-center mt-5 mb-8 max-w-2xl">
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">Asistente de Orientación Médica</h1>
                        <p class="text-slate-500 mt-2 text-base">Describe cómo te sientes en lenguaje natural y nuestro sistema inteligente te guiará con el especialista adecuado.</p>
                    </div>

                    <!-- Formulario de búsqueda -->
                    <div class="w-full max-w-4xl mb-6">
                        <form @submit.prevent="search()" class="bg-white p-4 rounded-3xl shadow-2xl flex flex-col md:flex-row gap-4 border border-slate-100">

                            <!-- Input de síntoma -->
                            <div class="flex-1">
                                <label for="symptom" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Qué síntomas tienes?</label>
                                <input type="search" id="symptom" x-model="query" :disabled="loading" name="symptom"
                                    placeholder="Ej: Siento que la habitación me da vueltas al acostarme..."
                                    required minlength="3"
                                    class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 placeholder-slate-400">
                            </div>

                            <!-- Botón Buscar -->
                            <button type="submit" :disabled="loading || query.trim().length < 3"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-black px-10 py-4 rounded-2xl transition disabled:opacity-50 shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                                <!-- Ícono lupa / Spinner -->
                                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loading ? 'Analizando...' : 'Buscar'"></span>
                            </button>
                        </form>
                    </div>

                    <!-- Alerta de Triage Dinámica -->
                    <div class="w-full max-w-5xl my-5 mt-10">
                        <template x-if="triageInfo.active && !loading">
                            <div :class="{
                                    'bg-emerald-50 border-emerald-500 text-emerald-900': triageInfo.urgency === 'Baja',
                                    'bg-amber-50 border-amber-500 text-amber-900':   triageInfo.urgency === 'Media',
                                    'bg-rose-50 border-rose-500 text-rose-900':       triageInfo.urgency === 'Alta'
                                }"
                                class="border-l-4 shadow-sm mb-6 rounded-r-2xl p-4 flex items-start gap-3" role="alert">
                                <span class="text-2xl leading-none">💡</span>
                                <div>
                                    <p class="text-base font-medium mb-1">
                                        <strong>Prioridad <span x-text="triageInfo.urgency"></span>:</strong>
                                        <span x-text="triageInfo.advice"></span>
                                    </p>
                                    <p class="text-sm opacity-90">
                                        Contamos con profesionales calificados para atenderte.
                                        <a :href="`{{ route('search') }}?specialty=${triageInfo.slug}`"
                                        class="underline font-bold ml-1 hover:opacity-80 transition">
                                            Ver nuestros especialistas en <span x-text="triageInfo.specialtyName.toLowerCase()"></span> recomendados →
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Resultados de Médicos -->
                    <div class="w-full max-w-5xl mt-4">

                        <!-- Spinner de carga central -->
                        <template x-if="loading">
                            <div class="text-center py-12 flex flex-col items-center justify-center">
                                <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-slate-500 font-medium">Procesando diagnóstico preliminar de especialidades...</p>
                            </div>
                        </template>

                        <!-- Listado dinámico de doctores -->                                                
                        <template x-if="results.length > 0 && !loading">
                            <div class="space-y-3">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">
                                    <span x-text="results.length"></span> resultados en <span x-text="triageInfo.specialtyName"></span>
                                </p>

                                <template x-for="result in results" :key="result.result_type + '_' + result.id">
                                    <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-300 hover:shadow-sm transition"
                                        :class="result.result_type === 'doctor' ? 'border-l-[3px] border-l-blue-500' : 'border-l-[3px] border-l-emerald-500'">

                                        <div class="flex items-start gap-4">

                                            <!-- Avatar -->
                                            <div class="flex-shrink-0">
                                                <template x-if="result.user && result.user.profile_photo_path">
                                                    <img :src="result.user.profile_photo_url"
                                                        :alt="result.user.name"
                                                        class="w-14 h-14 rounded-full object-cover border border-slate-100">
                                                </template>
                                                <template x-if="!result.user || !result.user.profile_photo_path">
                                                    <div class="w-14 h-14 rounded-full flex items-center justify-center border"
                                                        :class="result.result_type === 'doctor' ? 'bg-blue-50 border-blue-100' : 'bg-emerald-50 border-emerald-100'">
                                                        <span class="text-sm font-bold"
                                                            :class="result.result_type === 'doctor' ? 'text-blue-600' : 'text-emerald-600'"
                                                            x-text="result.user ? result.user.name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'CL'">
                                                        </span>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Info -->
                                            <div class="flex-1 min-w-0">

                                                <!-- Tipo + badge -->
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                                        :class="result.result_type === 'doctor' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'"
                                                        x-text="result.result_type === 'doctor' ? 'Médico' : 'Clínica'">
                                                    </span>
                                                    <template x-if="result.result_type === 'doctor' && result.experience_years">
                                                        <span class="text-xs text-slate-400"
                                                            x-text="result.experience_years + ' años de exp.'">
                                                        </span>
                                                    </template>
                                                </div>

                                                <!-- Nombre -->
                                                <h5 class="text-sm font-bold text-slate-800 mb-2"
                                                    x-text="result.result_type === 'doctor'
                                                        ? (result.gender === 'female' ? 'Dra. ' : 'Dr. ') + result.user.name
                                                        : result.user.name">
                                                </h5>

                                                <!-- Especialidades (doctores) -->
                                                <template x-if="result.result_type === 'doctor' && result.specialties">
                                                    <div class="flex flex-wrap gap-1 mb-3">
                                                        <template x-for="specialty in result.specialties.slice(0, 3)" :key="specialty.id">
                                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100"
                                                                x-text="specialty.name">
                                                            </span>
                                                        </template>
                                                        <template x-if="result.specialties.length > 3">
                                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500"
                                                                x-text="'+' + (result.specialties.length - 3) + ' más'">
                                                            </span>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- Especialidades (clínicas: via doctors) -->
                                                <template x-if="result.result_type === 'clinic' && result.doctors">
                                                    <div class="flex flex-wrap gap-1 mb-3">
                                                        <template x-for="doc in result.doctors.slice(0,2)" :key="doc.id">
                                                            <template x-for="sp in doc.specialties.slice(0,1)" :key="sp.id">
                                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100"
                                                                    x-text="sp.name">
                                                                </span>
                                                            </template>
                                                        </template>
                                                        <template x-if="result.doctors.length > 1">
                                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500"
                                                                x-text="result.doctors.length + ' especialistas'">
                                                            </span>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- Sedes (virtual + físicas) -->
                                                <div class="space-y-1.5">
                                                    <template x-for="address in result.addresses" :key="address.id">
                                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                                            <template x-if="address.type === 'virtual'">
                                                                <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                                                </svg>
                                                            </template>
                                                            <template x-if="address.type !== 'virtual'">
                                                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                </svg>
                                                            </template>
                                                            <span x-text="address.type === 'virtual'
                                                                ? 'Atención Online'
                                                                : address.name + (address.city ? ' · ' + address.city.name : '')">
                                                            </span>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Idiomas (solo doctores con más de 1 idioma) -->
                                                <template x-if="result.result_type === 'doctor' && result.languages && result.languages.length > 1">
                                                    <div class="flex items-center gap-1 mt-2.5">
                                                        <svg class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                                        </svg>
                                                        <span class="text-xs text-slate-400"
                                                            x-text="result.languages.map(l => l === 'es' ? 'Español' : l === 'en' ? 'Inglés' : l).join(' · ')">
                                                        </span>
                                                    </div>
                                                </template>

                                            </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
                                            <span class="text-xs text-slate-400"
                                                x-text="(result.addresses ? result.addresses.length : 0) + ' sede' + (result.addresses && result.addresses.length !== 1 ? 's' : '') + ' disponible' + (result.addresses && result.addresses.length !== 1 ? 's' : '')">
                                            </span>
                                            <a :href="result.result_type === 'doctor' 
                                                ? `/medical-partner/${result.slug}?address_id=${result.addresses[0]?.id ?? ''}&specialty=${triageInfo.slug}`
                                                : `/clinic/${result.slug}/${triageInfo.slug}`"
                                            class="w-full sm:w-auto text-white font-black text-[11px] uppercase tracking-wider text-center py-3.5 px-6 rounded-xl shadow-md shadow-blue-500/10 transition-all transform flex items-center justify-center gap-2.5 min-h-[46px] select-none"
                                            :class="result.result_type === 'doctor' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                                </svg>
                                                agendar cita
                                            </a>
                                        </div>
                                    </div>
                                </template>

                                <!-- Botón ver todos -->
                                <div class="pt-2 text-center">
                                    <a :href="`{{ route('search') }}?specialty=${triageInfo.slug}`"
                                    class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-6 py-3 rounded-full transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                        Ver todos los especialistas en <span x-text="triageInfo.specialtyName.toLowerCase()"></span>
                                    </a>
                                </div>

                            </div>
                        </template>

                        <!-- Estado vacío -->
                        <template x-if="searchPerformed && results.length === 0 && !loading">
                            <div class="text-center py-12 bg-white rounded-3xl border border-slate-100 p-8 max-w-5xl mx-auto shadow-sm">

                                <!-- Ilustración acogedora -->
                                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-5">
                                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>

                                <!-- Mensaje empático principal -->
                                <h3 class="text-xl font-black text-slate-800 mb-2">
                                    Entendemos lo que buscas 💙
                                </h3>
                                <p class="text-slate-500 text-sm font-medium max-w-md mx-auto leading-relaxed mb-2">
                                    Nuestro sistema identificó tu necesidad, pero aún estamos incorporando especialistas a la plataforma.
                                    <strong class="text-slate-700">Estamos creciendo para ti.</strong>
                                </p>

                                <!-- Triage mostrado de todas formas (el consejo sigue siendo valioso) -->
                                <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-semibold px-4 py-2 rounded-full mb-8 border border-blue-100">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tu síntoma fue analizado correctamente — solo nos faltan los especialistas disponibles
                                </div>

                                <!-- Opciones humanizadas -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left mb-8">

                                    <!-- Opción 1: Notificarme -->
                                    <div class="bg-gradient-to-br from-blue-50 to-slate-50 border border-blue-100 rounded-2xl p-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl">🔔</span>
                                            <h4 class="text-sm font-black text-slate-800">Avísame cuando haya disponibilidad</h4>
                                        </div>
                                        <p class="text-xs text-slate-500 leading-relaxed mb-3">
                                            Déjanos tu correo y te notificamos en cuanto un especialista en
                                            <strong x-text="triageInfo.specialtyName"></strong> esté disponible.
                                        </p>

                                        <div x-data="{
                                            email: '',
                                            loading: false,
                                            sent: false,
                                            error: '',
                                            async submit() {
                                                this.error = '';
                                                if (!this.email) { this.error = 'Ingresa tu correo.'; return; }
                                                this.loading = true;
                                                try {
                                                    const res = await fetch('{{ route('availability.notify') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                        },
                                                        body: JSON.stringify({
                                                            email: this.email,
                                                            specialty: triageInfo.specialtyName
                                                        })
                                                    });
                                                    const data = await res.json();
                                                    if (res.ok) {
                                                        this.sent = true;
                                                    } else {
                                                        this.error = data.message ?? 'Ocurrió un error. Intenta de nuevo.';
                                                    }
                                                } catch (e) {
                                                    this.error = 'No se pudo conectar. Intenta de nuevo.';
                                                } finally {
                                                    this.loading = false;
                                                }
                                            }
                                        }">
                                            <!-- Estado de éxito -->
                                            <div x-show="sent" x-transition
                                                class="flex items-center gap-2 text-xs text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2">
                                                <span>✅</span>
                                                <span>¡Listo! Te avisaremos cuando haya disponibilidad.</span>
                                            </div>

                                            <!-- Formulario -->
                                            <div x-show="!sent" class="flex flex-col gap-2">
                                                <div class="flex gap-2">
                                                    <input
                                                        type="email"
                                                        x-model="email"
                                                        placeholder="tu@correo.com"
                                                        :disabled="loading"
                                                        @keydown.enter="submit()"
                                                        class="flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-300 focus:outline-none bg-white disabled:opacity-50"
                                                    >
                                                    <button
                                                        @click="submit()"
                                                        :disabled="loading"
                                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1"
                                                    >
                                                        <span x-show="loading" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                        <span x-text="loading ? 'Enviando…' : 'Avisar'"></span>
                                                    </button>
                                                </div>

                                                <!-- Error -->
                                                <p x-show="error" x-text="error" class="text-xs text-red-500 pl-1"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Opción 2: Consulta general -->
                                    <div class="bg-gradient-to-br from-emerald-50 to-slate-50 border border-emerald-100 rounded-2xl p-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl">🩺</span>
                                            <h4 class="text-sm font-black text-slate-800">Habla con un médico general</h4>
                                        </div>
                                        <p class="text-xs text-slate-500 leading-relaxed mb-3">
                                            Mientras conseguimos al especialista ideal, un médico general puede orientarte y darte tranquilidad hoy mismo.
                                        </p>
                                        <a href="{{ route('search') }}?specialty=medicina-general"
                                        class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                                            Ver médicos generales
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>

                                </div>

                                <!-- Mensaje tranquilizador de cierre -->
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 max-w-md mx-auto">
                                    <p class="text-xs text-slate-400 leading-relaxed">
                                        🌱 <strong class="text-slate-600">opendoctor.online</strong> está en pleno crecimiento.
                                        Cada semana sumamos nuevos profesionales de la salud. Tu búsqueda nos ayuda a saber
                                        qué especialidades priorizar. <strong class="text-slate-600">¡Gracias por confiar en nosotros!</strong>
                                    </p>
                                </div>

                            </div>
                        </template>

                    </div>
                </div>
            </div> 
        </div>       
    </div>
</x-guest-layout>