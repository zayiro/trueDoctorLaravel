{{--
Perfil público completo del médico.
Variables: $doctor (App\Models\Doctor) y, opcional, $bookingUrl (enlace del botón "Agendar cita").
Carga recomendada en el controlador:
    $doctor->load(['user', 'specialties', 'addresses' => fn ($q) => $q->bookable()->with(['city', 'services'])]);
    Las secciones se muestran SOLO si hay datos; los campos opcionales se leen con data_get() (ajusta los nombres a tus columnas).
--}}
@php
    $user        = $doctor->user;
    $name        = $user?->name ?? 'Especialista';
    $photo       = $user?->profile_photo_path;
    $specialties = $doctor->specialties ?? collect();
    $primary     = $specialties->first()?->name ?? 'Médico';

    $addresses   = ($doctor->addresses ?? collect())->where('status', true)->values();
    $physical    = $addresses->where('type', 'physical')->values();
    $hasVirtual  = $addresses->where('type', 'virtual')->isNotEmpty();
    $offerings   = $addresses->filter(fn ($a) => $a->services->isNotEmpty())->values();   // sedes con servicios (cada uno es independiente por sede)

    $bio       = data_get($doctor, 'bio') ?? data_get($doctor, 'description') ?? data_get($doctor, 'about');
    $years     = data_get($doctor, 'years_experience') ?? data_get($doctor, 'experience_years');
    $license   = data_get($doctor, 'license_number') ?? data_get($doctor, 'medical_license');
    $languages = data_get($doctor, 'languages');
    $languages = is_array($languages) ? implode(', ', $languages) : $languages;

    $modalities = collect([$physical->isNotEmpty() ? 'Presencial' : null, $hasVirtual ? 'Telemedicina' : null])->filter()->implode(' · ');
    $isVerified = (string) data_get($doctor, 'validation_status') === 'approved';
    $bookingUrl = $bookingUrl ?? '#servicios';

    $mapsUrl = function ($address) {
        return $address->hasCoordinates()
            ? 'https://www.google.com/maps?q=' . $address->latitude . ',' . $address->longitude
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address->address . ' ' . ($address->city->name ?? ''));
    };

    $jsonLd = array_filter([
        '@context'         => 'https://schema.org',
        '@type'            => 'Physician',
        'name'             => $name,
        'image'            => $photo,
        'url'              => url()->current(),
        'medicalSpecialty' => $specialties->pluck('name')->values()->all() ?: null,
        'address'          => $physical->map(fn ($a) => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $a->address,
            'addressLocality' => $a->city->name ?? null,
            'addressCountry'  => $a->country_code ?? 'CO',
        ])->values()->all() ?: null,
    ]);
@endphp

<x-guest-layout
    meta_title="Perfil de Doctor o Centro Médico | OpenDoctor"
    meta_description="Perfil Médico especializado"
>

<div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 font-sans my-6">
    <div class="grid grid-cols-1 md:grid-cols-12 w-full">
        
        {{-- COLUMNA IZQUIERDA: Imagen y Biografía --}}
        <div class="md:col-span-5 flex flex-col bg-gray-50/50 w-full border-r border-gray-100">           
            {{-- 📷 RENDERIZADO DIRECTO: Flujo natural sin absolute/relative --}}
            @if ($photo)
                <img 
                    src="{{ \Storage::url($photo) }}" 
                    alt="{{ $doctor->user->name }}" 
                    class="w-full h-auto max-h-[32rem] object-cover"
                    decoding="async"
                >
            @else
                <div class="w-full py-16 text-center text-7xl font-black text-slate-300 bg-slate-100 uppercase select-none">
                    {{ mb_substr($doctor->user->name, 0, 1) }}
                </div>
            @endif

            {{-- Sección: Biografía --}}
            <div class="p-6 flex-1 bg-white">
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-2">Biografía</h3>
                <div class="text-md text-gray-600 leading-relaxed space-y-3">
                    <p>{{ $doctor->bio ?? 'Sin descripción profesional disponible en este momento.' }}</p>
                    @if($doctor->experience_years)
                        <p class="text-md text-gray-500 italic">Formado con los más altos estándares científicos y éticos en salud.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white p-6 sm:p-8 max-w-4xl mx-auto my-6 font-sans">
    
                <!-- Cabecera de la Sección -->
                <div class="flex items-center gap-2 mb-6">
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Reseñas de pacientes</h3>
                    <div class="flex items-center gap-1.5 text-sm font-medium text-slate-500">
                        <!-- Icono de Estrella -->
                        <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20">
                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                        </svg>
                        <span class="text-slate-900 font-semibold">{{ number_format($doctor->rating ?? 0.0, 1) }}</span>
                        <span>({{ $doctor->reviews_count ?? 0 }} reseñas)</span>
                    </div>
                </div>

                <!-- Alerta: Iniciar Sesión (Solo si el usuario no está autenticado como paciente) -->
                @if(!auth()->check() || auth()->user()->role !== 'patient')
                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3.5 text-sm text-slate-600 mb-6 leading-relaxed">
                        Inicia sesión como paciente para dejar una reseña sobre este profesional.
                    </div>
                @endif

                <!-- Contenedor de Estado Vacío (Empty State) -->
                {{-- Puedes condicionar esto con un @if($doctor->reviews->isEmpty()) --}}
                <div class="border border-dashed border-slate-200 rounded-2xl p-10 text-center flex flex-col items-center justify-center bg-white">
                    <!-- Icono del Mensaje / Globo de diálogo -->
                    <div class="bg-slate-50 rounded-full p-4 text-slate-400 mb-4 flex items-center justify-center shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641l-.318 2.396 2.396-.318a2.25 2.25 0 011.508.473A8.966 8.966 0 0012 20.25z" />
                        </svg>
                    </div>

                    <!-- Textos del Estado Vacío -->
                    <h4 class="text-base font-bold text-slate-900 tracking-tight mb-2">
                        Aún no hay reseñas públicas
                    </h4>
                    <p class="text-sm text-slate-500 max-w-md mx-auto leading-normal">
                        Los pacientes podrán dejar reseñas una vez completen una cita con el profesional.
                    </p>
                </div>

            </div>


        </div>
        {{-- COLUMNA DERECHA: Detalles Profesionales y Contacto --}}
        <div class="md:col-span-7 p-6 md:p-8 flex flex-col justify-between bg-white space-y-6">
            
            {{-- Bloque de Perfil / Hospital --}}
            <div>
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-1">Perfil Profesional</h3>
                <h2 class="text-base font-bold text-gray-800">Dr. {{ $doctor->user->name }}</h2>
                <p class="text-md text-gray-500 mt-0.5 font-medium">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
                
                {{-- Credenciales Académicas --}}
                <div class="mt-3 text-md text-gray-600 space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100/80">
                    <p class="font-medium text-gray-700">Licencia Médica: <span class="font-normal text-gray-600">{{ $doctor->medical_license ?? 'En proceso de verificación' }}</span></p>
                    <p class="font-medium text-gray-700">Institución: <span class="font-normal text-gray-600">{{ $doctor->clinic?->user->name ?? 'Consultorio Independiente' }}</span></p>
                </div>
            </div>

            {{-- Sección: Especialidades / Enfoques (Badges) --}}
            <div>
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-2.5">Especialidades</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($doctor->specialties as $specialty)
                        <span class="px-3 py-1 rounded-full text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200">
                            {{ $specialty->name }}
                        </span>
                    @empty
                        <span class="px-3 py-1 rounded-full text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200">
                            Medicina General
                        </span>
                    @endforelse
                </div>
            </div>

            {{-- Fila de Datos Rápidos: Experiencia, Idiomas, Tipo --}}
            <div class="grid grid-cols-3 gap-4 border-t border-b border-gray-100 py-4 text-md">
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-md">Experiencia</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->experience_years ?? '0' }} Años+</p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-md">Idiomas</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">
                        {{ is_array($doctor->languages) ? implode(', ', $doctor->languages) : 'Español' }}
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-md">Vinculación</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->clinic_id ? 'Staff de Clínica' : 'Médico Autónomo' }}</p>
                </div>
            </div>

            {{-- Sección Inferior: Datos de Contacto --}}
      
            <div class="text-md text-gray-600">

                {{-- Extracción segura de la dirección --}}
                @php
                    $activeAddress = $doctor->addresses->first();
                @endphp

                @if($activeAddress)
                    <div class="mt-4 pl-4 border-l-2 border-sky-500 py-0.5 space-y-0.5">
                        <p class="font-bold text-sky-600 uppercase tracking-wider text-md">Modalidad de consulta</p>
                        <p class="text-gray-600 leading-tight">{{ $activeAddress->address }}</p>
                        <p class="text-md text-gray-400 font-medium">{{ $activeAddress->name }}</p>
                    </div>
                @endif
                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8 mt-5"
                    x-data="{ 
                        nameText: '',
                        emailText: '',
                        phoneText: '',
                        messageText: '',
                        isSubmitting: false,
                        isSuccess: false,
                        errorMessage: '',
                        
                        // Método opcional para procesar por Fetch de forma elegante
                        async submitForm() {
                            this.isSubmitting = true;
                            this.errorMessage = '';
                            
                            try {
                                // Modifica esta URL por tu ruta real de Laravel (ej: route('contact.submit'))
                                const response = await fetch('/partner-profile/contact', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        doctor_id: '{{ $doctor->id }}',
                                        client_name: this.nameText,
                                        client_email: this.emailText,
                                        client_phone: this.phoneText,
                                        message_content: this.messageText
                                    })
                                });

                                if (!response.ok) throw new Error('Hubo un problema al enviar el mensaje.');
                                
                                this.isSuccess = true;
                                // Limpiar campos tras el éxito
                                this.nameText = '';
                                this.emailText = '';
                                this.phoneText = '';
                                this.messageText = '';
                            } catch (error) {
                                this.errorMessage = error.message;
                            } finally {
                                this.isSubmitting = false;
                            }
                        }
                    }">

                    <!-- PANTALLA DE ÉXITO (Muestra un estado premium tras enviar el mensaje) -->
                    <div x-show="isSuccess" x-cloak class="text-center py-8 space-y-4">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">¡Mensaje enviado con éxito!</h3>
                        <p class="text-sm text-slate-500 max-w-xs mx-auto">
                            El especialista ha recibido tu solicitud y se pondrá en contacto contigo a la mayor brevedad.
                        </p>
                        <button type="button" @click="isSuccess = false" class="text-xs font-semibold text-sky-600 hover:text-sky-700 underline">
                            Enviar otro mensaje
                        </button>
                    </div>

                    <!-- CUERPO DEL FORMULARIO -->
                    <div x-show="!isSuccess">
                        <!-- Encabezado -->
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-slate-900 leading-tight">
                                Contactar con {{ $doctor->gender === 'female' ? 'Dra. ' : 'Dr. ' }}{{ $doctor->user->name }}
                            </h3>
                            <p class="text-sm text-slate-500 mt-1">
                                Déjale un mensaje y se pondrá en contacto contigo.
                            </p>
                        </div>

                        <!-- Formulario Nativo controlado por Alpine -->
                        <form @submit.prevent="submitForm()" class="space-y-4">
                            
                            <!-- Alerta de Error en caso de fallo de red o servidor -->
                            <div x-show="errorMessage" x-cloak class="p-3 bg-rose-50 border border-rose-100 text-rose-700 text-xs rounded-xl" x-text="errorMessage"></div>

                            <!-- Campo: Nombre Completo -->
                            <div>
                                <label for="client_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Nombre completo
                                </label>
                                <input type="text" id="client_name" x-model="nameText" placeholder="Ej. Ana García" required :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>

                            <!-- Campo: Correo Electrónico -->
                            <div>
                                <label for="client_email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Correo electrónico
                                </label>
                                <input type="email" id="client_email" x-model="emailText" placeholder="tu@correo.com" required :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>

                            <!-- Campo: Teléfono (Opcional) -->
                            <div>
                                <label for="client_phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.83a1 1 0 01.9.553l2 4a1 1 0 01-.145 1.055L17 14l-2 2a11.042 11.042 0 01-5.5-5.5l2-2 2.943-2.943a1 1 0 011.055-.145l4 2a1 1 0 01.553.9v.583a2 2 0 01-2 2H3z"/></svg>
                                    Teléfono <span class="text-slate-400 font-normal lowercase">(opcional)</span>
                                </label>
                                <input type="tel" id="client_phone" x-model="phoneText" placeholder="+34 600 000 000" :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>

                            <!-- Campo: Mensaje con Contador Proporcional -->
                            <div>
                                <label for="message_content" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    Mensaje
                                </label>
                                <textarea id="message_content" x-model="messageText" maxlength="1000" rows="4" required :disabled="isSubmitting"
                                        placeholder="Cuéntale brevemente el motivo de consulta o tus necesidades..."
                                        class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 resize-none disabled:opacity-50 disabled:cursor-not-allowed"></textarea>
                                
                                <!-- Contador de caracteres -->
                                <div class="text-right text-slate-400 text-xs mt-1">
                                    <span x-text="messageText.length"></span>/1000
                                </div>
                            </div>

                            <!-- Botón de Envío Interactivo -->
                            <div class="pt-2">
                                <button type="submit" :disabled="isSubmitting"
                                        class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 group active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    
                                    <!-- Icono de Avión que se anima, oculto si está cargando -->
                                    <svg x-show="!isSubmitting" class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>

                                    <!-- Icono Spinner de Carga de Tailwind -->
                                    <svg x-show="isSubmitting" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.001 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>

                                    <span x-text="isSubmitting ? 'Procesando...' : 'Enviar mensaje'"></span>
                                </button>
                            </div>

                            <!-- Nota informativa inferior -->
                            <p class="text-center text-slate-500 text-xs mt-4 leading-normal">
                                El profesional recibirá tu mensaje y te contactará directamente.
                            </p>
                        </form>
                    </div>
                </div>                    
            </div>

        </div>
    </div>
</div>

</x-guest-layout>

