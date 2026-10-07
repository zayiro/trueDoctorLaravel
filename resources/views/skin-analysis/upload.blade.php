<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    <div class="min-h-screen bg-slate-50 py-12 px-4 md:px-6">
        <div class="max-w-5xl mx-auto">
            
            <!-- Encabezado de la Sección -->
            <div class="text-center mb-10">
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                    Análisis Avanzado de tu Piel
                </h1>
                <p class="text-base text-slate-600 max-w-xl mx-auto">
                    Sube una imagen para que nuestra IA verifique si cuenta con la iluminación y nitidez adecuadas para un reporte dermatológico.
                </p>
            </div>

            <!-- Contenedor Principal Reactivo con Alpine.js -->
            <div x-data="skinUpload()" class="bg-white rounded-3xl shadow-md border border-slate-200 p-6 md:p-8 space-y-6">

                <!-- Alerta de Errores Globales de Alpine -->
                <div x-show="errorMessage" x-transition x-cloak class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl">
                    <p class="text-red-700 text-sm font-medium" x-text="errorMessage"></p>
                </div>

                <!-- Formulario Único unificado para Laravel -->
                <form action="{{ route('skin-analysis.before-preview') }}" method="POST">
                    @csrf
                    <input type="hidden" name="validated_token" :value="validatedFilesToken">

                    <!-- ======================================================= -->
                    <!-- BLOQUE 1: Carga de Imagen Real y Botón de Validación    -->
                    <!-- ======================================================= -->
                    <div x-show="step === 'upload'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-2">📸 Captura o Sube la Foto de la Lesión</label>
                            
                            <div @click="$refs.fileInput.click()" class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center bg-slate-50 cursor-pointer hover:border-purple-500 hover:bg-slate-100 transition-all duration-300">
                                <svg class="mx-auto h-12 w-12 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                </svg>
                                <p class="text-sm text-slate-700 font-semibold">Haz clic para seleccionar o tomar la foto</p>
                                <p class="text-xs text-slate-400 mt-1">Formatos: JPG, PNG, WebP (Máx. 10MB)</p>
                            </div>
                            
                            <input type="file" id="image-file" x-ref="fileInput" @change="handleFileChange" accept="image/*" class="hidden">
                        </div>

                        <!-- Miniatura de Vista Previa -->
                        <div x-show="previewUrl" class="flex justify-center" x-cloak>
                            <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 p-1">
                                <img :src="previewUrl" alt="Vista previa de piel" class="w-48 h-48 object-cover rounded-xl">
                            </div>
                        </div>

                        <!-- Botón de Validación Óptica -->
                        <button type="button" @click="verificarImagen()" :disabled="loadingValidation || !previewUrl" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-4 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 active:scale-[0.99] transition-all disabled:opacity-40 disabled:pointer-events-none">
                            <span x-text="loadingValidation ? 'Analizando viabilidad óptica...' : '✔ Validar Calidad de Imagen'"></span>
                            <svg x-show="loadingValidation" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                    <!-- ======================================================= -->
                    <!-- BLOQUE 2: Datos Adicionales (Se activa al ser viable)    -->
                    <!-- ======================================================= -->
                    <div x-show="step === 'form'" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" x-cloak>
                        
                        <!-- Banner Informativo de Éxito Técnico -->
                        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex gap-3 items-center">
                            <span class="text-xl text-emerald-600">✨</span>
                            <p class="text-xs text-emerald-800 font-medium">
                                ¡Imagen aprobada con éxito! Cuenta con los parámetros de luz, enfoque y nitidez necesarios para el análisis dermatológico.
                            </p>
                        </div>

                        <!-- Email e Idioma -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-bold text-slate-800 mb-1.5">📧 Correo Electrónico</label>
                                <input type="email" name="email" id="email" placeholder="nombre@correo.com" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition text-sm text-slate-800" required>
                            </div>
                            <div>
                                <label for="language" class="block text-sm font-bold text-slate-800 mb-1.5">🌐 Idioma del Reporte</label>
                                <select name="language" id="language" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition text-sm text-slate-800">
                                    <option value="es" selected>Español (Spanish)</option>
                                    <option value="en">Inglés (English)</option>
                                    <option value="fr">Frances (French)</option>
                                    <option value="pt">Portugues (Portuguese)</option>
                                    <option value="de">Alemán (Deutsch)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ubicación Opcional -->
                        <div>
                            <label for="body_location" class="block text-sm font-bold text-slate-800 mb-1.5">📍 Ubicación en el Cuerpo <span class="text-xs font-normal text-slate-400">(Opcional)</span></label>
                            <select name="body_location" id="body_location" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition text-sm text-slate-800">
                                <option value="">Selecciona una zona…</option>
                                @foreach ($bodyLocations as $group => $options)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}" @selected(old('body_location') === $option)>{{ $option }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <!-- Descripción del Síntoma -->
                        <div>
                            <label for="description" class="block text-sm font-bold text-slate-800 mb-1.5">📝 Descripción o Síntomas <span class="text-xs font-normal text-slate-400">(Opcional)</span></label>
                            <textarea name="description" id="description" rows="3" placeholder="Ej: Empezó hace 1 semana, genera picazón leve y pica con el sudor..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition text-sm text-slate-800 resize-none"></textarea>
                        </div>

                        <!-- Botones de Acción Final -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="button" @click="changePhoto()" class="px-4 py-3 bg-slate-100 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-200 transition-all duration-200">
                                Cambiar Fotografía
                            </button>
                            <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white shadow-sm hover:bg-blue-700 transition-all duration-200">
                                Continuar
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <script>
        function skinUpload() {
            return {
                step: @json($resumeToken ? 'form' : 'upload'),
                errorMessage: '',
                loadingValidation: false,
                previewUrl: null,
                validatedFilesToken: @json($resumeToken),

                comprimirImagen(file, maxSide = 1600, quality = 0.85) {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        const url = URL.createObjectURL(file);
                        img.onload = () => {
                            const scale = Math.min(1, maxSide / Math.max(img.width, img.height));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(img.width * scale);
                            canvas.height = Math.round(img.height * scale);
                            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                            URL.revokeObjectURL(url);
                            canvas.toBlob(
                                blob => blob ? resolve(blob) : reject(new Error('No se pudo comprimir')),
                                'image/jpeg',
                                quality
                            );
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(url);
                            reject(new Error('No se pudo leer la imagen. Prueba con JPG o PNG.'));
                        };
                        img.src = url;
                    });
                },

                async verificarImagen() {
                    const fileInput = document.getElementById('image-file');
                    if (fileInput.files.length === 0) {
                        this.errorMessage = 'Por favor, selecciona una imagen primero.';
                        return;
                    }

                    this.loadingValidation = true;
                    this.errorMessage = '';

                    try {
                        const blob = await this.comprimirImagen(fileInput.files[0]);

                        const formData = new FormData();
                        formData.append('file', blob, 'foto.jpg');
                        formData.append('_token', '{{ csrf_token() }}');

                        const response = await fetch('{{ route('skin-analysis.validate-image') }}', {
                            method: 'POST',
                            body: formData,
                            headers: { 'Accept': 'application/json' }
                        });

                        let result = null;
                        try { result = await response.json(); } catch (e) {}

                        if (!result) {
                            this.errorMessage = 'El servidor respondió con un error (' + response.status + '). Intenta nuevamente.';
                            return;
                        }

                        if (result.success && result.is_viable) {
                            this.step = 'form';
                            this.validatedFilesToken = result.temp_token;
                        } else if (result.success) {
                            this.errorMessage = result.reason || 'La imagen no tiene la calidad requerida. Intenta con otra toma.';
                        } else {
                            this.errorMessage = result.error || 'Ocurrió un error inesperado al procesar la validación.';
                        }
                    } catch (error) {
                        this.errorMessage = error.message || 'Error de conexión con el servidor. Intenta nuevamente.';
                    } finally {
                        this.loadingValidation = false;
                    }
                },

                handleFileChange(event) {
                    const file = event.target.files[0];
                    if (file) {
                        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = URL.createObjectURL(file);
                        this.errorMessage = '';
                        this.validatedFilesToken = '';
                        this.step = 'upload';
                    }
                },
            
                async changePhoto() {
                    try {
                        await fetch('{{ route('skin-analysis.reset-image') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                    } catch (e) {}

                    this.step = 'upload';
                    this.validatedFilesToken = '';
                    this.previewUrl = null;
                    this.errorMessage = '';
                    document.getElementById('image-file').value = '';
                },
            }
        }
        </script>
</x-guest-layout>
