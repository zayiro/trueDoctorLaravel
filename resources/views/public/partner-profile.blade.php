{{--
Perfil público completo del médico.
Variables: $doctor (App\Models\Doctor) y, opcional, $bookingUrl.
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
    $offerings   = $addresses->filter(fn ($a) => $a->services->isNotEmpty())->values();

    $bio       = data_get($doctor, 'bio') ?? data_get($doctor, 'description') ?? data_get($doctor, 'about');
    $years     = data_get($doctor, 'years_experience') ?? data_get($doctor, 'experience_years');
    $license   = data_get($doctor, 'license_number') ?? data_get($doctor, 'medical_license');
    $languages = data_get($doctor, 'languages');
    $languages = is_array($languages) ? implode(', ', $languages) : $languages;

    $modalities = collect([$physical->isNotEmpty() ? 'Presencial' : null, $hasVirtual ? 'Telemedicina' : null])->filter()->implode(' · ');
    $isVerified = (string) data_get($doctor, 'validation_status') === 'approved';
    $bookingUrl = $bookingUrl ?? '#servicios';
@endphp

<x-guest-layout
    meta_title="Perfil de Doctor o Centro Médico | OpenDoctor"
    meta_description="Perfil Médico especializado"
>

<div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 font-sans my-6">
    <div class="grid grid-cols-1 md:grid-cols-12 w-full">
        
        {{-- COLUMNA IZQUIERDA: Imagen y Biografía --}}
        <div class="md:col-span-5 flex flex-col bg-gray-50/50 w-full border-r border-gray-100">           
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

            <div class="p-6 flex-1 bg-white">
                <h3 class="text-md font-bold text-sky-600 uppercase tracking-wider mb-2">Biografía</h3>
                <div class="text-md text-gray-600 leading-relaxed space-y-3">
                    <p>{{ $doctor->bio ?? 'Sin descripción profesional disponible en este momento.' }}</p>
                    @if($doctor->experience_years)
                        <p class="text-[11px] text-gray-500 italic">Formado con los más altos estándares científicos y éticos en salud.</p>
                    @endif
                </div>
            </div>
            {{-- Reseñas --}}
            <div id="resenas" class="bg-white p-6 sm:p-8 max-w-4xl mx-auto my-6 font-sans scroll-mt-24">

                {{-- Encabezado: promedio + total --}}
                <div class="flex items-center gap-2 mb-6">
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Reseñas de pacientes</h3>
                    <div class="flex items-center gap-1.5 text-sm font-medium text-slate-500">
                        <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                        </svg>
                        <span class="text-slate-900 font-semibold">{{ number_format($reviewSummary['average'], 1) }}</span>
                        <span>({{ $reviewSummary['count'] }} {{ Str::plural('reseña', $reviewSummary['count']) }})</span>
                    </div>
                </div>

                {{-- Distribución por estrellas --}}
                @if ($reviewSummary['count'] > 0)
                    <div class="space-y-1.5 mb-6">
                        @foreach ($reviewSummary['distribution'] as $stars => $total)
                            @php $pct = round($total / $reviewSummary['count'] * 100); @endphp
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="w-3 text-right">{{ $stars }}</span>
                                <svg class="w-3 h-3 text-amber-400 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                </svg>
                                <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-amber-400 rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="w-6 text-right">{{ $total }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Mensajes --}}
                @if (session('success'))
                    <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm rounded-xl px-4 py-3 mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-rose-50 border border-rose-100 text-rose-700 text-sm rounded-xl px-4 py-3 mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-rose-50 border border-rose-100 text-rose-700 text-sm rounded-xl px-4 py-3 mb-6 space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- Formulario / avisos según el estado del usuario --}}
                @guest
                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3.5 text-sm text-slate-600 mb-6 leading-relaxed">
                        <a href="{{ route('login') }}" class="font-semibold text-sky-600 hover:text-sky-700 underline">Inicia sesión</a>
                        como paciente para dejar una reseña sobre este profesional.
                    </div>
                @else
                    @if (auth()->user()->role !== 'patient')
                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3.5 text-sm text-slate-600 mb-6 leading-relaxed">
                            Solo los pacientes con una cita completada pueden dejar reseñas.
                        </div>
                    @elseif ($canReview)
                        <form method="POST"
                            action="{{ route('reviews.store') }}"
                            x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0, comment: @js(old('comment', '')), submitting: false }"
                            @submit="submitting = true"
                            class="bg-slate-50/60 border border-slate-100 rounded-2xl p-5 mb-6 space-y-4">
                            @csrf
                            <input type="hidden" name="reviewable_type" value="doctor">
                            <input type="hidden" name="reviewable_id" value="{{ $doctor->id }}">
                            <input type="hidden" name="rating" :value="rating">

                            <div>
                                <p class="text-sm font-semibold text-slate-800 mb-2">¿Cómo fue tu experiencia?</p>
                                <div class="flex items-center gap-1" @mouseleave="hover = 0">
                                    @foreach (range(1, 5) as $i)
                                        <button type="button"
                                                @click="rating = {{ $i }}"
                                                @mouseenter="hover = {{ $i }}"
                                                class="p-0.5 focus:outline-none focus:ring-2 focus:ring-sky-500 rounded"
                                                aria-label="{{ $i }} {{ $i === 1 ? 'estrella' : 'estrellas' }}">
                                            <svg class="w-7 h-7 fill-current transition-colors"
                                                :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-slate-200'"
                                                viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label for="review_comment" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Comentario <span class="text-slate-400 font-normal lowercase">(opcional)</span>
                                </label>
                                <textarea id="review_comment" name="comment" x-model="comment" maxlength="500" rows="3"
                                        placeholder="Cuéntale a otros pacientes cómo fue tu consulta..."
                                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent placeholder:text-slate-400 resize-none"></textarea>
                                <div class="text-right text-slate-400 text-xs mt-1"><span x-text="comment.length"></span>/500</div>
                            </div>

                            <button type="submit" :disabled="rating === 0 || submitting"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-all text-sm shadow-md shadow-blue-600/15 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="submitting ? 'Publicando...' : 'Publicar reseña'"></span>
                            </button>
                        </form>
                    @else
                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3.5 text-sm text-slate-600 mb-6 leading-relaxed">
                            Podrás dejar tu reseña una vez completes una cita con este profesional.
                        </div>
                    @endif
                @endguest

                {{-- Listado --}}
                @forelse ($reviews as $review)
                    <article class="py-4 border-t border-slate-100 first:border-t-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-sky-50 text-sky-600 flex items-center justify-center text-sm font-bold uppercase select-none">
                                    {{ mb_substr($review->author_name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $review->author_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $review->created_at->translatedFormat('d M Y') }} · Paciente verificado</p>
                                </div>
                            </div>
                            <x-review-stars :rating="$review->rating" />
                        </div>

                        @if ($review->comment)
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $review->comment }}</p>
                        @endif
                    </article>
                @empty
                    <div class="border border-dashed border-slate-200 rounded-2xl p-10 text-center flex flex-col items-center justify-center bg-white">
                        <div class="bg-slate-50 rounded-full p-4 text-slate-400 mb-4 flex items-center justify-center shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641l-.318 2.396 2.396-.318a2.25 2.25 0 011.508.473A8.966 8.966 0 0012 20.25z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-slate-900 tracking-tight mb-2">Aún no hay reseñas públicas</h4>
                        <p class="text-sm text-slate-500 max-w-md mx-auto leading-normal">Los pacientes podrán dejar reseñas una vez completen una cita con el profesional.</p>
                    </div>
                @endforelse

                @if ($reviews->hasPages())
                    <div class="mt-6">{{ $reviews->links() }}</div>
                @endif
            </div>

        </div>
        {{-- COLUMNA DERECHA: Detalles Profesionales y Contacto --}}
        <div class="md:col-span-7 p-6 md:p-8 flex flex-col bg-white space-y-6">
            
            {{-- Bloque de Perfil --}}
            <div>
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-1">Perfil Profesional</h3>
                <h2 class="text-base font-bold text-gray-800">Dr. {{ $doctor->user->name }}</h2>
                <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
                
                {{-- Credenciales Académicas --}}
                <div class="mt-3 text-xs text-gray-600 space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100/80">
                    <p class="font-bold text-gray-700">Licencia Médica: <span class="font-normal text-gray-600">{{ $doctor->medical_license ?? 'En proceso de verificación' }}</span></p>
                    <p class="font-bold text-gray-700">Identificación: <span class="font-normal text-gray-600">{{ $doctor->identification }}</span></p>
                </div>
            </div>

            {{-- Sección: Especialidades --}}
            <div>
                <h3 class="text-xs font-bold text-sky-600 uppercase tracking-wider mb-2.5">Especialidades</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($doctor->specialties as $specialty)
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-[11px] font-medium rounded-full shadow-xs">
                            {{ $specialty->name }}
                        </span>
                    @empty
                        <span class="px-3 py-1.5 bg-white border border-slate-200 text-slate-500 text-[11px] font-medium rounded-full shadow-xs">
                            Medicina General
                        </span>
                    @endforelse
                </div>
            </div>

            {{-- Fila de Datos Rápidos: Experiencia, Idiomas, Tipo --}}
            <div class="grid grid-cols-3 gap-4 border-t border-b border-gray-100 py-4 text-xs">
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Experiencia</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->experience_years ?? '0' }} Años+</p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Idiomas</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">
                        {{ is_array($doctor->languages) ? implode(', ', $doctor->languages) : 'Español' }}
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Vinculación</h4>
                    <p class="text-gray-700 mt-0.5 font-medium">{{ $doctor->clinic_id ? 'Staff de Clínica' : 'Médico Autónomo' }}</p>
                </div>
            </div>
            {{-- SECCIÓN DE GALERÍA DE IMÁGENES INTERACTIVA --}}
            @if($doctor->gallery && $doctor->gallery->isNotEmpty())
                @php
                    $galleryImages = $doctor->gallery->map(fn($img) => \Storage::url($img->path))->toArray();
                @endphp

                <div class="space-y-3" 
                     x-data="{ 
                        isOpen: false, 
                        currentIndex: 0, 
                        images: {{ json_encode($galleryImages) }},
                        next() { this.currentIndex = (this.currentIndex + 1) % this.images.length },
                        prev() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length }
                     }"
                     @keydown.window.escape="isOpen = false"
                     @keydown.window.arrow-right="if(isOpen) next()"
                     @keydown.window.arrow-left="if(isOpen) prev()">
                    
                    <h4 class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Instalaciones y Consultorio</h4>
                    
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($doctor->gallery->take(3) as $index => $image)
                            <div class="relative aspect-video rounded-xl overflow-hidden border border-slate-200/60 bg-slate-100 cursor-pointer group shadow-xs hover:border-sky-500 transition-all duration-200"
                                 @click="isOpen = true; currentIndex = {{ $index }}">
                                <img src="{{ \Storage::url($image->path) }}" 
                                     alt="Instalación {{ $index + 1 }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                
                                @if($index === 2 && $doctor->gallery->count() > 3)
                                    <div class="absolute inset-0 bg-slate-900/60 flex items-center justify-center text-white text-xs font-bold font-mono tracking-wide backdrop-blur-xs">
                                        +{{ $doctor->gallery->count() - 3 }} fotos
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- VENTANA POPUP FLOTANTE (LIGHTBOX) -->
                    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/90 p-4 drop-shadow-2xl backdrop-blur-md"
                         x-show="isOpen" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-cloak>
                        
                        <button type="button" @click="isOpen = false" 
                                class="absolute top-4 right-4 text-white/80 hover:text-white p-2 rounded-xl bg-white/10 hover:bg-white/20 transition-all focus:outline-none z-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="relative max-w-4xl max-h-[85vh] flex items-center justify-center" @click.away="isOpen = false">
                            <button type="button" @click="prev()" x-show="images.length > 1"
                                    class="absolute left-4 bg-white/10 hover:bg-white/20 text-white p-3 rounded-xl transition-all focus:outline-none z-40 select-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                            </button>

                            <img :src="images[currentIndex]" 
                                 class="max-w-full max-h-[80vh] rounded-2xl object-contain shadow-2xl select-none"
                                 alt="Galería ampliada">

                            <button type="button" @click="next()" x-show="images.length > 1"
                                    class="absolute right-4 bg-white/10 hover:bg-white/20 text-white p-3 rounded-xl transition-all focus:outline-none z-40 select-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                        </div>

                        <div class="absolute bottom-4 bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-white text-xs font-medium tracking-wide">
                            <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Sección Inferior: Datos de Contacto y Ubicación --}}
            <div class="text-md text-gray-600">
                @php
                    $activeAddress = $doctor->addresses->first();
                @endphp

                @if($activeAddress)
                    <div class="mt-4 pl-4 border-l-2 border-sky-500 py-0.5 space-y-0.5">
                        <p class="font-bold text-sky-600 uppercase tracking-wider text-[11px]">Modalidad de consulta</p>
                        <p class="text-gray-600 leading-tight">{{ $activeAddress->address }}</p>
                        <p class="text-md text-gray-400 font-medium">{{ $activeAddress->name }} - Cali, Colombia</p>
                    </div>
                @endif
                
                {{-- Formulario de Contacto Dinámico con Alpine.js Fetch --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8 mt-5"
                    x-data="{ 
                        nameText: '',
                        emailText: '',
                        phoneText: '',
                        messageText: '',
                        isSubmitting: false,
                        isSuccess: false,
                        errorMessage: '',
                        
                        async submitForm() {
                            this.isSubmitting = true;
                            this.errorMessage = '';
                            try {
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

                    <!-- PANTALLA DE ÉXITO -->
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
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-slate-900 leading-tight">
                                Contactar con {{ $doctor->gender === 'female' ? 'Dra. ' : 'Dr. ' }}{{ $doctor->user->name }}
                            </h3>
                            <p class="text-sm text-slate-500 mt-1">Déjale un mensaje y se pondrá en contacto contigo.</p>
                        </div>

                        <form @submit.prevent="submitForm()" class="space-y-4">
                            <div x-show="errorMessage" x-cloak class="p-3 bg-rose-50 border border-rose-100 text-rose-700 text-xs rounded-xl" x-text="errorMessage"></div>

                            <div>
                                <label for="client_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Nombre completo
                                </label>
                                <input type="text" id="client_name" x-model="nameText" placeholder="Ej. Ana García" required :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>

                            <div>
                                <label for="client_email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Correo electrónico
                                </label>
                                <input type="email" id="client_email" x-model="emailText" placeholder="tu@correo.com" required :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>

                            <div>
                                <label for="client_phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.83a1 1 0 01.9.553l2 4a1 1 0 01-.145 1.055L17 14l-2 2a11.042 11.042 0 01-5.5-5.5l2-2 2.943-2.943a1 1 0 011.055-.145l4 2a1 1 0 01.553.9v.583a2 2 0 01-2 2H3z"/></svg>
                                    Teléfono <span class="text-slate-400 font-normal lowercase">(opcional)</span>
                                </label>
                                <input type="tel" id="client_phone" x-model="phoneText" placeholder="+57 300 000 000" :disabled="isSubmitting"
                                    class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>
                            <div>
                                <label for="message_content" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    Mensaje
                                </label>
                                <textarea id="message_content" x-model="messageText" maxlength="1000" rows="4" required :disabled="isSubmitting"
                                        placeholder="Cuéntale brevemente el motivo de consulta o tus necesidades..."
                                        class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent focus:bg-white transition-all placeholder:text-slate-400 resize-none disabled:opacity-50 disabled:cursor-not-allowed"></textarea>
                                <div class="text-right text-slate-400 text-xs mt-1">
                                    <span x-text="messageText.length"></span>/1000
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" :disabled="isSubmitting"
                                        class="group w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2 text-base transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="isSubmitting" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.001 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="isSubmitting ? 'Procesando...' : 'Enviar mensaje'"></span>
                                    <svg x-show="!isSubmitting" class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </button>
                            </div>

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
